<?php

namespace MobStar;

use MobStar\Storage\Vote\EloquentVoteRepository as VoteRepository;

use DB;

class ResponseHelper
{

    private static $S3client;


    public static function getUserProfile( $session, $normal = false )
    {
        $userId = (int)$session->token_user_id;

        $users = UserHelper::getUsersInfo( array( $userId ) );

        $user = $users[ $userId ];

        $return = array();

        if ($normal) {
            $profileImage = ( isset( $user['user_profile_image'] ) ) ? 'http://' . $_ENV[ 'URL' ] . '/' . $user['user_profile_image'] : '';
            $profileCover = ( isset( $user['user_cover_image'] ) )   ? 'http://' . $_ENV[ 'URL' ] . '/' . $user['user_cover_image'] : '';
        } else {
            $profileImage = self::getResourceUrl( $user['user_profile_image'] );
            $profileCover = self::getResourceUrl( $user['user_cover_image'] );
        }

        $return[ 'profileImage' ] = $profileImage;
        $return[ 'profileCover' ] = $profileCover;

        $return[ 'token' ] = $session->token_value;
        $return[ 'userId' ] = $userId;

        if ( ( empty( $user['display_name'] ) ) && $session->token_type != 'Native' ) {

            $return[ 'userDisplayName' ] = $user['display_name'];
            $return[ 'userName' ] = $user['name'];
            $return[ 'fullName' ] = $user['full_name'];

        } elseif ( ( !empty( $user['display_name'] ) ) && $session->token_type != 'Native' ) {

            $return[ 'userDisplayName' ] = $user['display_name'];
            $return[ 'userName' ] = $user['name'];
            $return[ 'fullName' ] = $user['full_name'];

        } else {

            $return[ 'userDisplayName' ] = $user['display_name'];
            $return[ 'userName' ] = $user['name'];
        }

        $return[ 'userTagline' ] = isset( $user['user_tagline'] ) ?  $user['user_tagline'] : '';
        $return[ 'userBio' ] = isset( $user['user_bio'] ) ?  $user['user_bio'] : '';

        return $return;
    }


    public static function userDetails( $userId )
    {
        $users = UserHelper::getUsersInfo( array( $userId ) );
        $socialNames = UserHelper::getSocialUserNames( $users );

        $user = $users[ $userId ];
        $userSocialNames = isset( $socialNames[ $userId ] ) ? $socialNames[ $userId ] : array();

        $return = array();

        if ( ( $user['user_display_name'] == '' ) || ( is_null( $user['user_name'] ) ) || ( is_null( $user['user_email'] ) ) ) {
            if( isset( $userSocialNames['facebook'] ) )
            {
                $return[ 'userName' ] = $userSocialNames['facebook']['name'];
                $return[ 'displayName' ] = $userSocialNames['facebook']['display_name'];
                $return[ 'fullName' ] = $userSocialNames['facebook']['full_name'];
            }
            elseif( isset( $userSocialNames[ 'twitter' ] ) )
            {
                $return[ 'userName' ] = $userSocialNames['twitter']['name'];
                $return[ 'displayName' ] = $userSocialNames['twitter']['display_name'];
                $return[ 'fullName' ] = $userSocialNames['twitter']['full_name'];
            }
            elseif( isset( $userSocialNames[ 'google' ] ) )
            {
                $return[ 'userName' ] = $userSocialNames[ 'google' ]['name'];
                $return[ 'displayName' ] = $userSocialNames[ 'google' ]['display_name'];
                $return[ 'fullName' ] = $userSocialNames[ 'google' ]['full_name'];
            }
        }
        else
        {
            $return[ 'userName' ] = $user['user_name'];
            $return[ 'displayName' ] = $user['user_display_name'];
            $return[ 'fullName' ] = $user['user_full_name'];
        }
        return $return;
    }


    public static function getusernamebyid( $userId )
    {
        $users = UserHelper::getUsersInfo( array( $userId ) );

        $user = $users[ $userId ];

        return isset( $user['display_name'] ) ? $user['display_name'] : 'Guest';
    }


    public static function particUser( $userId, $session, $includeStars = false )
    {
        $users = UserHelper::getUsersInfo( array( $userId ) );

        $user = $users[ $userId ];

        $return = array();

        $return['userId'] = $userId;

        $return['profileImage'] = self::getResourceUrl( $user['user_profile_image'] );

        $return['profileCover'] = self::getResourceUrl( $user['user_cover_image'] );

        $return['displayName'] = $user['display_name'];

        return $return;
    }


