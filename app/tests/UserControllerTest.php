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

        $preparator = new DataPreparator(
            array(),
            array( 'profileImage', 'profileCover' )
        );

        foreach( $testData as $test ) {

            $userId = $test['userId'];

            $response = $this->getResponse( $test['token'], 'GET', '/user/'.$userId );

            $this->assertEquals( $test['statusCode'], $response->getStatusCode() );

            $content = json_decode( $response->getContent() );

            $test['data'] = $preparator->getPreparedData( $test['data'] );
            $content = $preparator->getPreparedData( $content );

            $this->assertEquals(
                $test['data'],
                $content
            );
        }
    }
}
