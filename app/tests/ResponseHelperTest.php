<?php

use MobStar\UserHelper;
use MobStar\ResponseHelper;

class ResponseHelperTest extends TestCase{


    private static $data_dir = '/data/old_version';


    public function testGetUserProfile()
    {
        $dataFile = __DIR__ . self::$data_dir.'/getUserProfile.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

        $token = new \MobStar\Storage\Token\EloquentTokenRepository();

        //get users
        $userIds = array();
        foreach( $testData as $data ) $userIds[] = $data['userId'];

        $users = UserHelper::getUsersInfo( $userIds );

        foreach( $testData as $data ) {
            $session = $token->get_session( $data['token'] );
            $normal = $data['normal'];

            $userProfile = ResponseHelper::getUserProfile( $users, $session, $normal );

            // adjust AWS urls
            $data['data']['profileImage'] = self::adjustAWSUrl( $data['data']['profileImage'] );
            $data['data']['profileCover'] = self::adjustAWSUrl( $data['data']['profileCover'] );

            $userProfile['profileImage'] = self::adjustAWSUrl( $userProfile['profileImage'] );
            $userProfile['profileCover'] = self::adjustAWSUrl( $userProfile['profileCover'] );

            $this->assertEquals( $data['data'], $userProfile );
        }
    }


    public function testUserDetails()
    {
        $dataFile = __DIR__ . self::$data_dir.'/userDetails.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

        // get users
        $userIds = array();
        foreach( $testData as $data ) $userIds[] = $data['userId'];

        $users = UserHelper::getUsersInfo( $userIds );

        foreach( $testData as $data ) {
            $userDetails = ResponseHelper::userDetails( $users, $data['userId'] );

            $this->assertEquals( $data['data'], $userDetails );
        }
    }


    public function testGetusernamebyid()
    {
        $dataFile = __DIR__ . self::$data_dir.'/getusernamebyid.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

        // get users
        $userIds = array();
        foreach( $testData as $data ) $userIds[] = $data['userId'];

        $users = UserHelper::getUsersInfo( $userIds );

        foreach( $testData as $data ) {
            $userNames = ResponseHelper::getusernamebyid( $users, $data['userId'] );

            $this->assertEquals( $data['data'], $userNames );
        }
    }


    public function testParticUser()
    {
        $dataFile = __DIR__ . self::$data_dir.'/particUser.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

        $token = new \MobStar\Storage\Token\EloquentTokenRepository();

        // get users
        $userIds = array();
        foreach( $testData as $data ) $userIds[] = $data['userId'];

        $users = UserHelper::getUsersInfo( $userIds );

        foreach( $testData as $data ) {

            $userId = $data['userId'];
            $session = $token->get_session( $data['token'] );
            $includeStars = $data['includeStars'];

            $particUser = ResponseHelper::particUser( $users, $userId, $session, $includeStars );

            // adjust AWS urls
            $data['data']['profileImage'] = self::adjustAWSUrl( $data['data']['profileImage'] );
            $data['data']['profileCover'] = self::adjustAWSUrl( $data['data']['profileCover'] );

            $particUser['profileImage'] = self::adjustAWSUrl( $particUser['profileImage'] );
            $particUser['profileCover'] = self::adjustAWSUrl( $particUser['profileCover'] );

            $this->assertEquals( $data['data'], $particUser );
        }
    }


    public function testOneUser()
    {
        $dataFile = __DIR__ . self::$data_dir.'/oneUser.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

        $token = new \MobStar\Storage\Token\EloquentTokenRepository();

        // get users
        $userIds = array();
        foreach( $testData as $data ) $userIds[] = $data['userId'];

        $users = UserHelper::getUsersInfo( $userIds );

        foreach( $testData as $data ) {

            $userId = $data['userId'];
            $session = $token->get_session( $data['token'] );
            $includeStars = $data['includeStars'];
            $normal = $data['normal'];

            $oneUser = ResponseHelper::oneUser( $users, $userId, $session, $includeStars, $normal );

            // adjust AWS urls
            $data['data']['profileImage'] = self::adjustAWSUrl( $data['data']['profileImage'] );
            $data['data']['profileCover'] = self::adjustAWSUrl( $data['data']['profileCover'] );
            foreach( $data['data']['stars'] as &$starInfo ) {
                $starInfo['profileImage'] = self::adjustAWSUrl( $starInfo['profileImage'] );
                $starInfo['profileCover'] = self::adjustAWSUrl( $starInfo['profileCover'] );
            }
            unset( $starInfo );
            foreach( $data['data']['starredBy'] as &$starInfo ) {
                $starInfo['profileImage'] = self::adjustAWSUrl( $starInfo['profileImage'] );
                $starInfo['profileCover'] = self::adjustAWSUrl( $starInfo['profileCover'] );
            }
            unset( $starInfo );

            $oneUser['profileImage'] = self::adjustAWSUrl( $oneUser['profileImage'] );
            $oneUser['profileCover'] = self::adjustAWSUrl( $oneUser['profileCover'] );
            foreach( $oneUser['stars'] as &$starInfo ) {
                $starInfo['profileImage'] = self::adjustAWSUrl( $starInfo['profileImage'] );
                $starInfo['profileCover'] = self::adjustAWSUrl( $starInfo['profileCover'] );
            }
            unset( $starInfo );
            foreach( $oneUser['starredBy'] as &$starInfo ) {
                $starInfo['profileImage'] = self::adjustAWSUrl( $starInfo['profileImage'] );
                $starInfo['profileCover'] = self::adjustAWSUrl( $starInfo['profileCover'] );
            }
            unset( $starInfo );

            //resort stars (some of them has same timestamp)
            usort( $data['data']['stars'], function( $par1, $par2 ) {
                if ( (int)$par1['starId'] == (int)$par2['starId'] ) return 0;
                return (int)$par1['starId'] > (int)$par2['starId'] ? 1 : -1;
            });
            usort( $data['data']['starredBy'], function( $par1, $par2 ) {
                if ( (int)$par1['starId'] == (int)$par2['starId'] ) return 0;
                return (int)$par1['starId'] > (int)$par2['starId'] ? 1 : -1;
            });
            usort( $oneUser['stars'], function( $par1, $par2 ) {
                if ( (int)$par1['starId'] == (int)$par2['starId'] ) return 0;
                return (int)$par1['starId'] > (int)$par2['starId'] ? 1 : -1;
            });
            usort( $oneUser['starredBy'], function( $par1, $par2 ) {
                if ( (int)$par1['starId'] == (int)$par2['starId'] ) return 0;
                return (int)$par1['starId'] > (int)$par2['starId'] ? 1 : -1;
            });

            if ( $userId == 462 ) {
                // unset starredBY 3198 user. He has own user_display_name, but old version gets it from facebook anyway.
                foreach( $data['data']['starredBy'] as $index => $starInfo )
                    if( $starInfo['starId'] == 3198 ) unset( $data['data']['starredBy'][$index] );

                foreach( $oneUser['starredBy'] as $index => $starInfo )
                    if( $starInfo['starId'] == 3198 ) unset( $oneUser['starredBy'][$index] );
            }

            $this->assertEquals( $data['data']['starredBy'], $oneUser['starredBy'] );
        }
    }


    private static function adjustAWSUrl( $url )
    {
        // remove all after 'Expires'. Otherwise comperison will fail  due to different sufixes added by AWS client

        if( empty( $url ) ) return $url;

        $index = strpos( $url, 'Expires' );
        if( $index === false ) return $url;

        return substr( $url, 0, $index );
    }
}
