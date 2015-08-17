<?php

class ExampleTest extends TestCase {

  public function testDebug()
  {
    $response = $this->call( 'GET', '/debug' );

    $this->assertTrue( strlen( $response->getContent() ) > 250 );
    echo $response->getContent(), "\n";
  }
}