    protected static function userProfileInfo( $userId, $normal = false )
    {
        $users = UserHelper::getUsersInfo( array( $userId ), array( 'votes' ) );

        $user = $users[ $userId ];

        $profileImage = '';
        $profileCover = '';

        if ($normal) {
            $profileImage = ( isset( $user['user_profile_image'] ) ) ? 'http://' . $_ENV[ 'URL' ] . '/' . $user['user_profile_image'] : '';
            $profileCover = ( isset( $user['user_cover_image'] ) )   ? 'http://' . $_ENV[ 'URL' ] . '/' . $user['user_cover_image'] : '';
        } else {
            $profileImage = self::getResourceUrl( $user['user_profile_image'] );
            $profileCover = self::getResourceUrl( $user['user_cover_image'] );
        }

        $return = array();
        $return['id'] = $user['user_id'];
        $return['email'] = $user['user_email'];
        $return['tagLine'] = $user['user_tagline'] ? $user['user_tagline'] : '';
        $return['bio'] = $user['user_bio'] ? $user['user_bio'] :'';
        $return['usergroup'] = $user['user_user_group'] ? $user['user_user_group'] :'';
        $return['profileImage'] = $profileImage;
        $return['profileCover'] = $profileCover;

        if ( ( $user['user_display_name'] == '' ) || ( is_null( $user['user_name'] ) ) || ( is_null( $user['user_email'] ) ) ) {

            if ( $user['user_facebook_id'] || $user['user_google_id'] || $user['user_twitter_id'] ) {

                $return[ 'userName' ] = $user['name'];
                $return[ 'displayName' ] = $user['display_name'];
                $return[ 'fullName' ] = $user['full_name'];
            }

        } else {
            $return[ 'userName' ] = $user['user_name'];
            $return[ 'displayName' ] = $user['user_display_name'];
            $return[ 'fullName' ] = $user['user_full_name'];
        }

        $return['rank'] = $user['user_rank'];
        $return['votes'] = $user['votes']['me']['up'];

        return $return;
    }


    public static function userProfile( $userId, $sessionUserId, $normal = false )
    {
        $fields = array( 'votes', 'stars' );

        $users = UserHelper::getUsersInfo( array( $userId ), $fields );

        $user = $users[ $userId ];

        $profile = self::userProfileInfo( $userId, $normal );


        if ($userId != $sessionUserId) {

            $starFlags = self::getStarFlags( $userId, $sessionUserId );

            $profile[ 'isMyStar' ] = (int)$starFlags['isMyStar'];
            $profile['iAmStar'] = (int)$starFlags['iAmStar'];
        }

        $starredBy = $user['stars_info']['me'];
        $profile['fans'] = count( $starredBy );

        return $profile;
    }


    public static function oneUser( $userId, $sessionUserId, $includeStars = false, $normal = false )
    {
        $userProfile = self::userProfile( $userId, $sessionUserId, $normal );
        $stars = array();
        $starredBy = array();

        if ( $includeStars ) {

            $stars = self::getStars( $userId );

            $starredBy = self::getFollowers( $userId );
        }

        $userProfile['stars'] = $stars;
        $userProfile['starredBy'] = $starredBy;
        $userProfile['fans'] = count( $starredBy );

        return $userProfile;
    }


    public static function oneUser_StarsCountsOnly( $userId, $sessionUserId )
    {
        $fields = array( 'votes', 'stars' );

        $users = UserHelper::getUsersInfo( array( $userId ), $fields );

        $user = $users[ $userId ];

        $userProfile = self::userProfile( $userId, $sessionUserId );

        $userProfile['starsCount'] = self::getUniqueStarsCount( $user['stars_info']['my'] );
        $userProfile['starredByCount'] = count( $user['stars_info']['me'] );
        $userProfile['fans'] = $userProfile['starredByCount'];

        return $userProfile;
    }


    public static function oneEntry( $entry, $sessionUserId, $includeUser = false )
    {
        return self::oneEntryById( $entry->entry_id, $sessionUserId, $includeUser );
    }


