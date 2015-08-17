<?php

class ExampleTest extends TestCase {

  public function testDebug()
  {
    $response = $this->call( 'GET', '/debug' );

    $this->assertTrue( strlen( $response->getContent() ) > 250 );
  }


  public function testGetEntries()
  {

    $response = $this->call( 'GET', '/entry', array(), array(), array( 'HTTP_X-API-TOKEN' => '07516258357' ) );

    $this->assertEquals( 200, $response->getStatusCode() );

    $contentJSON = $response->getContent();

    $this->assertNotEmpty( $contentJSON );

    $content = json_decode( $contentJSON );

    $this->assertINstanceOf( 'stdClass', $content );

    $this->assertObjectHasAttribute( 'entries', $content );
    $this->assertObjectHasAttribute( 'next', $content );

    $entries = $content->entries;
    $this->assertTrue( count( $entries ) == 20 );
  }
}