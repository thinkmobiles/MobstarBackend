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
	 *       nickname="getAllComments",
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

		$votes = Vote::where( 'vote_user_id', '=', $session->token_user_id )->list( 'vote_entry_id' );

		$entries = Entry::whereIn( 'entry_id', $votes )->get();

		foreach( $entries as $entry )
		{
			$return[ 'entries' ][ ][ 'entry' ] = oneEntry( $entry, $session, true );
		}

		$response = Response::make( $return, 200 );

		return $response;
	}
}