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

		$return['privacyPolicy'] = 'This privacy policy (the "Privacy Policy") describes how and when MobStar, Inc. ("MobStar" "us" "we" "our") collects, uses, and shares your information when you use the MobStar application, software and/or website(s) (the "Services"). This Privacy Policy explains the information gathering, use and sharing practices for the Services, including what information is collected, how that information is acquired, maintained, stored, shared and/or used, and your privacy choices. This Privacy Policy is independent from our offline personal data collection practices. When using any of our Services you consent to the collection, transfer, manipulation, storage, disclosure and other uses of your information as described in this Privacy Policy. Irrespective of which country you reside in or supply information from, you authorize MobStar to use your information in the United States, Ireland, and any other country where MobStar operates.In some cases, you also may visit third party websites through links on the Services who may collect and use your personal information and other non-personal information. We encourage you to review the policies of any such third parties before disclosing your personal information, as we have no control over, and are not liable for, their privacy practices.';

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
