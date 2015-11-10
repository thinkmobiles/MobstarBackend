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

        $preparator = new DataPreparator(
            array('stars', 'starredBy'), // unset keys
            array('profileImage', 'profileCover') // AWS keys
        );

        foreach( $testData as $test ) {

            $userId = $test['userId'];
            $session = $token->get_session( $test['token'] );
            $sessionUserId = $session->token_user_id;

            $response = UserResponseHelper::getUserInfo( $userId, $sessionUserId );

            $this->assertEquals( $test['statusCode'], $response['code'] );

            $content = json_decode( json_encode( $response['data'] ) );

            $test['data'] = $preparator->getPreparedData( $test['data'] );
            $content = $preparator->getPreparedData( $content );

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

        $testDataPreparator = new DataPreparator(
            array('starredBy'),
            array('profileImage', 'profileCover')
        );
        $contentPreparator = new DataPreparator(
            array(),
            array('profileImage', 'profileCover')
        );

        foreach( $testData as $test ) {

            $userId = $test['userId'];
            $session = $token->get_session( $test['token'] );
            $sessionUserId = $session->token_user_id;

            $response = UserResponseHelper::getUserInfoWithStars( $userId, $sessionUserId );

            $this->assertEquals( $test['statusCode'], $response['code'] );

            $content = json_decode( json_encode( $response['data'] ) );

            $content = $contentPreparator->getPreparedData( $content );
            $test['data'] = $testDataPreparator->getPreparedData( $test['data'] );

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

        $testDataPreparator = new DataPreparator(
            array('stars'),
            array('profileImage', 'profileCover')
        );
        $contentPreparator = new DataPreparator(
            array(),
            array('profileImage', 'profileCover')
        );
        foreach( $testData as $test ) {


            $userId = $test['userId'];
            $session = $token->get_session( $test['token'] );
            $sessionUserId = $session->token_user_id;

            $response = UserResponseHelper::getUserInfoWithStarredBy( $userId, $sessionUserId );

            $this->assertEquals( $test['statusCode'], $response['code'] );

            $content = json_decode( json_encode( $response['data'] ) );

            $test['data'] = $testDataPreparator->getPreparedData( $test['data'] );
            $content = $contentPreparator->getPreparedData( $content );

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

        $preparator = new DataPreparator();

        foreach( $testData as $test ) {


            $userId = $test['userId'];
            $session = $token->get_session( $test['token'] );
            $sessionUserId = $session->token_user_id;

            $response = UserResponseHelper::showUsers( array( $userId ), $sessionUserId );

            $this->assertEquals( $test['statusCode'], $response['code'] );

            $content = json_decode( json_encode( $response['data'] ) );

            $test['data'] = $preparator->getPreparedData( $test['data'] );
            $content = $preparator->getPreparedData( $content );

            $this->assertEquals(
                $test['data'],
                $content
            );
        }
    }
}