    public static function oneEntryInfo( $entryId, $sessionUserId )
    {
        EntryHelper::prepareVotedByUserInfo( array( $entryId ), $sessionUserId );

        $entries = EntryHelper::getEntries(
            array( $entryId ),
            array( 'commentCounts', 'filesInfo', 'tagNames', 'totalVotes', 'votedByUser' ),
            $sessionUserId
        );
        $entry = $entries[ $entryId ];

        $noEntry = array(
            'id' => $entryId
        );

        if( empty( $entry ) )
        {
            return $noEntry;
        }

        $data = self::makeEntryBasicInfo( $entry, $sessionUserId );

        if( empty( $data ) )
        {
            return $noEntry;
        }

        $data[ 'user' ] = self::oneUser_StarsCountsOnly( $entry->entry_user_id, $sessionUserId );

        return $data;

    }


    // same as self::oneEntry but takes entryId not entry object as parameter
    public static function oneEntryById( $entryId, $sessionUserId, $includeUser = false )
    {
        EntryHelper::prepareVotedByUserInfo( array( $entryId ), $sessionUserId );

        $entries = EntryHelper::getEntries(
            array( $entryId ),
            array( 'commentCounts', 'filesInfo', 'tagNames', 'totalVotes', 'votedByUser' ),
            $sessionUserId
        );
        $entry = $entries[ $entryId ];

        $noEntry = array(
            'id' => $entryId
        );

        if( empty( $entry ) )
        {
            return $noEntry;
        }

        $data = self::makeEntryBasicInfo( $entry, $sessionUserId );

        if( empty( $data ) )
        {
            return $noEntry;
        }

        if( $includeUser )
        {
            $data[ 'user' ] = self::oneUser( $entry->entry_user_id, $sessionUserId );
        }

        return $data;
    }


    // moved from EntryController::oneEntryNew
    public static function oneEntryNewById( $entryId, $sessionUserId, $includeUser = false )
    {
        $entries = EntryHelper::getEntries(
            array( $entryId ),
            array( 'commentCounts', 'filesInfo', 'tagNames', 'totalVotes' )
        );
        $entry = $entries[ $entryId ];

        $current = array();

        $current[ 'id' ] = $entryId;
        if( $entry->entry_splitVideoId ) $current['splitVideoId'] = $entry->entry_splitVideoId;
        if( isset( $entry->entry_category_id )  && $entry->entry_category_id == 3 )
        {
            $current[ 'subcategory' ] = $entry->entry_subcategory;
            $current[ 'age' ] = $entry->entry_age;
            $current[ 'height' ] = $entry->entry_height;
        }
        $current[ 'category' ] = $entry->categoryInfo->category_name;
        $current[ 'type' ] = $entry->entry_type;

        if( $includeUser )
        {
             $current[ 'user' ] = self::oneUser( $entry->entry_user_id, $sessionUserId );
        }

        $current[ 'name' ] = $entry->entry_name;
        $current[ 'description' ] = $entry->entry_description;

        $totalComments = $entry->commentCounts;
        if ( $entry instanceof \Entry ) {
            $totalviews = $entry->viewsTotal();
        } else {
            $totalviews = $entry->entry_views + $entry->entry_views_added; // @todo must be in model
        }
        $current[ 'totalComments' ] = $totalComments;
        $current[ 'totalviews' ] = $totalviews;
        $current[ 'created' ] = $entry->entry_created_date;
        $current[ 'modified' ] = $entry->entry_modified_date;

        $current[ 'tags' ] = $entry->tagNames;

        $current[ 'entryFiles' ] = array();
        $EntryFile = $entry->filesInfo;
        if(count($EntryFile) <= 0)
            return false;
        foreach( $EntryFile as $file )
        {
            $current['entryFiles'][] = self::entryFile( $file );
        }
        $current['videoThumb'] = self::entryThumb( $entry, $EntryFile );

        if( ! self::isEntryFilesValid( $entry, $EntryFile ) )
        {
            return false;
        }

        $current[ 'upVotes' ] = $entry->totalVotes['up'];
        $current[ 'downVotes' ] = $entry->totalVotes['down'];
        $current[ 'rank' ] = $entry->entry_rank;
        $current[ 'language' ] = $entry->entry_language;

        if( $entry->entry_deleted )
        {
            $current[ 'deleted' ] = true;
        }
        else
        {
            $current[ 'deleted' ] = false;
        }

        $voteInfo = EntryHelper::getVotedByUserInfo( array( $entry->entry_id ), $sessionUserId );
        $voteInfo = array_pop( $voteInfo );

        $current['isVotedByYou'] = empty( $voteInfo['up'] ) ? 0 : 1;

        return $current;
    }


