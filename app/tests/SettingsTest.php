<?php

class SettingsTest extends TestCase
{
    protected $authInfo = array(
        'tokenId' => 'vXISaFByuOlWyju6z7uu3CyAtjBAnNJzIhYEQWnI',
        'userId' => 310,
    );


    protected $authInfoNoFilter = array(
        'userId' => 311,
        'tokenId' => '7NQLtJXXEABt3CeJ5FKDp0dph7EAXeOzuhMW4J9r'
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


    protected function setUserContinentFilter( $continentFilter, $token )
    {
        $response = $this->call(
            'POST',
            '/settings/continentFilter',
            array( 'continentFilter' => $continentFilter ),
            array(),
            array( 'HTTP_X-API-TOKEN' => $token )
        );

        return $response;
    }


    protected function setUserCategoryFilter( $categoryFilter, $token )
    {
        $response = $this->call(
            'POST',
            '/settings/categoryFilter',
            array( 'categoryFilter' => $categoryFilter ),
            array(),
            array( 'HTTP_X-API-TOKEN' => $token )
        );

        return $response;
    }


    protected function getUserContinentFilter( $token )
    {
        $response = $this->call(
            'GET',
            '/settings/continentFilter',
            array(),
            array(),
            array( 'HTTP_X-API-TOKEN' => $token )
        );

        return $response;
    }


    protected function assertUserHasCategoryFilter( $expected, $token )
    {
        $response = $this->call(
            'GET',
            '/settings/categoryFilter',
            array(),
            array(),
            array( 'HTTP_X-API-TOKEN' => $token )
        );

        $this->assertEquals( 200, $response->getStatusCode() );

        $content = json_decode( $response->getContent() );

        $this->assertNotEmpty( $content );
        $this->assertHasNoErrors( $content );
        $this->assertContentHasCategoryFilter( $expected, $content );
    }


    protected function assertResponseIsOkAndHasCategoryFilter( $filter, $response )
    {
        $this->assertEquals( 200, $response->getStatusCode() );

        $content = json_decode( $response->getContent() );

        $this->assertNotEmpty( $content );
        $this->assertHasNoErrors( $content );
        $this->assertContentHasCategoryFilter($filter, $content);
    }


    protected function assertResponseHasCategoryFilter( $filter, $response )
    {
        $content = json_decode( $response->getContent() );

        $this->assertNotEmpty( $content );
        $this->assertContentHasCategoryFilter( $filter, $content );
    }


    protected function assertContentHasCategoryFilter( $filter, $content )
    {
        $this->assertObjectHasAttribute( 'categoryFilter', $content );
        $this->assertEquals( $filter, $content->categoryFilter );
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
        $response = $this->call( 'GET', '/settings/account', array(), array(), array( 'HTTP_X-API-TOKEN' => $this->authInfoNoFilter['tokenId'] ));

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


    public function testSetContinentFilter_OK()
    {
        $continentFilter = array( 1, 3 );

        $response = $this->setUserContinentFilter( json_encode( $continentFilter ), $this->authInfo['tokenId'] );

        $this->assertEquals( 200, $response->getStatusCode() );

        $content = json_decode( $response->getContent() );

        $this->assertNotEmpty( $content );
        $this->assertHasNoErrors( $content );
        $this->assertObjectHasAttribute( 'continentFilter', $content );
        $this->assertEquals( $continentFilter, $content->continentFilter );
    }


    public function testSetContinentFilter_withAllWorld()
    {
        // first set to good
        $continentFilter = array( 2, 4 );
        $response = $this->setUserContinentFilter( json_encode( $continentFilter ), $this->authInfo['tokenId'] );

        $this->assertEquals( 200, $response->getStatusCode() );

        $content = json_decode( $response->getContent() );

        $this->assertNotEmpty( $content );
        $this->assertHasNoErrors( $content );
        $this->assertObjectHasAttribute( 'continentFilter', $content );
        $this->assertEquals( $continentFilter, $content->continentFilter );

        // try to set with all world
        $continentFilterWithWorld = array( 1, 3, 0 );

        $response = $this->setUserContinentFilter( json_encode( $continentFilterWithWorld ), $this->authInfo['tokenId'] );

        $this->assertEquals( 400, $response->getStatusCode(), "setting 0 (all world) is not allowed" );

        //check that user continent filter remains valid
        error_log( $response->getContent() );
        $content = json_decode( $response->getContent() );
        error_log( print_r( $content, true ) );

        $this->assertTrue( isset( $content->error) || isset( $content->errors), 'no error description returned' );
        $this->assertObjectHasAttribute( 'continentFilter', $content );
        $this->assertEquals( $continentFilter, $content->continentFilter );
    }


    public function testSetContinentFilter_noValue()
    {
        // first set to good
        $continentFilter = array( 3, 5 );
        $response = $this->setUserContinentFilter( json_encode( $continentFilter ), $this->authInfo['tokenId'] );

        $this->assertEquals( 200, $response->getStatusCode() );

        $content = json_decode( $response->getContent() );

        $this->assertNotEmpty( $content );
        $this->assertHasNoErrors( $content );
        $this->assertObjectHasAttribute( 'continentFilter', $content );
        $this->assertEquals( $continentFilter, $content->continentFilter );

        // try to set to no value
        $continentFilterNoValue = array();

        $response = $this->setUserContinentFilter( json_encode( $continentFilterNoValue ), $this->authInfo['tokenId'] );

        $this->assertEquals( 200, $response->getStatusCode() );

        //check that user continent filter remains valid
        $content = json_decode( $response->getContent() );

        $this->assertHasNoErrors( $content );
        $this->assertObjectHasAttribute( 'continentFilter', $content );
        $this->assertEquals( $continentFilterNoValue, $content->continentFilter );
    }


    public function testSetContinentFilter_wrongValue()
    {
        // first set to good
        $continentFilter = array( 1, 3 );
        $response = $this->setUserContinentFilter( json_encode( $continentFilter ), $this->authInfo['tokenId'] );

        $this->assertEquals( 200, $response->getStatusCode() );

        $content = json_decode( $response->getContent() );

        $this->assertNotEmpty( $content );
        $this->assertHasNoErrors( $content );
        $this->assertObjectHasAttribute( 'continentFilter', $content );
        $this->assertEquals( $continentFilter, $content->continentFilter );


        // try to set wrong
        $continentFilterWrong = array(1234, 5678);

        $response = $this->setUserContinentFilter( json_encode( $continentFilterWrong ), $this->authInfo['tokenId'] );

        $this->assertEquals( 400, $response->getStatusCode() );

        //check that user continent filter remains valid
        $content = json_decode( $response->getContent() );

        $this->assertTrue( isset( $content->error) || isset( $content->errors), 'no error description returned' );
        $this->assertObjectHasAttribute( 'continentFilter', $content );
        $this->assertEquals( $continentFilter, $content->continentFilter );
    }


    public function testGetContinentFilter_noFilter()
    {
        $response = $this->getUserContinentFilter( $this->authInfoNoFilter['tokenId'] );

        $this->assertEquals( 200, $response->getStatusCode() );

        $content = json_decode( $response->getContent() );

        $this->assertNotEmpty( $content );
        $this->assertHasNoErrors( $content );
        $this->assertObjectHasAttribute( 'continentFilter', $content );
        $this->assertEquals( array(), $content->continentFilter );
    }


    public function testGetContinentFilter_OK()
    {
        // first set filter to good values
        $continentFilterGood = array( 1, 2, 3 );

        $response = $this->setUserContinentFilter( json_encode( $continentFilterGood ), $this->authInfo['tokenId'] );

        $this->assertEquals( 200, $response->getStatusCode() );

        // get filter
        $response = $this->getUserContinentFilter( $this->authInfo['tokenId'] );

        $this->assertEquals( 200, $response->getStatusCode() );

        $content = json_decode( $response->getContent() );

        $this->assertNotEmpty( $content );
        $this->assertHasNoErrors( $content );
        $this->assertObjectHasAttribute( 'continentFilter', $content );
        $this->assertEquals( $continentFilterGood, $content->continentFilter );
    }


    public function testGetContinentFilter_AllContinents()
    {
        // first set filter to all continents
        $continentFilterAllContinents = array( 1, 2, 3, 4, 5, 6 );

        $response = $this->setUserContinentFilter( json_encode( $continentFilterAllContinents ), $this->authInfo['tokenId'] );

        $this->assertEquals( 200, $response->getStatusCode() );

        // get filter
        $response = $this->getUserContinentFilter( $this->authInfo['tokenId'] );

        $this->assertEquals( 200, $response->getStatusCode() );

        $content = json_decode( $response->getContent() );

        $this->assertNotEmpty( $content );
        $this->assertHasNoErrors( $content );
        $this->assertObjectHasAttribute( 'continentFilter', $content );
        // must return empty array
        $this->assertEquals( array(), $content->continentFilter );

    }


    public function testSetCategoryFilter_OK()
    {
        $categoryFilter = array( 1, 3 );

        $response = $this->setUserCategoryFilter( json_encode( $categoryFilter ), $this->authInfo['tokenId'] );

        $this->assertResponseIsOkAndHasCategoryFilter( $categoryFilter, $response );
    }


    public function testSetCategoryFilter_withZero()
    {
        // first set to good
        $goodFilter = array( 3, 4, 6 );
        $response = $this->setUserCategoryFilter( json_encode( $goodFilter ), $this->authInfo['tokenId'] );

        $this->assertResponseIsOkAndHasCategoryFilter( $goodFilter, $response );

        // try to set with zero
        $categoryFilterWithZero = array( 1, 3, 0 );

        $response = $this->setUserCategoryFilter( json_encode( $categoryFilterWithZero ), $this->authInfo['tokenId'] );

        $this->assertEquals( 400, $response->getStatusCode(), "setting 0 category is not allowed" );
        $this->assertResponseHasCategoryFilter( $goodFilter, $response, "current filter is not returned" );
    }


    public function testSetCategoryFilter_wrongCategory()
    {
        // first set to good
        $goodFilter = array( 1, 5 );
        $response = $this->setUserCategoryFilter( json_encode( $goodFilter ), $this->authInfo['tokenId'] );

        $this->assertResponseIsOkAndHasCategoryFilter( $goodFilter, $response );

        // try to set with invalid id (9)
        $invalidFilter = array( 4, 9 );

        $response = $this->setUserCategoryFilter( json_encode( $invalidFilter ), $this->authInfo['tokenId'] );

        $this->assertEquals( 400, $response->getStatusCode(), "setting invalid category is not allowed" );
        $this->assertResponseHasCategoryFilter( $goodFilter, $response, "current filter is not returned" );
    }


    public function testSetCategoryFilter_noFilter()
    {
        // first set to good
        $goodFilter = array( 3, 6 );
        $response = $this->setUserCategoryFilter( json_encode( $goodFilter ), $this->authInfo['tokenId'] );

        $this->assertResponseIsOkAndHasCategoryFilter( $goodFilter, $response );

        // try to set with invalid id (9)
        $noFilter = array();

        $response = $this->setUserCategoryFilter( json_encode( $noFilter ), $this->authInfo['tokenId'] );

        $this->assertResponseIsOkAndHasCategoryFilter( $noFilter, $response );
    }


    public function testGetCategoryFilter_noFilter()
    {
        $this->assertUserHasCategoryFilter( array(), $this->authInfoNoFilter['tokenId'] );
    }


    public function testGetCategoryFilter_OK()
    {
        // first set filter to good values
        $categoryFilter = array( 1, 5 );

        $response = $this->setUserCategoryFilter( json_encode( $categoryFilter ), $this->authInfo['tokenId'] );

        $this->assertEquals( 200, $response->getStatusCode() );

        // get filter
        $this->assertUserHasCategoryFilter( $categoryFilter, $this->authInfo['tokenId'] );
    }


    public function testGetCategoryFilter_AllCategories()
    {
        // first set filter to all categories
        $categoryFilterAllCategories = array( 1, 3, 4, 5, 6 );

        $response = $this->setUserCategoryFilter( json_encode( $categoryFilterAllCategories ), $this->authInfo['tokenId'] );

        $this->assertEquals( 200, $response->getStatusCode() );

        // get filter
        $this->assertUserHasCategoryFilter( array() , $this->authInfo['tokenId'] );
    }
}
