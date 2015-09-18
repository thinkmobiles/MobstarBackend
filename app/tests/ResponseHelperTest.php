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

        UserHelper::clear();

        UserHelper::prepareUsers( $userIds );

        foreach( $testData as $data ) {
            $session = $token->get_session( $data['token'] );
            $normal = $data['normal'];

            $userProfile = ResponseHelper::getUserProfile( $session, $normal );

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

        UserHelper::clear();

        UserHelper::prepareUsers( $userIds );

        foreach( $testData as $data ) {
            $userDetails = ResponseHelper::userDetails( $data['userId'] );

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

        UserHelper::clear();

        UserHelper::prepareUsers( $userIds );

        foreach( $testData as $data ) {
            $userNames = ResponseHelper::getusernamebyid( $data['userId'] );

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

        UserHelper::clear();

        UserHelper::prepareUsers( $userIds );


        foreach( $testData as $data ) {

            $userId = $data['userId'];
            $session = $token->get_session( $data['token'] );
            $includeStars = $data['includeStars'];

            $particUser = ResponseHelper::particUser( $userId, $session, $includeStars );

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

        UserHelper::clear();

        UserHelper::prepareUsers( $userIds );


        foreach( $testData as $data ) {


            $userId = $data['userId'];
            if( $userId == 440 ) continue;
            $session = $token->get_session( $data['token'] );
            $includeStars = $data['includeStars'];
            $normal = $data['normal'];

            $oneUser = ResponseHelper::oneUser( $userId, $session->token_user_id, $includeStars, $normal );

            // adjust AWS urls
            $keys = array( 'profileImage', 'profileCover' );

            $this->adjustAWSUrlInArray( $data['data'], $keys );
            $this->adjustAWSUrlInArray( $oneUser, $keys );

            $this->assertEquals( $data['data'], $oneUser );
        }
    }


    public function testIndex_noEntries()
    {
        $dataFile = __DIR__ . self::$data_dir.'/EntryController_index_noEntries.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

        $token = new \MobStar\Storage\Token\EloquentTokenRepository();

        foreach( $testData as $test ) {

            $userId = $test['userId'];
            $session = $token->get_session( $test['token'] );
            $sessionUserId = $session->token_user_id;


            $response = ResponseHelper::entries_onlyUser( $userId, $sessionUserId );

            $this->assertEquals( $test['statusCode'], $response['code'] );

            $content = json_decode( json_encode( $response['data'] ) );

            $keys = array( 'profileImage', 'profileCover' );

            $this->adjustAWSUrlInArray( $test['data'], $keys );
            $this->adjustAWSUrlInArray( $content, $keys );

            $this->assertEquals(
                $test['data'],
                $content
            );
        }
    }


    private function adjustAWSUrlInArray( &$data, $keys )
    {
        if ( is_array( $data ) ) {
            foreach( $keys as $key ) {
                if( isset( $data[$key] ) ) $data[ $key ] = $this->adjustAWSUrl( $data[ $key ] );
            }
            foreach( $data as &$field ) {
                if( is_array( $field ) OR is_object( $field ) )
                    $this->adjustAWSUrlInArray( $field, $keys );
            }
            unset( $field );
        }
        if( is_object( $data ) ) {
            foreach( $keys as $key ) {
                if( isset( $data->$key ) ) $data->$key = $this->adjustAWSUrl( $data->$key );
            }
            foreach( $data as &$field ) {
                if( is_array( $field ) OR is_object( $field ) )
                    $this->adjustAWSUrlInArray( $field, $keys );
            }
            unset( $field );
        }
    }


    private function adjustAWSUrl( $url )
    {
        // remove all after 'Expires'. Otherwise comperison will fail  due to different sufixes added by AWS client

        if( empty( $url ) ) return $url;

        $index = strpos( $url, 'Expires' );
        if( $index === false ) return $url;

        return substr( $url, 0, $index );
    }
}
