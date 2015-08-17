<?php
use Swagger\Annotations as SWG;

/**
 * @package
 * @category
 * @subpackage
 *
 * @SWG\Resource(
 *  apiVersion=0.2,
 *  swaggerVersion=1.2,
 *  resourcePath="/welcome",
 *  basePath="http://api.mobstar.com"
 * )
 */
class WelcomeController extends BaseController {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */

	/**
     *
     * @SWG\Api(
     *   path="/welcome/",
     *   description="Operation for Welcome Video",
     *   @SWG\Operations(
     *     @SWG\Operation(
     *       method="GET",
     *       summary="Get welcome video link",
     *       notes="Get welcome video url.",
     *       nickname="getVideo",
     *       @SWG\ResponseMessages(
     *          @SWG\ResponseMessage(
     *            code=401,
     *            message="Authorization failed"
     *          ),
     *          @SWG\ResponseMessage(
     *            code=404,
     *            message="Video not found"
     *          )
     *       )
     *     )
     *   )
     * )
     */

	public function index()
	{

		$return['url'] = "http://".$_ENV['URL']."/uploads/7Abt9hFVKxTe.mp4";

		$response = Response::make($return, 200);

		return $response;
	}
}