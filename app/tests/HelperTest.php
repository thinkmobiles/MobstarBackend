<?php


class HelperTest extends TestCase {


  public function testGetVideoInfo()
  {
    $filesDir = __DIR__.'/files/';

    $filename = 'test_movie.mp4';

    $info = getMediaInfo( $filesDir.$filename );

    $this->assertInternalType( 'array', $info );

    $this->assertEquals( 'video', $info['type'] );
    $this->assertEquals( 15, $info['duration'] );
    $this->assertEquals( 90, $info['rotate'] );
  }


  public function testGetVideoInfoNoRotation()
  {
    $filesDir = __DIR__.'/files/';

    $filename = 'no_rotate.mp4';

    $info = getMediaInfo( $filesDir.$filename );

    $this->assertInternalType( 'array', $info );

    $this->assertEquals( 'video', $info['type'] );
    $this->assertEquals( 15, $info['duration'] );
    $this->assertEquals( 0, $info['rotate'] );
  }


  public function testGetAudioInfo()
  {
    $filesDir = __DIR__.'/files/';

    $filename = 'test_audio.mp3';

    $info = getMediaInfo( $filesDir.$filename );

    $this->assertInternalType( 'array', $info );

    $this->assertEquals( 'audio', $info['type'] );
    $this->assertEquals( 15, $info['duration'] );
    $this->assertEquals( false, $info['rotate'] );
  }


  public function testGetVideoThumbnail()
  {
    $filesDir = __DIR__.'/files/';

    $filename = 'test_movie';
    $ext = '.mp4';
    $thumbName = $filesDir.'out/'.$filename.'-thumb.jpeg';
    $filename .= $ext;

    if( file_exists( $thumbName ) ) unlink( $thumbName );

    makeVideoThumbnail( $filesDir.$filename, $thumbName);

    $this->assertFileExists( $thumbName );
  }


  public function testGetVideoThumbnailWithMediaInfo()
  {
    $filesDir = __DIR__.'/files/';

    $filename = 'no_rotate';
    $ext = '.mp4';
    $thumbName = $filesDir.'out/'.$filename.'-thumb.jpeg';
    $filename .= $ext;

    if( file_exists( $thumbName ) ) unlink( $thumbName );

    $videoInfo = getMediaInfo( $filename );

    makeVideoThumbnail( $filesDir.$filename, $thumbName, $videoInfo );

    $this->assertFileExists( $thumbName );
  }

}
