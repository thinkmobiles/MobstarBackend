<?php

use MobStar\Storage\Vote\VoteRepository as Vote;
use MobStar\Storage\Token\TokenRepository as Token;
use Swagger\Annotations as SWG;
use Aws\S3\S3Client;
use Aws\Common\Credentials\Credentials as Creds;
use MobStar\ResponseHelper;

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
		$limit = ( Input::get( 'limit', '20' ) );

		//If not numeric set it to the default limit
		$limit = ( !is_numeric( $limit ) || $limit < 1 ) ? 20 : $limit;

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

		$deleted = ( Input::get( 'delted', 0 ) );

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
		    if( $session->token_app_version < 4 )
		    {
		        // @todo this is quick fix to avoid youtube entries for old apps. Need to be rewritten
		        if( $vote->entry->entry_type == 'video_youtube' )
		        {
		            continue;
		        }
		    }

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
					$current[ 'user' ] = oneUser( $vote->user, $session );

				}

				if( in_array( "entry", $fields ) )
				{
					$current[ 'entry' ] = \MobStar\ResponseHelper::oneEntryById( $vote->entry->entry_id, $session->token_user_id, true );
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
				$current[ 'user' ] = oneUser( $vote->user, $session );
				$current[ 'entry' ] = \MobStar\ResponseHelper::oneEntryById($vote->entry->entry_id, $session->token_user_id, true );

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
			$return[ 'next' ] = "http://".$_ENV['URL']."/vote/?" . http_build_query( [ "limit" => $limit, "page" => $page + 1 ] );
		}

		if( $previous )
		{
			$return[ 'previous' ] = "http://".$_ENV['URL']."/vote/?" . http_build_query( [ "limit" => $limit, "page" => $page - 1 ] );
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
		$userid = $session->token_user_id;
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
			// @todo this will only mark votes as deleted. Whether we need them in database, or delete them completely?
			$this->vote->delete_previous( $input );
			$entryType = '';
			$entry = Entry::find( $input[ 'vote_entry_id' ] );
			$entryType = $entry->entry_type;
			if( Input::get( 'type' ) == 'up' )
			{
//				return $session;
				$input[ 'vote_up' ] = 1;
				$msg = 'voted up your '.$entryType;
				$icon = '';
				$notif_Type = 'Vote';
				if( $entry->entry_category_id == 7 || $entry->entry_category_id == 8 )
				{
					$msg = 'liked your '.$entryType;
   				    $icon = 'like.png';
					$notif_Type = 'Like';
					$entryId = $entry->entry_id;
				}
				else
				{
					$msg = 'voted up your '.$entryType;
					$icon = 'voteUp.png';
					$notif_Type = 'Vote';
					$entryId = $entry->entry_id;
				}
				$prev_not = Notification::where( 'notification_user_id', '=', $entry->entry_user_id, 'and' )
										->where( 'notification_entry_id', '=', $entry->entry_id, 'and' )
										->where( 'notification_details', '=', $msg, 'and' )
										->orderBy( 'notification_updated_date', 'desc' )
										->first();

				if( !count( $prev_not ) )
				{
					Notification::create( [ 'notification_user_id'      => $entry->entry_user_id,
											'notification_subject_ids'  => json_encode( [ $session->token_user_id ] ),
											'notification_details'      => $msg,
											'notification_icon'			=> $icon,
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
				$message = '';
				$userid = $session->token_user_id;
				$name = getusernamebyid($userid);
				$to = $entry->entry_user_id;
				// Added for make entry for push badge count
				$notification_count = 0;
				$inputbadge = array(
							'user_id' => $to,
						);

				$notificationcount = NotificationCount::firstOrNew( $inputbadge );
				if( isset( $notificationcount->id ) )
				{
					$notification_count = DB::table('notification_counts')
						->where('user_id','=',$to)
						->pluck( 'notification_count' );
					$notification_count = $notification_count + 1;
					$notificationcount->notification_count = $notification_count;
					$notificationcount->save();
				}
				else
				{
					$notificationcount->notification_count = 1;
					$notificationcount->save();
				}
				// End
				if(!empty($name))
				{
					if($notif_Type == 'Like')
					{
						$message = $name.' just liked your  '.$entryType;
					}
					else
					{
						$message = $name.' just voted for your entry.'; //.$entryType;
					}
					$icon = 'http://' . $_ENV[ 'URL' ] . '/images/' . $icon;

					$pushMessage = $message;
					$pushData = array(
					    "badge"=> (int)$notification_count,
					    "userId"=>$to, // @todo verify that it is right to send self user id to user when someone voted for his entry
					    "diaplayname"=>$name,
					    "Type"=>$notif_Type,
					    "entry_id"=>$entryId,
					    "notificationIcon"=>$icon,
					);

					\MobStar\SnsHelper::sendNotification( $to, $pushMessage, $pushData );
				}
				/* Change Yes vote to follow */
				//Get input
				$inputdata = array(
					'user_star_user_id' => $session->token_user_id,
					'user_star_star_id' => $entry->entry_user_id,
					'user_star_deleted' => 0,
				);

				$star = Star::firstOrNew( $inputdata );
				if( isset( $star->user_star_created_date ) )
				{}
				else
				{
					$star->user_star_created_date = date( 'Y-m-d H:i:s' );
					$star->save();
				}
				/* Change Yes vote to follow End*/
			}
			elseif( Input::get( 'type' ) == 'down' )
			{
				$input[ 'vote_down' ] = 1;
				/*$msg = 'voted down your '.$entryType;
				if( $entry->entry_category_id == 7 || $entry->entry_category_id == 8 )
				{
					$msg = 'unliked your '.$entryType;
				}
				else
				{
					$msg = 'voted down your '.$entryType;
				}
				$prev_not = Notification::where( 'notification_user_id', '=', $entry->entry_user_id, 'and' )
										->where( 'notification_entry_id', '=', $entry->entry_id, 'and' )
										->where( 'notification_details', '=', $msg, 'and' )
										->orderBy( 'notification_updated_date', 'desc' )
										->first();

				if( !count( $prev_not ) )
				{
					Notification::create( [ 'notification_user_id'      => $entry->entry_user_id,
											'notification_subject_ids'  => json_encode( [ $session->token_user_id ] ),
											'notification_details'      => $msg,
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

				}*/
			}
			else
			{
				return Response::make( [ 'error' => 'Invalid type, allowed types are "up" or "down"' ], 400 );
			}

			Eloquent::unguard();
			$userData = DB::table('votes')
					  	->where('vote_up', '=', '1')
					  	->where('vote_user_id', '=',$userid )->count();

			if($userData == 10)
			{
				// Added for make entry for push badge count
				$notification_count = 0;
				$inputbadge = array(
							'user_id' => $userid,
						);

				$notificationcount = NotificationCount::firstOrNew( $inputbadge );
				if( isset( $notificationcount->id ) )
				{
					$notification_count = DB::table('notification_counts')
						->where('user_id','=',$userid)
						->pluck( 'notification_count' );
					$notification_count = $notification_count + 1;
					$notificationcount->notification_count = $notification_count;
					$notificationcount->save();
				}
				else
				{
					$notificationcount->notification_count = 1;
					$notificationcount->save();
				}
				// End
				$message = "You have 10 votes";

				$pushMessage = $message;
				$pushData = array(
				    "badge"=> (int)$notification_count,
				);

				\MobStar\SnsHelper::sendNotification( $userid, $pushMessage, $pushData);
			}
			$this->vote->create( $input );
			$response[ 'message' ] = "vote added";
			$status_code = 201;
			Eloquent::reguard();

			// return entry in response to allow client to update entry info
			$entryId = Input::get('entry');
			$entryInfo = ResponseHelper::oneEntryInfo( $entryId, $session->totken_user_id );
			$response['entries'][]['entry'] = $entryInfo;
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
	    markDead( __METHOD__ );

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

	public function forMe()
	{
		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$user = (Input::get('user', $session->token_user_id));
		$excludeCategory = [7,8];
		//$entries = Entry::where( 'entry_user_id', '=', $user )->lists( 'entry_id' );
		//$entries = Entry::where( 'entry_user_id', '=', $user )->whereNotIn( 'entry_category_id', $excludeCategory )->lists( 'entry_id' );
		$entries = Entry::where( 'entry_user_id', '=', $user )->where( 'entry_deleted', '=', '0' )->whereNotIn( 'entry_category_id', $excludeCategory );

		if( $session->token_app_version < 4 )
		{
		    // @todo this is quick fix to avoid youtube entries for old apps. Need to be rewritten
		    $entries = $entries->where( 'entry_type', '<>', 'video_youtube' );
		}

		$entries = $entries->lists( 'entry_id' );


		if(count($entries) == 0)
		{
			return Response::make( ['info' => 'You do not have any entries'], 300 );
		}
		//Get subCategory
		$type = ( Input::get( 'type', 'up' ) );

		if( $type != "down" )
		{
			$up = true;
			$down = false;
		}
		else
		{
			$down = true;
			$up = false;
		}


		//Get limit to calculate pagination
		$limit = Input::get( 'limit', '20' );

		//If not numeric set it to the default limit
		$limit = ( !is_numeric( $limit ) || $limit < 1 ) ? 20 : $limit;

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

		$order = Input::get( 'order', 'date' );

		if( $order == 'name' )
		{
			$orderBy = 'user_name';
		}

		elseif( $order == 'date' )
		{
			$orderBy = 'vote_created_date';
		}

		$votes = $this->vote->for_entries( $entries, $up, $down, $limit, $offset, $orderBy, false );

		$count = $this->vote->for_entries( $entries, $up, $down, 0, 0, $order, true );

		if($count== 0)
		{
			return Response::make( ['info' => 'You do not have any votes on your entries'], 200 );
		}

		$return = [ ];

		foreach( $votes as $vote )
		{
			$current = [];

				$current[ 'id' ] = $vote->vote_id;

				$current[ 'user' ] = oneUser( $vote->user, $session, false );
				$current[ 'entry' ] = \MobStar\ResponseHelper::oneEntryById( $vote->entry->entry_id, $session->token_user_id, true );
				$current[ 'entry' ]['created'] = $vote['vote_created_date'];
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
	public function likes()
	{
		$client = getS3Client();

		$token = Request::header( "X-API-TOKEN" );
		$session = $this->token->get_session( $token );

		//Get entry
		$entry = ( Input::get( 'entry', '0' ) );
		$entry = ( !is_numeric( $entry ) ) ? 0 : $entry;

		/* Added By AJ */
		if( $entry != 0 )
		{
			$user = DB::table('votes')
                    ->select('vote_user_id')
                    ->groupBy('vote_user_id')
                    ->where('vote_entry_id', '=', $entry)
                    ->where('vote_up', '=', '1')
                    ->where('vote_deleted', '=', '0')
                    ->get();
			$return= array();
			$i = 0;
			if(count($user)>0)
			{
				foreach( $user as $vote )
				{
					$user = User::find( $vote->vote_user_id );
					$return[$i]['userId'] = $user->user_id;
					 if( ( $user->user_display_name == '' ) || ( is_null( $user->user_name ) ) || ( is_null( $user->user_email ) ) )
					 {
						  if( $user->user_facebook_id != 0 )
						  {
						   //$return[$i][ 'userName' ] = $user->FacebookUser->facebook_user_user_name;
						   $return[$i][ 'displayName' ] = $user->FacebookUser->facebook_user_display_name;
						   //$return[$i][ 'fullName' ] = $user->FacebookUser->facebook_user_full_name;
						  }
						  elseif( $user->user_twitter_id != 0 )
						  {
						   //$return[$i][ 'userName' ] = $user->TwitterUser->twitter_user_user_name;
						   $return[$i][ 'displayName' ] = $user->TwitterUser->twitter_user_display_name;
						   //$return[$i][ 'fullName' ] = $user->TwitterUser->twitter_user_full_name;
						  }
						  elseif( $user->user_google_id != 0 )
						  {
						   //$return[$i][ 'userName' ] = $user->GoogleUser->google_user_user_name;
						   $return[$i][ 'displayName' ] = $user->GoogleUser->google_user_display_name;
						   //$return[$i][ 'fullName' ] = $user->GoogleUser->google_user_full_name;
						  }

					 }
					 else
					 {
					  //$return[$i][ 'userName' ] = $user->user_name;
					  $return[$i][ 'displayName' ] = $user->user_display_name;
					  //$return[$i][ 'fullName' ] = $user->user_full_name;

					 }

					 $return[$i]['profileImage'] = ( isset( $user->user_profile_image ) )
							? $client->getObjectUrl( Config::get('app.bucket'), $user->user_profile_image, '+60 minutes' )
							: '';
					 $return[$i]['profileCover'] = ( isset( $user->user_cover_image ) )
							? $client->getObjectUrl( Config::get('app.bucket'), $user->user_cover_image, '+60 minutes' )
							: '';
					$return[ $i ][ 'isMyStar' ] = Star::where( 'user_star_user_id', '=', $session->token_user_id )->where( 'user_star_star_id', '=', $user->user_id )->where( 'user_star_deleted', '=', '0')->count();
				  $i++;
				}
				$status_code = 200;
			}
			else
			{
				$return = [ 'error' => 'No Entries Found' ];
				$status_code = 404;
			}

			//$return[]['fans'] = count($return);


		}
		else
		{
			$return = [ 'error' => 'No Entries Found' ];
			$status_code = 404;
		}
		/* End */
		return Response::make( $return ,$status_code);
	}
}
