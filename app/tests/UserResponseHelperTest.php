<?php

use MobStar\UserResponseHelper;
use MobStar\UserHelper;

class UserResponseHelperTest extends TestCase
{
    private static $data_dir = '/data/old_version';


    public function testGetUserInfo()
    {
        $dataFile = __DIR__ . self::$data_dir.'/UserController_show.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

        $token = new \MobStar\Storage\Token\EloquentTokenRepository();

        foreach( $testData as $test ) {

            $userId = $test['userId'];
            $session = $token->get_session( $test['token'] );
            $sessionUserId = $session->token_user_id;

            $response = UserResponseHelper::getUserInfo( $userId, $sessionUserId );

            $this->assertEquals( $test['statusCode'], $response['code'] );

            $content = json_decode( json_encode( $response['data'] ) );

            unset( $test['data']->users[0]->user->stars );
            unset( $content->users[0]->user->stars );

            unset( $test['data']->users[0]->user->starredBy );
            unset( $content->users[0]->user->starredBy );

            $keys = array( 'profileImage', 'profileCover' );

            $this->adjustAWSUrlInArray( $test['data'], $keys );
            $this->adjustAWSUrlInArray( $content, $keys );

            $this->assertEquals(
                $test['data'],
                $content
            );
        }
    }


    public function testGetUserInfoWithStars()
    {
        $dataFile = __DIR__ . self::$data_dir.'/UserController_show.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

        $token = new \MobStar\Storage\Token\EloquentTokenRepository();

        foreach( $testData as $test ) {

            $userId = $test['userId'];
            $session = $token->get_session( $test['token'] );
            $sessionUserId = $session->token_user_id;

            $response = UserResponseHelper::getUserInfoWithStars( $userId, $sessionUserId );

            $this->assertEquals( $test['statusCode'], $response['code'] );

            $content = json_decode( json_encode( $response['data'] ) );

            unset( $test['data']->users[0]->user->starredBy );
            unset( $content->users[0]->user->starredBy );

            $keys = array( 'profileImage', 'profileCover' );

            $this->adjustAWSUrlInArray( $test['data'], $keys );
            $this->adjustAWSUrlInArray( $content, $keys );

            $this->assertEquals(
                $test['data'],
                $content
            );
        }
    }


    public function testGetUserInfoWithStarredBy()
    {
        $dataFile = __DIR__ . self::$data_dir.'/UserController_show.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

        $token = new \MobStar\Storage\Token\EloquentTokenRepository();

        foreach( $testData as $test ) {


            $userId = $test['userId'];
            $session = $token->get_session( $test['token'] );
            $sessionUserId = $session->token_user_id;

            $response = UserResponseHelper::getUserInfoWithStarredBy( $userId, $sessionUserId );

            $this->assertEquals( $test['statusCode'], $response['code'] );

            $content = json_decode( json_encode( $response['data'] ) );

            unset( $test['data']->users[0]->user->stars );
            unset( $content->users[0]->user->stars );

            $keys = array( 'profileImage', 'profileCover' );

            $this->adjustAWSUrlInArray( $test['data'], $keys );
            $this->adjustAWSUrlInArray( $content, $keys );

            $this->assertEquals(
                $test['data'],
                $content
            );
        }
    }


    public function testShowUser()
    {
        $dataFile = __DIR__ . self::$data_dir.'/UserController_show.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

        $token = new \MobStar\Storage\Token\EloquentTokenRepository();

        foreach( $testData as $test ) {


            $userId = $test['userId'];
            $session = $token->get_session( $test['token'] );
            $sessionUserId = $session->token_user_id;

            $response = UserResponseHelper::showUser( $userId, $sessionUserId );

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
