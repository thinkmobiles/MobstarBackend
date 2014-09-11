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
class FeedbackController extends BaseController
{

	public function __construct( Token $token )
	{
		$this->token = $token;
	}

	/**
	 *
	 * @SWG\Api(
	 *    path="/feedback",
	 *   description="Operations to submit feedback",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Add feedback",
	 *       notes="Operation for user to report feedback.",
	 *       nickname="addFeedback",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="feedbackDetails",
	 *           description="The body of a feedback report.",
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
			'feedbackDetails' => 'required',
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
				'feedback_user_id' => $session->token_user_id,
				'feedback_details' => Input::get( 'feedbackDetails' ),
				'feedback_created_date' => date( 'Y-m-d H:i:s' ),
			);

			Feedback::create( $input );

			$response[ 'message' ] = "feedback submitted";
			$status_code = 201;
		}

		return Response::make( $response, $status_code );
	}

}