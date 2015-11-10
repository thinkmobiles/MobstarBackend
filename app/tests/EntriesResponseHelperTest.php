<?php

use MobStar\ResponseHelper;
use MobStar\EntriesResponseHelper;


class EntriesResponseHelperTest extends TestCase
{

    private static $data_dir = '/data/old_version';


    public function testOnlyUser()
    {
        $dataFile = __DIR__ . self::$data_dir.'/EntryController_index_noEntries.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

        $token = new \MobStar\Storage\Token\EloquentTokenRepository();

        $preparator = new DataPreparator();

        foreach( $testData as $test ) {

            $userId = $test['userId'];
            $session = $token->get_session( $test['token'] );
            $sessionUserId = $session->token_user_id;


            $response = EntriesResponseHelper::onlyUser( $userId, $sessionUserId );

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


    public function testGetOneEntry()
    {
        $dataFile = __DIR__ . self::$data_dir.'/EntryController_index_category6.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

        $token = new \MobStar\Storage\Token\EloquentTokenRepository();

        $preparator = new DataPreparator();
        $preparator->addUnsetKeys( 'isVotedByYou' );

        foreach( $testData as $test ) {

            $session = $token->get_session( $test['token'] );
            $sessionUserId = $session->token_user_id;
            $showFeedback = false;

            foreach( $test['data']->entries as $entryObj ) {
                $entryObj = $entryObj->entry;
                $entry = \Entry::findOrFail( $entryObj->id );

                $response = EntriesResponseHelper::getOneEntry( $entry, $sessionUserId, $showFeedback );
                $content = json_decode( json_encode( $response ) );

                $entryObj = $preparator->getPreparedData( $entryObj );
                $content = $preparator->getPreparedData( $content );

                $this->assertEquals( $entryObj, $content );
            }
        }
    }
}
