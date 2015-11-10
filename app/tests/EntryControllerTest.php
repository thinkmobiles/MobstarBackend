<?php

use MobStar\UserHelper;
use MobStar\EntryHelper;

class EntryControllerTest extends TestCase{

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

            $content = $this->prepDataForCompare( $content );

            $test['data'] = $this->prepDataForCompare( $test['data'] );

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

            $content = $this->prepDataForCompare( $content );

            $test['data'] = $this->prepDataForCompare( $test['data'] );

            $this->assertEquals(
                $test['data'],
                $content
            );
        }
    }


    public function testIndex_category6()
    {
        $dataFile = __DIR__ . self::$data_dir.'/EntryController_index_category6.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

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

            $content = $this->prepDataForCompare( $content );

            $test['data'] = $this->prepDataForCompare( $test['data'] );

            $this->assertEquals(
                $test['data'],
                $content
            );
        }
    }


    public function testIndex_category5()
    {
        $dataFile = __DIR__ . self::$data_dir.'/EntryController_index_category5.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

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

            $content = $this->prepDataForCompare( $content );

            $test['data'] = $this->prepDataForCompare( $test['data'] );

            $this->assertEquals(
                $test['data'],
                $content
            );
        }
    }


    public function testMix_user()
    {
        $dataFile = __DIR__ . self::$data_dir.'/EntryController_mix_user.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

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

            $content = $this->prepDataForCompare( $content );

            $test['data'] = $this->prepDataForCompare( $test['data'] );


            $this->assertEquals(
                $test['data'],
                $content
            );
        }
    }


    public function testSearch4()
    {
        $dataFile = __DIR__ . self::$data_dir.'/EntryController_search4.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

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

            $content = $this->prepDataForCompare( $content );

            $test['data'] = $this->prepDataForCompare( $test['data'] );

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

            $content = $this->prepDataForCompare( $content );

            $test['data'] = $this->prepDataForCompare( $test['data'] );

            if( isset( $test['data']->tags ) ) sort( $test['data']->tags );
            if( isset( $content->tags ) ) sort( $content->tags );

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


private function unsetRecursive( & $data, $keys )
    {
        if( is_array( $data ) )
        {
            foreach( $keys as $key )
            {
                unset( $data[ $key ] );
            }
        }
        elseif( is_object( $data ) )
        {
            foreach( $keys as $key )
            {
                unset( $data->$key );
            }
        }

        foreach( $data as &$value )
        {
            if( is_array( $value ) or is_object( $value ) )
            {
                $this->unsetRecursive( $value, $keys );
            }
        }
    }


    private function prepDataForCompare( $data, $AWSUrlKeys = null, $unsetKeys = null )
    {
        if( is_null( $AWSUrlKeys ) ) $AWSUrlKeys = array( 'profileImage', 'profileCover', 'filePath', 'videoThumb' );

        if( is_null( $unsetKeys ) ) $unsetKeys = array( 'modified', 'timestamp', 'isVotedByYou' );

        $this->adjustAWSUrlInArray( $data, $AWSUrlKeys );
        $this->unsetRecursive( $data, $unsetKeys );

        return $data;
    }

}
