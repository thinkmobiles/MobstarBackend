<?php

namespace MobStar;

class ResponseHelper
{

    private static $starsInfo = array();


    public static function getUserProfile( array& $users, $session, $normal = false )
    {
        $userId = (int)$session->token_user_id;

        if( ! isset( $users[ $userId ] ) ) {
            error_log( 'no user prepared in ResponseHelper::getUserProfile. User id: '.$userId );
            $usersInfo = UserHelper::getUsersInfo( array( $userId ) );
            $users[ $userId ] = $usersInfo[ $userId ];
        }

        $user = &$users[ $userId ];

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


    public static function userDetails( array& $users, $userId )
    {
        if ( ! isset( $users[ $userId ] ) ) {
            error_log( 'no user prepared in ResponseHelper::userDetails. User id: '.$userId );
            $usersInfo = UserHelper::getUsersInfo( array( $userId ) );
            $users[ $userId ] = $usersInfo[ $userId ];
        }

        $user = &$users[ $userId ];

        $return = array();

        $return[ 'userName' ] = $user['user_name'];
        $return[ 'displayName' ] = $user['user_display_name'];
        $return[ 'fullName' ] = $user['user_full_name'];

        return $return;
    }


    public static function getusernamebyid( array& $users, $userId )
    {
        if ( ! isset( $users[ $userId ] ) ) {
            error_log( 'no user prepared in ResponseHelper::getusernamebyid. User id: '.$userId );
            $usersInfo = UserHelper::getUsersInfo( array( $userId ) );
            $users[ $userId ] = $usersInfo[ $userId ];
        }

        $user = &$users[ $userId ];

        return isset( $user['user_display_name'] ) ? $user['user_display_name'] : 'Guest';
    }


    public static function particUser( array& $users, $userId, $session, $includeStars = false )
    {
        if ( ! isset( $users[ $userId ] ) ) {
            error_log( 'no user prepared in ResponseHelper::particUser. User id: '.$userId );
            $usersInfo = UserHelper::getUsersInfo( array( $userId ) );
            $users[ $userId ] = $usersInfo[ $userId ];
        }

        $user = &$users[ $userId ];

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


    public static function oneUser( array& $users, $userId, $session, $includeStars, $normal )
    {
        if ( ! isset( $users[ $userId ] ) ) {
            error_log( 'no user prepared in ResponseHelper::oneUser. User id: '.$userId );
            $usersInfo = UserHelper::getUsersInfo( array( $userId ) );
            $users[ $userId ] = $usersInfo[ $userId ];
        }

        $users = UserHelper::addVotes( $users );
        if ($includeStars)
            $users = UserHelper::addStarNames( $users );
        else
            $users = UserHelper::addStars( $users );

        $user = &$users[ $userId ];

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

            foreach( $user['stars_info']['me'] as $star_info ) {

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

                $starredBy[] = $star;
                unset( $star );
            }
        }

        $return['stars'] = $stars;
        $return['starredBy'] = $starredBy;

        $return['rank'] = $user['user_rank'];
        $return['fans'] = count( $starredBy );
        $return['votes'] = $user['votes']['up'];

        return $return;
    }
}
