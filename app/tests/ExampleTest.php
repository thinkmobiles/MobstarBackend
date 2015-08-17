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


  public function testPostEntry()
  {
    $testFilename = __DIR__.'/files/test_movie.mp4';
    $uploadedFilename = __DIR__.'/files/test_movie_for_upload.mp4';

    $this->assertTrue( copy( $testFilename, $uploadedFilename ), 'can not create temp file for uploading' );

    $file = new \Symfony\Component\HttpFoundation\File\UploadedFile(
      $uploadedFilename,
      'test_file'
    );

    $response = $this->call( 'POST', '/entry',
      array(
        'category' => 7,
        'type' => 'video',
        'language' => 'english',
        'name' => 'Test',
        'description' => 'Test Video',
      ),
      array( 'file1' => $file ),
      array( 'HTTP_X-API-TOKEN' => '07516258357' )
    );

    $this->assertEquals( 201, $response->getStatusCode() );
  }
}
