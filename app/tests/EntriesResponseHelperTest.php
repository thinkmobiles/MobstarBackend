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

        foreach( $testData as $test ) {

            $userId = $test['userId'];
            $session = $token->get_session( $test['token'] );
            $sessionUserId = $session->token_user_id;


            $response = EntriesResponseHelper::onlyUser( $userId, $sessionUserId );

            $this->assertEquals( $test['statusCode'], $response['code'] );

            $content = json_decode( json_encode( $response['data'] ) );

            $this->prepDataForCompare( $content );
            $this->prepDataForCompare( $test['data'] );

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

        foreach( $testData as $test ) {

            $session = $token->get_session( $test['token'] );
            $sessionUserId = $session->token_user_id;
            $showFeedback = false;

            foreach( $test['data']->entries as $entryObj ) {
                $entryObj = $entryObj->entry;
                $entry = \Entry::findOrFail( $entryObj->id );

                $response = EntriesResponseHelper::getOneEntry( $entry, $sessionUserId, $showFeedback );
                $content = json_decode( json_encode( $response ) );

                $keysToUnset = array( 'modified', 'isVotedByYou' );
                $this->prepDataForCompare( $content, null, $keysToUnset );
                $this->prepDataForCompare( $entryObj, null, $keysToUnset );

                $this->assertEquals( $entryObj, $content );
            }
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
            if( is_array( $value ) )
            {
                $this->unsetRecursive( $value, $keys );
            }
        }
    }

    private function prepDataForCompare( $data, $AWSUrlKeys = null, $unsetKeys = null )
    {
        if( is_null( $AWSUrlKeys ) ) $AWSUrlKeys = array( 'profileImage', 'profileCover', 'filePath', 'videoThumb' );

        if( is_null( $unsetKeys ) ) $unsetKeys = array( 'modified', 'timestamp' );

        $this->adjustAWSUrlInArray( $data, $AWSUrlKeys );
        $this->unsetRecursive( $data, $unsetKeys );

        return $data;
    }
}
