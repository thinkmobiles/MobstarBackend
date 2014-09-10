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
class AbuseReportController extends BaseController
{

	public function __construct( Token $token )
	{
		$this->token = $token;
	}

	/**
	 *
	 * @SWG\Api(
	 *    path="/report",
	 *   description="Operations to report abuse",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Add report",
	 *       notes="Operation for user to report abuse to moderators.",
	 *       nickname="addReport",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="reportDetails",
	 *           description="The body of a report.",
	 *           paramType="form",
	 *           required=true,
	 *           type="text"
	 *         )
	 *       ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=400,
	 *            message="Input validation failed"
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

		//Validate Input
		$rules = array(
			'reportDetails' => 'required',
		);

		$validator = Validator::make( Input::get(), $rules );

		if( $validator->fails() )
		{
			//var_dump($validator->messages());
			$response[ 'errors' ] = $validator->messages()->all();
			$status_code = 400;
		}
		else
		{

			//Get input
			$input = array(
				'abuse_report_user_id' => $session->token_user_id,
				'abuse_report_details' => Input::get( 'reportDetails' ),
				'abuse_report_created' => date( 'Y-m-d H:i:s' ),
			);

			AbuseReport::create( $input );

			$response[ 'message' ] = "report added";
			$status_code = 201;
		}

		return Response::make( $response, $status_code );
	}

}