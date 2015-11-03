<?php

use MobStar\Storage\Token\TokenRepository as Token;
use Swagger\Annotations as SWG;

/**
 * @package
 * @category
 * @subpackage
 *
 * @SWG\Resource(
 *  apiVersion=0.2,
 *  swaggerVersion=1.2,
 *  basePath="http://api.mobstar.com"
 * )
 */
class PrivacyController extends BaseController
{

	public function __construct( Token $token )
	{
		$this->token = $token;
	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/privacy",
	 *   description="Operations about privacy policy",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="Get MobStar Privacy Policy",
	 *       notes="Returns available policy",
	 *       nickname="getAllComments",
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=404,
	 *            message="Policy not found"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function index()
	{
		$return = [ ];

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );


		$user = User::find($session->token_user_id);

		$user->user_policy_seen = 1;

		$user->save();

		$return['privacyPolicy'] = "lorem ipsum dolor";

		$response = Response::make( $return, 200 );

		return $response;
	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/privacy/accept",
	 *   description="Operations about privacy policy",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="Accept MobStar Privacy Policy",
	 *       notes="Marks this user has accepted privacy policy",
	 *       nickname="getAllComments",
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=404,
	 *            message="Policy not found"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function store()
	{
	    markDeprecated( __METHOD__ );

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$user = User::find($session->token_user_id);

		$user->user_policy_seen = 1;
		$user->user_policy_accepted = 1;

		$user->save();

		$return['status'] = "User accepted";

		$response = Response::make( $return, 200 );

		return $response;
	}
}
