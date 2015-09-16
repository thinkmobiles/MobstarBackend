<?php

namespace MobStar;

class ResponseHelper
{

    private static $starsInfo = array();


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

        if ( ( empty( $user['user_display_name'] ) ) && $session->token_type != 'Native' ) {

            $return[ 'userDisplayName' ] = $user['user_display_name'];
            $return[ 'userName' ] = $user['user_name'];
            $return[ 'fullName' ] = $user['user_full_name'];

        } elseif ( ( !empty( $user['user_display_name'] ) ) && $session->token_type != 'Native' ) {

            $return[ 'userDisplayName' ] = $user['user_display_name'];
            $return[ 'userName' ] = $user['user_name'];
            $return[ 'fullName' ] = $user['user_full_name'];

        } else {

            $return[ 'userDisplayName' ] = $user['user_display_name'];
            $return[ 'userName' ] = $user['user_name'];
        }

        $return[ 'userTagline' ] = isset( $user['user_tagline'] ) ?  $user['user_tagline'] : '';
        $return[ 'userBio' ] = isset( $user['user_bio'] ) ?  $user['user_bio'] : '';

        return $return;
    }


    public static function userDetails( $userId )
    {
        $users = UserHelper::getUsersInfo( array( $userId ) );

        $user = $users[ $userId ];

        $return = array();

        $return[ 'userName' ] = $user['user_name'];
        $return[ 'displayName' ] = $user['user_display_name'];
        $return[ 'fullName' ] = $user['user_full_name'];

        return $return;
    }


    public static function getusernamebyid( $userId )
    {
        $users = UserHelper::getUsersInfo( array( $userId ) );

        $user = $users[ $userId ];

        return isset( $user['user_display_name'] ) ? $user['user_display_name'] : 'Guest';
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

        $return['displayName'] = $user['user_display_name'];

        return $return;
    }


    public static function oneUser( $userId, $session, $includeStars, $normal )
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

        $return[ 'userName' ] = $user['user_name'];
        $return[ 'displayName' ] = $user['user_display_name'];
        $return[ 'fullName' ] = $user['user_full_name'];

        if ($userId != $session->token_user_id) {

            $isMyStar = false;
            foreach( $user['stars_info']['me'] as $star_info ) {

                if( $session->token_user_id == $star_info['star_user_id'] ) {
                    $isMyStar = true;
                    break;
                }
            }
            $return[ 'isMyStar' ] = (int)$isMyStar;

            $iAmStar = false;
            foreach( $user['stars_info']['my'] as $star_info ) {

                if( $session->token_user_id == $star_info['star_user_id'] ) {
                    $iAmStar = true;
                    break;
                }
            }
            $return['iAmStar'] = (int)$iAmStar;
        }

        $stars = array();
        $starredBy = array();

        if ( $includeStars ) {

            foreach( $user['stars_info']['my'] as $star_info ) {

                $star = array();
                $star['starId'] = $star_info['star_user_id'];
                $star['starName'] = $star_info['user_info']['user_display_name'];
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

            $followersInfo = self::getFollowers( $userId );
            $starredBy = $followersInfo['starredBy'];
            unset( $followersInfo );

//             foreach( $user['stars_info']['me'] as $star_info ) {

//                 $star = array();
//                 $star['starId'] = $star_info['star_user_id'];
//                 $star['starName'] = $star_info['user_info']['user_display_name'];
//                 $star['starredDate'] = $star_info['star_date'];
//                 $star['profileImage'] = isset( $star_info['user_info']['user_profile_image'] )
//                     ? $client->getObjectUrl( \Config::get('app.bucket'), $star_info['user_info']['user_profile_image'], '+720 minutes' )
//                     : '';
//                 $star['profileCover'] = isset( $star_info['user_info']['user_cover_image'] )
//                     ? $client->getObjectUrl( \Config::get('app.bucket'), $star_info['user_info']['user_cover_image'], '+720 minutes' )
//                     : '';

//                 $starredBy[] = $star;
//                 unset( $star );
//             }
        }

        $return['stars'] = $stars;
        $return['starredBy'] = $starredBy;

        $return['rank'] = $user['user_rank'];
        $return['fans'] = count( $starredBy );
        $return['votes'] = $user['votes']['up'];

        return $return;
    }


    public static function getFollowers( $userId, array& $data = array() )
    {
        $client = getS3Client();

        $users = UserHelper::getUsersInfo( array( $userId ), array( 'stars.users') );

        $user = $users[ $userId ];

        $starredBy = array();

        foreach( $user['stars_info']['me'] as $star_info ) {

            $star = array();

            $star['starId'] = $star_info['star_user_id'];
            $star['starName'] = $star_info['user_info']['user_display_name']; // @todo fix it
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

        $data['starredBy'] = $starredBy;
        $data['fans'] = count( $starredBy );

        return $data;
    }
}
