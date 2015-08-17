<?php

//

// dump requests
if( ! empty( $_ENV['DUMP_REQUESTS'] ) )
{
  App::after( function( $request, $response ) {

    $d = date_format( date_create(), 'Y-m-d-h-i-s' );
    for( $count = 0; $count <= 50; $count++ )
    {
      $filename = sprintf( $_ENV["PATH"].'temp/%s-%02d', $d, $count );
      if ($file = @fopen( $filename, 'x' ) ) break;
    }

    if( ! $file ) {
      error_log( 'can not create dump file' );
      return;
    };

    $str = 'Request: '.$request->method().' => '.$request->url()."\n\n";
    if( strpos( $request->url(), 'entry' ) !== FALSE ) {
      ob_start();
      var_dump( $request );
      echo 'Input: ';
      print_r( Input::get() );
      echo 'REQUEST: ';
      print_r( $_REQUEST );
      echo 'POST: ';
      print_r( $_POST );
      echo 'FILES: ';
      print_r( $_FILES );


      $str .= ob_get_contents();
      ob_end_clean();
      $str .= "\n\n\n\n\n";
    }
    $str .= $response->getContent();
    fwrite( $file,  $str );
    fclose( $file );
  });
}
