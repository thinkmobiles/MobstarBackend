<?php

namespace MobStar;

use MobStar\UserHelper;
use MobStar\Pager;

class UserResponseHelper extends ResponseHelper
{

    public static function getUserInfo( $userId, $sessionUserId )
    {
        // prepare users info
        UserHelper::prepareUsers( array( $userId ), array( 'votes', 'stars' ) );

        $userProfile = self::userProfile( $userId, $sessionUserId );

        if( ! $userProfile ) {
            return array(
                'code' => 404,
                'data' => 'user not found'
            );
        }

        $code = 200;
        $data = array();
        $data['users'][]['user'] = $userProfile;

        return array( 'code' => $code, 'data' => $data );
    }


    public static function getUserInfoWithStars( $userId, $sessionUserId, Pager $pager = null )
    {
        // prepare users info
        UserHelper::prepareUsers( array( $userId ), array( 'votes', 'stars' ) );

        $userProfile = self::userProfile( $userId, $sessionUserId );

        if( ! $userProfile ) {
            return array(
                'code' => 404,
                'data' => 'user not found'
            );
        }

        $limit = $offset = 0;

        if( ! $pager ) {
            $pager = new Pager( '', 0 );
        }
        $limit = $pager->getLimit();
        $offset = $pager->getOffset();

        $starsInfo = UserHelper::getUserStarsInfoByPage( $userId, 'my', $limit, $offset );

        $starsInfo = $pager->getAdjustedToPage( $starsInfo );

        $starUserIds = array();

        foreach( $starsInfo as $starInfo ) {
            $starUserIds[] = $starInfo['star_user_id'];
        }

        UserHelper::prepareUsers( $starUserIds );

        $stars = array();
        $starsProcessed = array();

        foreach( $starsInfo as $starInfo )
        {
            if( isset( $starsProcessed[ $starInfo['star_user_id'] ] ) ) {
                continue;
            }
            $starsProcessed[ $starInfo['star_user_id'] ] = true;
            $stars[] = self::getStar( $starInfo );
        }

        $userProfile['stars'] = $stars;

        $code = 200;
        $data = array();
        $data['users'][]['user'] = $userProfile;

        if( $pager->needPrevLink() ) {
            $data['previous'] = $pager->getPrevLink();
        }
        if( $pager->needNextLink() ) {
            $data['next'] = $pager->getNextLink();
        }

        return array( 'code' => $code, 'data' => $data );
    }


    public static function getUserInfoWithStarredBy( $userId, $sessionUserId, Pager $pager = null )
    {
        // prepare users info
        UserHelper::prepareUsers( array( $userId ), array( 'votes', 'stars' ) );

        $userProfile = self::userProfile( $userId, $sessionUserId );

        if( ! $userProfile ) {
            return array(
                'code' => 404,
                'data' => 'user not found'
            );
        }

        if( ! $pager ) {
            $pager = new Pager( '', 0 );
        }

        $limit = $pager->getLimit();
        $offset = $pager->getOffset();

        $starsInfo = UserHelper::getUserStarsInfoByPage( $userId, 'me', $limit, $offset );

        $starsInfo = $pager->getAdjustedToPage( $starsInfo );

        $starUserIds = array();

        foreach( $starsInfo as $starInfo ) {
            $starUserIds[] = $starInfo['star_user_id'];
        }

        UserHelper::prepareUsers( $starUserIds );

        $stars = array();
        $starsProcessed = array();

        foreach( $starsInfo as $starInfo )
        {
            // @todo we need to make it right (skip dublicated starred by users)
            // commented out to emulate old behaviour
//             if( isset( $starsProcessed[ $starInfo['star_user_id'] ] ) ) {
//                 continue;
//             }
            $starsProcessed[ $starInfo['star_user_id'] ] = true;
            $stars[] = self::getFollower( $starInfo );
        }

        $userProfile['starredBy'] = $stars;

        $code = 200;
        $data = array();
        $data['users'][]['user'] = $userProfile;

        if( $pager->needPrevLink() ) {
            $data['previous'] = $pager->getPrevLink();
        }
        if( $pager->needNextLink() ) {
            $data['next'] = $pager->getNextLink();
        }

        return array( 'code' => $code, 'data' => $data );
    }
}
