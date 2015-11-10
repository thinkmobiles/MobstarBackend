<?php

use MobStar\UserHelper;
use MobStar\EntryHelper;
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

        $preparator = new DataPreparator();

        foreach( $testData as $data ) {
            $session = $token->get_session( $data['token'] );
            $normal = $data['normal'];

            $userProfile = ResponseHelper::getUserProfile( $session, $normal );

            $data['data'] = $preparator->getPreparedData( $data['data'] );
            $userProfile = $preparator->getPreparedData( $userProfile );

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

        $preparator = new DataPreparator();

        foreach( $testData as $data ) {

            $userId = $data['userId'];
            $session = $token->get_session( $data['token'] );
            $includeStars = $data['includeStars'];

            $particUser = ResponseHelper::particUser( $userId, $session, $includeStars );

            $data['data'] = $preparator->getPreparedData( $data['data'] );
            $particUser = $preparator->getPreparedData( $particUser );

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

        $preparator = new DataPreparator();

        foreach( $testData as $data ) {


            $userId = $data['userId'];
            $session = $token->get_session( $data['token'] );
            $includeStars = $data['includeStars'];
            $normal = $data['normal'];

            $oneUser = ResponseHelper::oneUser( $userId, $session->token_user_id, $includeStars, $normal );

            $data['data'] = $preparator->getPreparedData( $data['data'] );
            $oneUser = $preparator->getPreparedData( $oneUser );

            $this->assertEquals( $data['data'], $oneUser );
        }
    }


    public function testOneUser_StarsCountsOnly()
    {
        $dataFile = __DIR__ . self::$data_dir.'/oneUser.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

        $token = new \MobStar\Storage\Token\EloquentTokenRepository();

        // get users
        $userIds = array();
        foreach( $testData as $data ) $userIds[] = $data['userId'];

        UserHelper::clear();

        UserHelper::prepareUsers( $userIds );

        $preparator = new DataPreparator();

        foreach( $testData as $data ) {


            $userId = $data['userId'];
            $session = $token->get_session( $data['token'] );
            $includeStars = $data['includeStars'];

            if( ! $includeStars ) continue; // no need (incorect results)

            $oneUser = ResponseHelper::oneUser_StarsCountsOnly( $userId, $session->token_user_id );

            //add stars/starredby counts
            $data['data']['starsCount'] = count( $data['data']['stars'] );
            $data['data']['starredByCount'] = count( $data['data']['starredBy'] );

            // remove stars/starredby fields
            unset( $data['data']['stars'] );
            unset( $data['data']['starredBy'] );

            $data['data'] = $preparator->getPreparedData( $data['data'] );
            $oneUser = $preparator->getPreparedData( $oneUser );

            $this->assertEquals( $data['data'], $oneUser );
        }
    }


    public function testOneEntry()
    {
        $dataFile = __DIR__ . self::$data_dir.'/oneEntry.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

        $token = new \MobStar\Storage\Token\EloquentTokenRepository();

        // get users
        $userIds = array();
        foreach( $testData as $data ) $userIds[] = $data['userId'];

        UserHelper::clear();

        UserHelper::prepareUsers( $userIds );

        $preparator = new DataPreparator();
        $preparator->addUnsetKeys( array( 'isVotedByYou', 'subcategory', 'age', 'height' ) );

        foreach( $testData as $data ) {


            $entryId = $data['entryId'];
            $session = $token->get_session( $data['token'] );
            $includeUser = $data['includeUser'];

            $entry = \Entry::findOrFail( $entryId );

            $oneEntry = ResponseHelper::oneEntry( $entry, $session->token_user_id, $includeUser );

            $data['data'] = $preparator->getPreparedData( $data['data'] );
            $oneEntry = $preparator->getPreparedData( $oneEntry );

            $this->assertEquals( $data['data'], $oneEntry );
        }
    }


    public function testOneEntryById()
    {
        $dataFile = __DIR__ . self::$data_dir.'/oneEntry.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

        $token = new \MobStar\Storage\Token\EloquentTokenRepository();

        // get users
        $userIds = array();
        foreach( $testData as $data ) $userIds[] = $data['userId'];

        UserHelper::clear();

        UserHelper::prepareUsers( $userIds );

        // get entries
        $entryIds = array();
        foreach( $testData as $data ) $entryIds[] = $data['entryId'];

        EntryHelper::clear();
        EntryHelper::prepareEntries( $entryIds, array('commentCounts', 'filesInfo', 'tagNames', 'totalVotes', 'votedByUser') );

        $preparator = new DataPreparator();
        $preparator->addUnsetKeys( array( 'isVotedByYou', 'subcategory', 'age', 'height' ) );

        foreach( $testData as $data ) {

            $entryId = $data['entryId'];
            $session = $token->get_session( $data['token'] );
            $includeUser = $data['includeUser'];

            $oneEntry = ResponseHelper::oneEntryById( $entryId, $session->token_user_id, $includeUser );

            $data['data'] = $preparator->getPreparedData( $data['data'] );
            $oneEntry = $preparator->getPreparedData( $oneEntry );

            $this->assertEquals( $data['data'], $oneEntry );
        }
    }
}
