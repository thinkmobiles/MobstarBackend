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
 *  resourcePath="/star",
 *  basePath="http://api.mobstar.com"
 * )
 */
class StarController extends BaseController
{

	public function __construct( Token $token )
	{
		$this->token = $token;
	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/star",
	 *   description="Operations about stars",
	 *   produces="['application/json']",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Add star",
	 *       notes="Operation for user to add a Star to their 'Stars' list.",
	 *       nickname="addStar",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="star",
	 *           description="The stars User ID.",
	 *           paramType="form",
	 *           required=true,
	 *           type="integer"
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
			'star' => 'required|numeric|exists:users,user_id',
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
				'user_star_user_id'      => $session->token_user_id,
				'user_star_star_id'      => Input::get( 'star' ),
				'user_star_created_date' => date( 'Y-m-d H:i:s' ),
			);

			$star = new Star;
			$star->user_star_user_id = $session->token_user_id;
			$star->user_star_star_id = Input::get( 'star' );
			$star->user_star_created_date = date( 'Y-m-d H:i:s' );
			$star->save();

			$response[ 'message' ] = "star added";
			$status_code = 201;
		}

		return Response::make( $response, $status_code );
	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/star",
	 *   description="Operations about Stars",
	 *   produces="['application/json']",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="DELETE",
	 *       summary="Remove star",
	 *       notes="Operation for user to remove a user from their list of 'Stars'",
	 *       nickname="removeStar",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="star",
	 *           description="The stars User ID.",
	 *           paramType="path",
	 *           required=true,
	 *           type="integer"
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
	public function destroy($id)
	{
		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		//Validate Input
		$rules = array(
			'star' => 'required|numeric',
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

			$where = ['user_star_star_id' => $id,
			'user_star_user_id' => $session->token_user_id,];
			$stars = Star::where('user_star_star_id','=', $id)->where('user_star_user_id', '=', $session->token_user_id)->get();

			foreach ($stars as $star){
				$star->user_star_deleted = 1;
				$star->save();
			}

			$response[ 'message' ] = "star removed";
			$status_code = 200;

		}

		return Response::make( $response, $status_code );
	}

}