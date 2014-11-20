<?php

use MobStar\Storage\Vote\VoteRepository as Vote;
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
 *  resourcePath="/vote",
 *  basePath="http://api.mobstar.com"
 * )
 */
class VoteController extends BaseController
{

	public $valid_fields = [ "id", "user", "entry", "type", "date" ];

	public function __construct( Vote $vote, Token $token )
	{
		$this->vote = $vote;
		$this->token = $token;
	}
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */

	/**
	 *
	 * @SWG\Api(
	 *   path="/vote",
	 *   description="Operations about Votes",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="Get all votes",
	 *       notes="Returns all available votes",
	 *       nickname="getAllVotes",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="fields",
	 *           description="Accepted values for the fields parameter are: id, user, entry, type, date.",
	 *           paramType="query",
	 *           required=false,
	 *           type="comma seperated list"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="user",
	 *           description="User ID whose votes you want to view.",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="entry",
	 *           description="Entry ID of votes you want to view.",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="type",
	 *           description="Type of vote cast, options are up or down.",
	 *           paramType="query",
	 *           required=false,
	 *           type="string",
	 *             enum="['up', 'down']"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="page",
	 *           description="Page of results you want to view.",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="limit",
	 *           description="Maximum number of representations in response.",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer"
	 *         )
	 *       ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=404,
	 *            message="Vote not found"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function index()
	{

		$fields = array_values( explode( ',', Input::get( "fields" ) ) );

		if( $fields[ 0 ] == "" )
		{
			unset( $fields );
		}

		$return = [ ];
		$valid = false;

		if( !empty( $fields ) )
		{
			//Check if fields are valid
			foreach( $fields as $field )
			{
				if( !in_array( $field, $this->valid_fields ) )
				{
					$return[ 'errors' ][ ] = [ $field . " is not a valid field." ];
				}
				else
				{
					$valid = true;
				}
			}

		}

		//Get limit to calculate pagination 
		$limit = ( Input::get( 'limit', '50' ) );

		//If not numeric set it to the default limit
		$limit = ( !is_numeric( $limit ) || $limit < 1 ) ? 50 : $limit;

		//Get page
		$page = ( Input::get( 'page', '1' ) );
		$page = ( !is_numeric( $page ) ) ? 1 : $page;

		//Calculate offset
		$offset = ( $page * $limit ) - $limit;

		//If page is greter than one show a previous link
		if( $page > 1 )
		{
			$previous = true;
		}
		else
		{
			$previous = false;
		}

		//Get user
		$user = ( Input::get( 'user', '0' ) );
		$user = ( !is_numeric( $user ) ) ? 0 : $user;

		//Get Category
		$entry = ( Input::get( 'entry', '0' ) );
		$entry = ( !is_numeric( $entry ) ) ? 0 : $entry;

		//Get subCategory
		$type = ( Input::get( 'type', '0' ) );

		if( $type )
		{
			if( $type == "up" )
			{
				$up = true;
				$down = false;
			}
			else
			{
				if( $type == 'down' )
				{
					$down = true;
					$up = false;
				}
			}
		}
		else
		{
			$up = false;
			$down = false;
		}

		$deleted = ( Input::get( 'delted', '0' ) );

		$votes = $this->vote->get_votes( $entry, $user, $up, $down, $deleted, $limit, $offset, false );
		$count = $this->vote->get_votes( $entry, $user, $up, $down, $deleted, $limit, $offset, true );

		//return $votes;

		if( $count == 0 )
		{
			$return = [ 'error' => 'No Entries Found' ];
			$status_code = 404;

			return Response::make( $return, $status_code );
		}

		//If the count is greater than the highest number of items displayed show a next link
		elseif( $count > ( $limit * $page ) )
		{
			$next = true;
		}
		else
		{
			$next = false;
		}

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );


		foreach( $votes as $vote )
		{

			$current = array();

			//check to see if fields were specified and at least one is valid
			if( ( !empty( $fields ) ) && $valid )
			{

				if( in_array( "id", $fields ) )
				{
					$current[ 'id' ] = $vote->vote_id;
				}

				if( in_array( "user", $fields ) )
				{
					$current[ 'user' ] = oneUser($vote->user, $session,  true);

				}

				if( in_array( "entry", $fields ) )
				{
					$current[ 'entry' ] = oneEntry($vote->entry, $session, true);
				}

				if( in_array( "type", $fields ) )
				{
					if( $vote[ 'vote_up' ] == 1 && $vote[ 'vote_down' ] == 0 )
					{
						$current[ 'type' ] = "Upvote";
					}
					elseif( $vote[ 'vote_up' ] == 0 && $vote[ 'vote_down' ] == 1 )
					{
						$current[ 'type' ] = "Downvote";
					}
					else
					{
						$current[ 'type' ] = "Error";
					}
				}

				if( in_array( "date", $fields ) )
				{
					$current[ 'date' ] = $vote->vote_created_date;
				}

				$return[ 'votes' ][ ][ 'votes' ] = $current;
			}

			else
			{

				$current[ 'id' ] = $vote->vote_id;
				$current[ 'user' ] = oneUser($vote->user, $session,  true);
				$current[ 'entry' ] = oneEntry($vote->entry, $session, true);

				if( $vote[ 'vote_up' ] == 1 && $vote[ 'vote_down' ] == 0 )
				{
					$current[ 'type' ] = "Upvote";
				}
				elseif( $vote[ 'vote_up' ] == 0 && $vote[ 'vote_down' ] == 1 )
				{
					$current[ 'type' ] = "Downvote";
				}
				else
				{
					$current[ 'type' ] = "Error";
				}

				$return[ 'votes' ][ ][ 'vote' ] = $current;
			}
		}

		$status_code = 200;

		//If next is true create next page link
		if( $next )
		{
			$return[ 'next' ] = "http://api.mobstar.com/vote/?" . http_build_query( [ "limit" => $limit, "page" => $page + 1 ] );
		}

		if( $previous )
		{
			$return[ 'previous' ] = "http://api.mobstar.com/vote/?" . http_build_query( [ "limit" => $limit, "page" => $page - 1 ] );
		}

		$response = Response::make( $return, $status_code );

		$response->header( 'X-Total-Count', $count );

		return $response;
	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/vote",
	 *   description="Operations about Votes",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Cast vote",
	 *       notes="Operation for user to cast a vote on an entry, if they have previously cast a vote for this entry the previous vote will be discarded.",
	 *       nickname="getAllVotes",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="entry",
	 *           description="Entry ID that the vote is associated with.",
	 *           paramType="form",
	 *           required=true,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="type",
	 *           description="Type of vote to be cast, options are up or down.",
	 *           paramType="form",
	 *           required=true,
	 *           type="string",
	 *             enum="['up', 'down']"
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
			'entry' => 'required|numeric|exists:entries,entry_id',
			'type'  => 'required'
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
			$input = [
				'vote_user_id'      => $session->token_user_id,
				'vote_entry_id'     => Input::get( 'entry' ),
				'vote_created_date' => date( 'Y-m-d H:i:s' ),
			];

			//Delete previous votes for this entry cast by this user
			$this->vote->delete_previous( $input );

			$entry = Entry::find( $input[ 'vote_entry_id' ] );

			if( Input::get( 'type' ) == 'up' )
			{
//				return $session;
				$input[ 'vote_up' ] = 1;
				$prev_not = Notification::where( 'notification_user_id', '=', $entry->entry_user_id, 'and' )
										->where( 'notification_entry_id', '=', $entry->entry_id, 'and' )
										->where( 'notification_details', '=', 'has voted up your entry', 'and' )
										->orderBy( 'notification_updated_date', 'desc' )
										->first();

				if( !count( $prev_not ) )
				{
					Notification::create( [ 'notification_user_id'      => $entry->entry_user_id,
											'notification_subject_ids'  => json_encode( [ $session->token_user_id ] ),
											'notification_details'      => 'has voted up your entry',
											'notification_read'         => 0,
											'notification_entry_id'     => $entry->entry_id,
											'notification_type'         => 'Entry Vote',
											'notification_created_date' => date( 'Y-m-d H:i:s' ),
											'notification_updated_date' => date( 'Y-m-d H:i:s' ) ] );
				}
				else
				{

					$subjects = json_decode( $prev_not->notification_subject_ids );

					if( !in_array( $session->token_user_id, $subjects ) )
					{
						array_push( $subjects, $session->token_user_id );

						$prev_not->notification_subject_ids = json_encode( $subjects );
						$prev_not->notification_read = 0;
						$prev_not->notification_updated_date = date( 'Y-m-d H:i:s' );

						$prev_not->save();
					}
				}
			}
			elseif( Input::get( 'type' ) == 'down' )
			{
				$input[ 'vote_down' ] = 1;

				$prev_not = Notification::where( 'notification_user_id', '=', $entry->entry_user_id, 'and' )
										->where( 'notification_entry_id', '=', $entry->entry_id, 'and' )
										->where( 'notification_details', '=', 'has voted down your entry', 'and' )
										->orderBy( 'notification_updated_date', 'desc' )
										->first();

				if( !count( $prev_not ) )
				{
					Notification::create( [ 'notification_user_id'      => $entry->entry_user_id,
											'notification_subject_ids'  => json_encode( [ $session->token_user_id ] ),
											'notification_details'      => 'has voted down your entry',
											'notification_read'         => 0,
											'notification_entry_id'     => $entry->entry_id,
											'notification_type'         => 'Entry Vote',
											'notification_created_date' => date( 'Y-m-d H:i:s' ),
											'notification_updated_date' => date( 'Y-m-d H:i:s' )
										  ] );
				}
				else
				{

					$subjects = json_decode( $prev_not->notification_subject_ids );

					if( !in_array( $session->token_user_id, $subjects ) )
					{
						array_push( $subjects, $session->token_user_id );

						$prev_not->notification_subject_ids = json_encode( $subjects );
						$prev_not->notification_read = 0;
						$prev_not->notification_updated_date = date( 'Y-m-d H:i:s' );

						$prev_not->save();
					}

				}
			}
			else
			{
				return Response::make( [ 'error' => 'Invalid type, allowed types are "up" or "down"' ], 400 );
			}

			Eloquent::unguard();
			$this->vote->create( $input );
			$response[ 'message' ] = "vote added";
			$status_code = 201;
			Eloquent::reguard();
		}

		return Response::make( $response, $status_code );
	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/vote",
	 *   description="Operations about Votes",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="DELETE",
	 *       summary="Remove vote",
	 *       notes="Operation for user to cancel a vote they have previously submitted",
	 *       nickname="getAllVotes",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="entry",
	 *           description="Entry ID that the vote is associated with.",
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
	public function destroy()
	{
		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		//Validate Input
		$rules = array(
			'entry' => 'required|numeric',
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
			$input = [
				'vote_user_id'  => $session->token_user_id,
				'vote_entry_id' => Input::get( 'entry' ),
			];

			//Delete previous votes for this entry cast by this user
			$this->vote->delete_previous( $input );

			$response[ 'message' ] = "vote removed";
			$status_code = 200;

		}

		return Response::make( $response, $status_code );
	}

	public function forMe(){
		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$entries = Entry::where('entry_user_id', '=', $session->token_user_id)->lists('entry_id');

		//Get subCategory
		$type = ( Input::get( 'type', '0' ) );

		if( $type )
		{
			if( $type == "up" )
			{
				$up = true;
				$down = false;
			}
			else
			{
				if( $type == 'down' )
				{
					$down = true;
					$up = false;
				}
			}
		}


		//Get limit to calculate pagination
		$limit = ( Input::get( 'limit', '50' ) );

		//If not numeric set it to the default limit
		$limit = ( !is_numeric( $limit ) || $limit < 1 ) ? 50 : $limit;

		//Get page
		$page = Input::get( 'page', '1' );
		$page = ( !is_numeric( $page ) ) ? 1 : $page;

		//Calculate offset
		$offset = ( $page * $limit ) - $limit;

		//If page is greter than one show a previous link
		if( $page > 1 )
		{
			$previous = true;
		}
		else
		{
			$previous = false;
		}

		$order = Input::get('order', 'date');

		if($order == 'name')
			$orderBy = 'user_name';

		elseif($order == 'date')
			$orderBy = 'vote_created_date';

		$votes = $this->vote->for_entries($entries, $up, $down, $limit, $offset, $order, false);

		$count = $this->vote->for_entries($entries, $up, $down, 0, 0, $order, true);

		$return = [];

		foreach ($votes as $vote)
		{
			$current[ 'id' ] = $vote->vote_id;
			$current[ 'user' ] = oneUser($vote->user, $session,  true);
			$current[ 'entry' ] = oneEntry($vote->entry, $session, true);

			if( $vote[ 'vote_up' ] == 1 && $vote[ 'vote_down' ] == 0 )
			{
				$current[ 'type' ] = "Upvote";
			}
			elseif( $vote[ 'vote_up' ] == 0 && $vote[ 'vote_down' ] == 1 )
			{
				$current[ 'type' ] = "Downvote";
			}
			else
			{
				$current[ 'type' ] = "Error";
			}

			$return[ 'votes' ][ ][ 'vote' ] = $current;
		}

		$response = Response::make( $return, 200 );

		$response->header( 'X-Total-Count', $count );

		return $response;
	}

}