    private static function makeEntryBasicInfo( $entry, $sessionUserId )
    {
        if( ! self::isEntryFilesValid( $entry, $entry->filesInfo ) )
        {
            return null;
        }

        $data = array();
        $data['id'] = $entry->entry_id;
        if( $entry->entry_splitVideoId ) {
            $data['splitVideoId'] = $entry->entry_splitVideoId;
        }
        $data['category'] = $entry->categoryInfo->category_name;

        if( isset( $entry->entry_category_id )  && $entry->entry_category_id == 3 )
        {
            $data['subcategory'] = $entry->entry_subcategory;
            $data['age'] = $entry->entry_age;
            $data['height'] = $entry->entry_height;
        }

        $data['type'] = $entry->entry_type;

        $data['name'] = $entry->entry_name;
        $data['description'] = $entry->entry_description;
        $data['totalComments'] = $entry->commentCounts;
        $data['totalviews'] = $entry->entry_views + $entry->entry_views_added;
        $data['created'] = $entry->entry_created_date;
        $data['modified'] = $entry->entry_modified_date;

        $data['tags'] = $entry->tagNames;

        $data['entryFiles'] = array();
        foreach( $entry->filesInfo as $file ) {
            $data['entryFiles'][] = self::entryFile( $file );
        }

        $data['videoThumb'] = self::entryThumb( $entry, $entry->filesInfo );

        $data['upVotes'] = $entry->totalVotes['up'];
        $data['downVotes'] = $entry->totalVotes['down'];

        $data['rank'] = $entry->entry_rank;
        $data['language'] = $entry->entry_language;

        $data['deleted'] = $entry->entry_deleted ? true : false;

        $voteInfo = EntryHelper::getVotedByUserInfo( array( $entry->entry_id ), $sessionUserId );
        $voteInfo = array_pop( $voteInfo );

        $data['isVotedByYou'] = empty( $voteInfo['up'] ) ? 0 : 1;

        return $data;
    }


    public static function isEntryFilesValid( $entry, $files )
    {
        $isValid = false;

        switch( $entry->entry_type )
        {
            case 'image':
                if( count( $files ) == 1 ) $isValid = true;
                break;
            case 'audio':
                if( count( $files ) == 2 ) $isValid = true;
                break;
            case 'video':
                if( count( $files ) == 1 ) $isValid = true;
                break;
            case 'video_youtube':
                if( count( $files ) == 2  )
                {
                    $fileTypes = array();
                    foreach( $files as $file )
                    {
                        $fileTypes[] = $file->entry_file_type;
                    }
                    if( in_array( 'video_youtube', $fileTypes ) )
                    {
                        $isValid = true;
                    }
                }
                break;
            default:
               $isValid = false;
        }
        return $isValid;
    }


    public static function entryFile( $file )
    {
        return array(
            'fileType' => $file->entry_file_type,
            'filePath' => self::filePath( $file )
        );
    }


    private static function filePath( $file )
    {
        $path = '';
        switch( $file->entry_file_location_type )
        {
            case 'url':
                $path = $file->entry_file_location;
                break;
            case 'S3':
            case 's3':
            case '':
            default:
                $path = self::getResourceUrl( $file->entry_file_name . '.' . $file->entry_file_type );
                break;
        }
        return $path;
    }


    public static function entryThumb( $entry, $files )
    {
        $thumb = '';
        switch( $entry->entry_type )
        {
            case 'video':
                $thumb = self::entryVideoThumb( $files );
                break;
            case 'video_youtube':
                $thumb = self::entryYoutubeThumb( $files );
                break;
        }
        return $thumb;
    }


    private static function entryVideoThumb( $files )
    {
        $thumb = '';
        foreach( $files as $file )
        {
            if( $file->entry_file_type == 'mp4' )
            {
                $thumb = self::getResourceUrl( 'thumbs/' . $file->entry_file_name . '-thumb.jpg' );
            }
        }
        return $thumb;
    }


    private static function entryYoutubeThumb( $files )
    {
        $thumb = '';
        foreach( $files as $file )
        {
            if( $file->entry_file_type != 'video_youtube' )
            {
                $thumb = self::filePath( $file );
            }
        }
        return $thumb;
    }


