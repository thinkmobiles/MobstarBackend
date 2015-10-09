<?php

class UserControllerTest extends TestCase
{

    private static $data_dir = '/data/old_version';


    protected function getResponse( $token, $method, $url, $pars = array() )
    {
        $response = $this->call( $method, $url, $pars, array(), array( 'HTTP_X-API-TOKEN' => $token ) );

        return $response;
    }


    public function testShow()
    {
        $dataFile = __DIR__ . self::$data_dir.'/UserController_show.txt';

        $testData = unserialize( file_get_contents( $dataFile ) );

        foreach( $testData as $test ) {

            $userId = $test['userId'];

            $response = $this->getResponse( $test['token'], 'GET', '/user/'.$userId );

            $this->assertEquals( $test['statusCode'], $response->getStatusCode() );

            $content = json_decode( $response->getContent() );

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
