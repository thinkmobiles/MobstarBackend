<?php

use Swagger\Annotations as SWG;

/**
 * @SWG\Resource(
 *  apiVersion=0.2,
 *  swaggerVersion=1.2,
 *  basePath="http://api.mobstar.com"
 * )
 */
class ServerController extends BaseController
{


  /**
   *
   * @SWG\Api(
   *   path="/server/time",
   *   description="Get the current server time in milliseconds (UTC)",
   *   @SWG\Operations(
   *     @SWG\Operation(
   *       method="GET",
   *       summary="Get the current server time in milliseconds (UTC)",
   *       notes="Get the current server time in milliseconds (UTC)",
   *       @SWG\ResponseMessages(
   *          @SWG\ResponseMessage(
   *            code=401,
   *            message="Authorization failed"
   *          )
   *       )
   *     )
   *   )
   * )
   */

  public function time()
  {
    list( $msec, $sec ) = explode( ' ', microtime() );

    if( empty( $msec ) ) $msec = 0;

    $timeInMillisecs = round( ((float)$sec + (float)$msec ) * 1000, 0 );

    $return = array(
      'serverCurrentTime' => $timeInMillisecs,
    );

    $response = Response::make( $return, 200 );

    return $response;
  }

}