    protected static function getUniqueStarsCount( $stars_info )
    {
        // for some reasons star may appear multiple times
        $processedStarUsers = array(); // holds already processed stars.

        foreach( $stars_info as $star_info ) {

            // do not process already processed stars
            if( isset( $processedStarUsers[ $star_info['star_user_id'] ] ) ) {
                continue;
            } else {
                $processedStarUsers[ $star_info['star_user_id'] ] = true;
            }
        }

        return count( $processedStarUsers );
    }


    public static function getStars( $userId )
    {
        $users = UserHelper::getUsersInfo( array( $userId ), array( 'stars.users') );

        $user = $users[ $userId ];

        $stars = array();

        // for some reasons star may appear multiple times
        $processedStarUsers = array(); // holds already processed stars.

        foreach( $user['stars_info']['my'] as $star_info ) {

            // do not process already processed stars
            if( isset( $processedStarUsers[ $star_info['star_user_id'] ] ) ) {
                continue;
            } else {
                $processedStarUsers[ $star_info['star_user_id'] ] = true;
            }

            $stars[] = self::getStar( $star_info );
        }

        return $stars;
    }


    public static function getStar( $starInfo )
    {
        $star = array();
        $star['starId'] = $starInfo['star_user_id'];

        $starUserDetails = self::userDetails( $starInfo['star_user_id'] );
        $star['starName'] = isset( $starUserDetails['displayName'] ) ? $starUserDetails['displayName'] : '';
        $star['starredDate'] = $starInfo['star_date'];

        $starUserInfo = UserHelper::getBasicInfo( array( $starInfo['star_user_id'] ) );
        $starUserInfo = array_pop( $starUserInfo );

        $star['profileImage'] = self::getResourceUrl( $starUserInfo['user_profile_image'] );
        $star['profileCover'] = self::getResourceUrl( $starUserInfo['user_cover_image'] );
        $star['rank'] = $starUserInfo['user_rank'];
        $star['stat'] = $starUserInfo['user_entry_rank'];

        return $star;
    }


    public static function getFollowers( $userId )
    {
        $users = UserHelper::getUsersInfo( array( $userId ), array( 'stars.users') );

        $user = $users[ $userId ];

        $starredBy = array();

        // for some reasons star may appear multiple times
        $processedStarUsers = array(); // holds already processed stars.

        foreach( $user['stars_info']['me'] as $star_info ) {

            // commented out to follow old behaviour ( wrong )
            // @todo uncomment it work correct working
            // do not process already processed stars
//             if( isset( $processedStarUsers[ $star_info['star_user_id'] ] ) ) {
//                 continue;
//             } else {
//                 $processedStarUsers[ $star_info['star_user_id'] ] = true;
//             }

            $starredBy[] = self::getFollower( $star_info );
        }

        return $starredBy;
    }


    public static function getFollower( $starInfo )
    {
        $star = array();

        $starUserDetails = self::userDetails( $starInfo['star_user_id'] );

        $star['starId'] = $starInfo['star_user_id'];
        $star['starName'] = isset( $starUserDetails['displayName'] ) ? $starUserDetails['displayName'] : '';
        $star['starredDate'] = $starInfo['star_date'];

        $starUserInfo = UserHelper::getBasicInfo( array( $starInfo['star_user_id'] ) );
        $starUserInfo = array_pop( $starUserInfo );

        $star['profileImage'] = self::getResourceUrl( $starUserInfo['user_profile_image'] );
        $star['profileCover'] = self::getResourceUrl( $starUserInfo['user_cover_image'] );

        return $star;
    }


    public static function getStarFlags( $userId, $sessionUserId )
    {
        $starFlags = UserHelper::getStarFlags( array( $userId ), $sessionUserId );

        return $starFlags[ $userId ];
    }


    public static function getEntryVotes( $entryId )
    {
        $voteRepository = new VoteRepository();

        $votes = $voteRepository->getTotalVotesForEntries( $entryId );

        if( empty( $votes ) ) {
            $votes = new \stdClass();
            $votes->votes_up = 0;
            $votes->votes_down = 0;
        }

        return $votes;
    }


    public static function getResourceUrl( $name )
    {
        if ( ! self::$S3client )
            self::$S3client = getS3Client();

        return $name
            ? self::$S3client->getObjectUrl( \Config::get('app.bucket'), $name, '+720 minutes' )
            : '';
    }
}
