<?php

namespace MobStar;

class ResponseHelper
{

    public static function getUserProfile( $session, $normal = false )
    {
        $userId = (int)$session->token_user_id;

        $users = UserHelper::getUsersInfo( array( $userId ) );

        $user = $users[ $userId ];

        $client = getS3Client();

        $return = array();

        if ($normal) {
            $profileImage = ( isset( $user['user_profile_image'] ) ) ? 'http://' . $_ENV[ 'URL' ] . '/' . $user['user_profile_image'] : '';
            $profileCover = ( isset( $user['user_cover_image'] ) )   ? 'http://' . $_ENV[ 'URL' ] . '/' . $user['user_cover_image'] : '';
        } else {
            $profileImage = ( isset( $user['user_profile_image'] ) ) ? $client->getObjectUrl( \Config::get('app.bucket'), $user['user_profile_image'], '+720 minutes' ) : '';
            $profileCover = ( isset( $user['user_cover_image'] ) )   ? $client->getObjectUrl( \Config::get('app.bucket'), $user['user_cover_image'], '+720 minutes' ) : '';
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

        $client = getS3Client();

        $return = array();

        $return['userId'] = $userId;

        $return['profileImage'] = isset( $user['user_profile_image'] )
            ? $client->getObjectUrl( \Config::get('app.bucket'), $user['user_profile_image'], '+720 minutes' )
            : '';

        $return['profileCover'] = isset( $user['user_cover_image'] )
            ? $client->getObjectUrl( \Config::get('app.bucket'), $user['user_cover_image'], '+720 minutes' )
            : '';

        $return['displayName'] = $user['display_name'];

        return $return;
    }


    public static function oneUser( $userId, $sessionUserId, $includeStars = false, $normal = false )
    {
        $fields = array( 'votes' );

        if ($includeStars)
            $fields[] = 'stars.users';
        else
            $fields[] = 'stars';

        $users = UserHelper::getUsersInfo( array( $userId ), $fields );

        $user = $users[ $userId ];

        $client = getS3Client();

        $profileImage = '';
        $profileCover = '';

        if ($normal) {
            $profileImage = ( isset( $user['user_profile_image'] ) ) ? 'http://' . $_ENV[ 'URL' ] . '/' . $user['user_profile_image'] : '';
            $profileCover = ( isset( $user['user_cover_image'] ) )   ? 'http://' . $_ENV[ 'URL' ] . '/' . $user['user_cover_image'] : '';
        } else {
            $profileImage = ( isset( $user['user_profile_image'] ) ) ? $client->getObjectUrl( \Config::get('app.bucket'), $user['user_profile_image'], '+720 minutes' ) : '';
            $profileCover = ( isset( $user['user_cover_image'] ) )   ? $client->getObjectUrl( \Config::get('app.bucket'), $user['user_cover_image'], '+720 minutes' ) : '';
        }


        $return = array();
        $return['id'] = $user['user_id'];
        $return['email'] = $user['user_email'];
        $return['tagLine'] = $user['user_tagline'] ? $user['user_tagline'] : '';
        $return['bio'] = $user['user_bio'] ? $user['user_bio'] :'';
        $return['usergroup'] = $user['user_user_group'] ? $user['user_user_group'] :'';
        $return['profileImage'] = $profileImage;
        $return['profileCover'] = $profileCover;

        $return[ 'userName' ] = $user['name'];
        $return[ 'displayName' ] = $user['display_name'];
        $return[ 'fullName' ] = $user['full_name'];

        if ($userId != $sessionUserId) {

            $starFlags = self::getStarFlags( $userId, $sessionUserId );

            $return[ 'isMyStar' ] = $starFlags['isMyStar'];
            $return['iAmStar'] = $starFlags['iAmStar'];
        }

        $stars = array();
        $starredBy = array();

        if ( $includeStars ) {

            $stars = self::getStars( $userId );

            $starredBy = self::getFollowers( $userId );
        }

        $return['stars'] = $stars;
        $return['starredBy'] = $starredBy;

        $return['rank'] = $user['user_rank'];
        $return['fans'] = count( $starredBy );
        $return['votes'] = $user['votes']['me']['up'];

        return $return;
    }


    public static function getStars( $userId )
    {
        $client = getS3Client();

        $users = UserHelper::getUsersInfo( array( $userId ), array( 'stars.users') );

        $user = $users[ $userId ];

        $stars = array();

        foreach( $user['stars_info']['my'] as $star_info ) {

            $star = array();
            $star['starId'] = $star_info['star_user_id'];
            $star['starName'] = $star_info['user_info']['display_name'];
            $star['starredDate'] = $star_info['star_date'];
            $star['profileImage'] = isset( $star_info['user_info']['user_profile_image'] )
                ? $client->getObjectUrl( \Config::get('app.bucket'), $star_info['user_info']['user_profile_image'], '+720 minutes' )
                : '';
            $star['profileCover'] = isset( $star_info['user_info']['user_cover_image'] )
                ? $client->getObjectUrl( \Config::get('app.bucket'), $star_info['user_info']['user_cover_image'], '+720 minutes' )
                : '';
            $star['rank'] = $star_info['user_info']['user_rank'];
            $star['stat'] = $star_info['user_info']['user_entry_rank'];

            $stars[] = $star;
            unset( $star );
        }

        return $stars;
    }


    public static function getFollowers( $userId )
    {
        $client = getS3Client();

        $users = UserHelper::getUsersInfo( array( $userId ), array( 'stars.users') );

        $user = $users[ $userId ];

        $starredBy = array();

        foreach( $user['stars_info']['me'] as $star_info ) {

            $star = array();

            $starDetails = self::userDetails( $star_info['star_user_id'] );

            $star['starId'] = $star_info['star_user_id'];
            $star['starName'] = $starDetails['displayName'];
            $star['starredDate'] = $star_info['star_date'];

            $star['profileImage'] = isset( $star_info['user_info']['user_profile_image'] )
                ? $client->getObjectUrl( \Config::get('app.bucket'), $star_info['user_info']['user_profile_image'], '+720 minutes' )
                : '';
            $star['profileCover'] = isset( $star_info['user_info']['user_cover_image'] )
                ? $client->getObjectUrl( \Config::get('app.bucket'), $star_info['user_info']['user_cover_image'], '+720 minutes' )
                : '';

            $starredBy[] = $star;
            unset( $star );
        }

        return $starredBy;
    }


    public static function getStarFlags( $userId, $sessionUserId )
    {
        $starsInfo = UserHelper::getStars( array( $userId ) );
        $starFlags = array(
            'isMyStar' => 0,
            'iAmStar' => 0,
        );
        $userStarsInfo = isset( $starsInfo[ $userId ] ) ? $starsInfo[ $userId ] : null;
        if ( empty( $userStarsInfo ) ) return $starFlags;

        $isMyStar = false;
        foreach( $userStarsInfo['me'] as $star_info ) {

            if( $sessionUserId == $star_info['star_user_id'] ) {
                $isMyStar = true;
                break;
            }
        }
        $starFlags['isMyStar'] = (int)$isMyStar;

        $iAmStar = false;
        foreach( $userStarsInfo['my'] as $star_info ) {

            if( $sessionUserId == $star_info['star_user_id'] ) {
                $iAmStar = true;
                break;
            }
        }
        $starFlags['iAmStar'] = (int)$iAmStar;

        return $starFlags;
    }


    /**
     * Returns response, when no entries found and user is provided in request.
     *
     * @param int $userId
     * @param int $sessionUserId
     * @return array
     */
    public static function entries_onlyUser( $userId, $sessionUserId )
    {
        $current = array();
        $current[ 'id' ] = null;
        $current[ 'user' ] = ResponseHelper::oneUser( $userId, $sessionUserId );

        $starFlags = ResponseHelper::getStarFlags( $userId, $sessionUserId );
        $current['user']['isMyStar'] = $starFlags['isMyStar'];
        $current['user']['iAmStar'] = $starFlags['iAmStar'];
        $current[ 'category' ] = null;
        $current[ 'type' ] = null;
        $current[ 'name' ] = null;
        $current[ 'description' ] = null;
        $current[ 'created' ] = null;
        $current[ 'modified' ] = null;

        $data = array();
        $data[ 'entries' ][ ][ 'entry' ] = $current;

        $starredBy = ResponseHelper::getFollowers( $userId );
        $data[ 'starredBy' ] = $starredBy;
        $data['fans'] = count($starredBy);
        $status_code = 200;

        return array( 'code' => $status_code, 'data' => $data );
    }


    /**
     * Returns response when no entries found.
     *
     * @return array
     */
    public static function entries_noEntries()
    {
        $code = 404;
        $data = array( 'error' => 'No Entries Found' );

        return array( 'code' => $code, 'data' => $data );
    }
}
