<?php

class AppVertionTest extends TestCase
{

    private $testUser = array(
        'userId' => 4899,
        'v1_api_key' => 'fjwiofrnxhr892305hje93nf834m63lr93l5u3ntrhj4k',
        'v2_api_key' => '2_xPvd11Vjj1PfgYZ5C5fIWIosTmR4ADEgVIXsXp95',
        'token' => 'ncK0JvjROAN2Mvn9D4RanFxXmTmy919xCWKkjd4O',
    );


    public function testSetAppVertion()
    {
        // enable filters for this test
        Route::enableFilters();

        // set user version to 1
        $response = $this->call(
            'GET',
            '/settings/account',
            array(),
            array(),
            array(
                'HTTP_X-API-TOKEN' => $this->testUser['token'],
                'HTTP_X-API-KEY' => $this->testUser['v1_api_key'],
            )
        );

        $this->assertEquals( 200, $response->getStatusCode() );

        // check user version
        $session = DB::table( 'tokens')->where( 'token_value', '=', $this->testUser['token'] )->first();

        $this->assertEquals( 1, $session->token_app_version );

        // set user version to 2
        $response = $this->call(
            'GET',
            '/settings/account',
            array(),
            array(),
            array(
                'HTTP_X-API-TOKEN' => $this->testUser['token'],
                'HTTP_X-API-KEY' => $this->testUser['v2_api_key'],
            )
        );

        $this->assertEquals( 200, $response->getStatusCode() );

        //check user version
        $session = DB::table( 'tokens')->where( 'token_value', '=', $this->testUser['token'] )->first();

        $this->assertEquals( 2, $session->token_app_version );
    }
}
