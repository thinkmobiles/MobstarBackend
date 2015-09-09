<?php

class TalentTest extends TestCase
{
    protected $users = array(
        'withFilter' => array(
            'tokenId' => 'vXISaFByuOlWyju6z7uu3CyAtjBAnNJzIhYEQWnI',
            'userId' => 310,
        ),
        'noFilter' => array(
            'userId' => 311,
            'tokenId' => '7NQLtJXXEABt3CeJ5FKDp0dph7EAXeOzuhMW4J9r'
        ),
    );


    public function testTopNew_v2()
    {
        // set filter to Afrika( 1 );
        $response = $this->call(
            'POST',
            '/settings/continentFilter',
            array( 'continentFilter' => json_encode( array( 1) ) ),
            array(),
            array( 'HTTP_X-API-TOKEN' => $this->users['withFilter']['tokenId'] )
        );
        $this->assertEquals( 200, $response->getStatusCode() );

        // get without filter
        $response = $this->call(
            'GET',
            '/talent/topnew',
            array(),
            array(),
            array( 'HTTP_X-API-TOKEN' => $this->users['noFilter']['tokenId'] )
        );
        $this->assertEquals( 200, $response->getStatusCode() );
        $talentsNoFilter = json_decode( $response->getContent() );

        // get with filter
        \Config::set( 'app.force_include_all_world', true );
        $response = $this->call(
            'GET',
            '/talent/topnew',
            array(),
            array(),
            array( 'HTTP_X-API-TOKEN' => $this->users['withFilter']['tokenId'] )
        );
        $this->assertEquals( 200, $response->getStatusCode() );
        $talentsWithFilter = json_decode( $response->getContent() );

        // with filter and without must not be the same
        $this->assertNotEquals( $talentsNoFilter, $talentsWithFilter );
    }
}
