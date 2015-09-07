<?php

class ServerTest extends TestCase {

  public function testTime()
  {
    $response = $this->call( 'GET', 'server/time' );

    $this->assertEquals( 200, $response->getStatusCode() );

    $content = json_decode( $response->getContent() );

    $this->assertObjectHasAttribute( 'serverCurrentTime', $content );

    $receivedTimeMillisec = $content->serverCurrentTime;

    list( $msecs, $secs ) = explode( ' ', microtime() );
    $currentTimeMillisec = round( ((float)$secs + (float)$msecs)*1000, 0 );

    $this->assertLessThanOrEqual(
      $currentTimeMillisec,
      $receivedTimeMillisec,
      'received time is greater then current'
    );
    $this->assertGreaterThanOrEqual(
      $currentTimeMillisec-100,
      $receivedTimeMillisec,
      'received time is less then 1 second from current'
    );
  }
}
