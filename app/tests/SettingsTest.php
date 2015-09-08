<?php

class SettingsTest extends TestCase
{
    protected $authInfo = array(
        'tokenId' => 'vXISaFByuOlWyju6z7uu3CyAtjBAnNJzIhYEQWnI',
        'userId' => 310,
    );

    protected function assertHasNoErrors( $jsonObject )
    {
        $this->assertObjectNotHasAttribute( 'error', $jsonObject );
        $this->assertObjectNotHasAttribute( 'errors', $jsonObject );
    }


    protected function setUserContinent( $continentId, $token )
    {
        $response = $this->call(
            'POST',
            '/settings/userContinent',
            array( 'userContinent' => $continentId ),
            array(),
            array( 'HTTP_X-API-TOKEN' => $token )
        );

        return $response;
    }


    public function testSetUserContinent_OK()
    {
        $continentId = 3; // Europe

        $response = $this->setUserContinent( $continentId, $this->authInfo['tokenId'] );

        $this->assertEquals( 200, $response->getStatusCode() );

        $content = json_decode( $response->getContent() );

        $this->assertHasNoErrors( $content );
        $this->assertObjectHasAttribute( 'userContinent', $content );
        $this->assertEquals( $continentId, $content->userContinent );

        unset( $response );

        // set to africa
        $continentId = 1; // Africa

        $response = $this->setUserContinent( $continentId, $this->authInfo['tokenId'] );

        $this->assertEquals( 200, $response->getStatusCode() );

        $content = json_decode( $response->getContent() );

        $this->assertHasNoErrors( $content );
        $this->assertObjectHasAttribute( 'userContinent', $content );
        $this->assertEquals( $continentId, $content->userContinent );
    }


    public function testSetUserContinent_allWorld()
    {
        // first, set to South America
        $continentId = 6; // South America

        $response = $this->setUserContinent( $continentId, $this->authInfo['tokenId'] );

        $this->assertEquals( 200, $response->getStatusCode() );

        $content = json_decode( $response->getContent() );

        $this->assertHasNoErrors( $content );
        $this->assertObjectHasAttribute( 'userContinent', $content );
        $this->assertEquals( $continentId, $content->userContinent );

        unset( $response );

        // try to set all world
        $allWorldId = 0; // All world

        $response = $this->setUserContinent( $allWorldId, $this->authInfo['tokenId'] );

        $this->assertEquals( 400, $response->getStatusCode() );

       //check that user continent still points to Europe
        $content = json_decode( $response->getContent() );

        $this->assertTrue( isset( $content->error) || isset( $content->errors), 'no error description returned' );
        $this->assertObjectHasAttribute( 'userContinent', $content );
        $this->assertEquals( $continentId, $content->userContinent );
    }


    public function testSetUserContinent_wrongContinent()
    {
        // first, set to Oceania
        $continentId = 5; // Oceania

        $response = $this->setUserContinent( $continentId, $this->authInfo['tokenId'] );

        $this->assertEquals( 200, $response->getStatusCode() );

        $content = json_decode( $response->getContent() );

        $this->assertHasNoErrors( $content );
        $this->assertObjectHasAttribute( 'userContinent', $content );
        $this->assertEquals( $continentId, $content->userContinent );

        unset( $response );

        // try to set wrong continent
        $allWorldId = 123; // wrong

        $response = $this->setUserContinent( $allWorldId, $this->authInfo['tokenId'] );

        $this->assertEquals( 400, $response->getStatusCode() );

        //check that user continent still points to Europe
        $content = json_decode( $response->getContent() );

        $this->assertTrue( isset( $content->error) || isset( $content->errors), 'no error description returned' );
        $this->assertObjectHasAttribute( 'userContinent', $content );
        $this->assertEquals( $continentId, $content->userContinent );
    }


    public function testAccountReturnUserContinent_noContinent()
    {
        $userWithoutContinent = array(
            'userId' => 311,
            'token' => '7NQLtJXXEABt3CeJ5FKDp0dph7EAXeOzuhMW4J9r'
        );

        $response = $this->call( 'GET', '/settings/account', array(), array(), array( 'HTTP_X-API-TOKEN' => $userWithoutContinent['token'] ));

        $this->assertEquals( 200, $response->getStatusCode() );

        $content = json_decode( $response->getContent() );

        $this->assertHasNoErrors( $content );
        $user = $content->user;

        $this->assertObjectHasAttribute( 'userContinent', $user );
        $this->assertEquals( 0, $user->userContinent );
    }


    public function testAccountReturnUserContinent()
    {
        // set continent to Europe
        $userContinent = 3;
        $response = $this->setUserContinent( $userContinent, $this->authInfo['tokenId'] );
        $this->assertEquals( 200, $response->getStatusCode() );

        // check user continent
        $response = $this->call( 'GET', '/settings/account', array(), array(), array( 'HTTP_X-API-TOKEN' => $this->authInfo['tokenId'] ));

        $this->assertEquals( 200, $response->getStatusCode() );

        $content = json_decode( $response->getContent() );

        $this->assertHasNoErrors( $content );
        $user = $content->user;

        $this->assertObjectHasAttribute( 'userContinent', $user );
        $this->assertEquals( $userContinent, $user->userContinent );

    }
}
