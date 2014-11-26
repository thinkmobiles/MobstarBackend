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
class TalentController extends BaseController
{

	public function __construct( Token $token )
	{
		$this->token = $token;
	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/talent",
	 *   description="Operations for My Talent Screen",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="Get the current users top talents",
	 *       notes="Returns entry objects",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="period",
	 *           description="Current or All time.",
	 *           paramType="query",
	 *           required=false,
	 *           type="string",
	 *             enum="['current','allTime']"
	 *         )
	 * 		  ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=404,
	 *            message="No entries found"
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

		$votes = Vote::where( 'vote_user_id', '=', $session->token_user_id )->where('vote_up', '=', 1)->where('vote_deleted', '=', 0)->lists( 'vote_entry_id' );

		if(count($votes) > 0)
		{
			$entries = Entry::whereIn( 'entry_id', $votes )->orderBy( 'entry_rank', 'asc')->get();

			foreach( $entries as $entry )
			{
				$return[ 'entries' ][ ][ 'entry' ] = oneEntry( $entry, $session, true );
			}
		}

		else{
			$return['entries'] = [];
		}

		$response = Response::make( $return, 200 );

		return $response;
	}



	/**
	 *
	 * @SWG\Api(
	 *   path="/talent",
	 *   description="Operations for My Talent Screen",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="DELETE",
	 *       summary="Remove Talent",
	 *       notes="Operation for user to remove another user from their talent pool",
	 *       nickname="deleteTalent",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="user",
	 *           description="User ID who you want to remove from your talent pool.",
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


	public function delete($user)
	{
		$return = [ ];

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$entries = Entry::where('entry_user_id', '=', $user)->lists('entry_id');

		DB::transaction(function() use ($entries)
		{
			foreach ($entries as $entry){
				DB::table('votes')
					->where('vote_entry_id', $entry )
					->update(array('vote_deleted' => 0));
			}
		});

		$return['talent'] = "All votes deleted";

		$response = Response::make( $return, 200 );

		return $response;
	}
}