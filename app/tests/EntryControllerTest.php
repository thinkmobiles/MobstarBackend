<?php

require_once __DIR__.'/ResponseTestCase.php';

use MobStar\UserHelper;
use MobStar\EntryHelper;


class EntryControllerTest extends ResponseTestCase{

    private static $data_dir = '/data/old_version';


    protected function getResponse( $token, $method, $url, $pars = array() )
    {
        $response = $this->call( $method, $url, $pars, array(), array( 'HTTP_X-API-TOKEN' => $token ) );

        return $response;
    }


    public function testIndex_noEntries()
    {
        $dataFile = __DIR__ . self::$data_dir.'/EntryController_index_noEntries.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

        $preparator = new DataPreparator();
        $preparator->addUnsetKeys( array( 'isVotedByYou', 'timestamp' ) );

        foreach( $testData as $test ) {

            $response = $this->getResponse(
                $test['token'],
                'GET',
                '/entry',
                array(
                    'user' => $test['userId'],
                    'category' => $test['categoryId'],
                )
            );

            $this->assertEquals( $test['statusCode'], $response->getStatusCode() );

            $content = json_decode( $response->getContent() );

            $content = $preparator->getPreparedData( $content );
            $test['data'] = $preparator->getPreparedData( $test['data'] );

            $this->assertEquals(
                $test['data'],
                $content
            );
        }
    }


    public function testIndex_userCategory()
    {
        $dataFile = __DIR__ . self::$data_dir.'/EntryController_index_userCategory.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

        $preparator = new DataPreparator();
        $preparator->addUnsetKeys( array( 'isVotedByYou', 'timestamp' ) );

        foreach( $testData as $test ) {

            $response = $this->getResponse(
                $test['token'],
                'GET',
                '/entry',
                array(
                    'user' => $test['userId'],
                    'category' => $test['categoryId'],
                )
            );

            $this->assertEquals( $test['statusCode'], $response->getStatusCode() );

            $content = json_decode( $response->getContent() );

            $content = $preparator->getPreparedData( $content );
            $test['data'] = $preparator->getPreparedData( $test['data'] );

            $this->assertEntryListEquals( $test['data'], $content );
        }
    }


    public function testIndex_category6()
    {
        $dataFile = __DIR__ . self::$data_dir.'/EntryController_index_category6.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

        $preparator = new DataPreparator();
        $preparator->addUnsetKeys( array( 'isVotedByYou', 'timestamp' ) );

        foreach( $testData as $test ) {

            $pars = array(
                'category' => $test['categoryId'],
            );
            if( $test['excludeVotes'] ) $pars['excludeVotes'] = 'true';
            if( $test['orderBy'] ) $pars['orderBy'] = $test['orderBy'];

            $response = $this->getResponse(
                $test['token'],
                'GET',
                '/entry',
                $pars
            );

            $this->assertEquals( $test['statusCode'], $response->getStatusCode() );

            $content = json_decode( $response->getContent() );

            $content = $preparator->getPreparedData( $content );
            $test['data'] = $preparator->getPreparedData( $test['data'] );

            $this->assertEntryListEquals(
                $test['data'],
                $content
            );
        }
    }


    public function testIndex_category5()
    {
        $dataFile = __DIR__ . self::$data_dir.'/EntryController_index_category5.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

        $preparator = new DataPreparator();
        $preparator->addUnsetKeys( array( 'isVotedByYou', 'timestamp' ) );

        foreach( $testData as $test ) {

            $pars = array(
                'category' => $test['categoryId'],
            );
            if( $test['excludeVotes'] ) $pars['excludeVotes'] = 'true';
            if( $test['orderBy'] ) $pars['orderBy'] = $test['orderBy'];

            $response = $this->getResponse(
                $test['token'],
                'GET',
                '/entry',
                $pars
            );

            $this->assertEquals( $test['statusCode'], $response->getStatusCode() );

            $content = json_decode( $response->getContent() );

            $content = $preparator->getPreparedData( $content );
            $test['data'] = $preparator->getPreparedData( $test['data'] );

            $this->assertEntryListEquals(
                $test['data'],
                $content
            );
        }
    }


    public function testMix_user()
    {
        $dataFile = __DIR__ . self::$data_dir.'/EntryController_mix_user.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

        $preparator = new DataPreparator();
        $preparator->addUnsetKeys( array( 'isVotedByYou', 'timestamp' ) );

        foreach( $testData as $test ) {

            $pars = array(
                'user' => $test['userId'],
            );
            if( $test['orderBy'] ) $pars['orderBy'] = $test['orderBy'];

            $response = $this->getResponse(
                $test['token'],
                'GET',
                '/entry/mix',
                $pars
            );

            $this->assertEquals( $test['statusCode'], $response->getStatusCode() );

            $content = json_decode( $response->getContent() );

            $content = $preparator->getPreparedData( $content );
            $test['data'] = $preparator->getPreparedData( $test['data'] );

            $this->assertEntryListEquals(
                $test['data'],
                $content
            );
        }
    }


    public function testSearch4()
    {
        $dataFile = __DIR__ . self::$data_dir.'/EntryController_search4.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

        $preparator = new DataPreparator();
        $preparator->addUnsetKeys( array( 'isVotedByYou', 'timestamp' ) );

        foreach( $testData as $test ) {

            UserHelper::clear();
            EntryHelper::clear();

            $pars = array(
                'term' => $test['term'],
            );

            $response = $this->getResponse(
                $test['token'],
                'GET',
                '/entry/search4',
                $pars
            );

            $this->assertEquals( $test['statusCode'], $response->getStatusCode() );

            $content = json_decode( $response->getContent() );

            $content = $preparator->getPreparedData( $content );
            $test['data'] = $preparator->getPreparedData( $test['data'] );

            foreach( $test['data']->entries as $e ) {
                if( isset( $e->entry->tags ) ) sort( $e->entry->tags );
            }
            foreach( $content->entries as $e ) {
                if( isset( $e->entry->tags ) ) sort( $e->entry->tags );
            }


            $this->assertEquals(
                $test['data'],
                $content
            );
        }
    }


    public function testShow()
    {
        $dataFile = __DIR__ . self::$data_dir.'/EntryController_show.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

        $preparator = new DataPreparator();
        $preparator->addUnsetKeys( array( 'isVotedByYou', 'timestamp' ) );

        foreach( $testData as $test ) {

            UserHelper::clear();
            EntryHelper::clear();

            $pars = array();

            $response = $this->getResponse(
                $test['token'],
                'GET',
                '/entry/'.$test['entryId'],
                $pars
            );

            $this->assertEquals( $test['statusCode'], $response->getStatusCode() );

            $content = json_decode( $response->getContent() );

            $content = $preparator->getPreparedData( $content );
            $test['data'] = $preparator->getPreparedData( $test['data'] );

            if( isset( $test['data']->tags ) ) sort( $test['data']->tags );
            if( isset( $content->tags ) ) sort( $content->tags );

            $this->assertEquals(
                $test['data'],
                $content
            );
        }
    }
}
