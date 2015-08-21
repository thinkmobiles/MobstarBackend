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
}
