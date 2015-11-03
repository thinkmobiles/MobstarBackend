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
class CommentController extends BaseController
{

	public function __construct( Token $token )
	{
		$this->token = $token;
	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/comment",
	 *   description="Operations about comments",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="Get all comments",
	 *       notes="Returns all available comments",
	 *       nickname="getAllComments",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="user",
	 *           description="User ID whose comments you want to view.",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="entry",
	 *           description="Entry ID of comments you want to view.",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer"
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
		$return = [ ];

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

		//Get entry
		$entry = ( Input::get( 'entry', '0' ) );
		$entry = ( !is_numeric( $entry ) ) ? 0 : $entry;

		$deleted = ( Input::get( 'delted', '0' ) );

		//$comments = Comment::with( 'User', 'Entry' );
		if($user > 0 && $entry == 0)
		{
		$comments = Comment::join('users as u', 'u.user_id', '=', 'comments.comment_user_id')
		   ->orderBy('comments.comment_added_date', 'desc')
		   ->orderBy('u.user_user_group', 'desc')
		   ->select('comments.*')       // just to avoid fetching anything from joined table
		   ->with('User', 'Entry');
		}
		else
		{
			$comments = Comment::join('users as u', 'u.user_id', '=', 'comments.comment_user_id')
			   ->orderBy('u.user_user_group', 'desc')
			   ->select('comments.*')       // just to avoid fetching anything from joined table
			   ->with('User', 'Entry');
		}
		if( $user )
		{
			$comments = $comments->where( 'comment_user_id', '=', $user );
		}

		if( $entry )
		{
			$comments = $comments->where( 'comment_entry_id', '=', $entry );
		}

		if( !$deleted )
		{
			$comments = $comments->where( 'comment_deleted', '=', '0' );
		}

		$comments = $comments->get();
		//dd(DB::getQueryLog());
		$count = $comments->count();

		if( $count == 0 )
		{
			$return = [ 'error' => 'No comments Found' ];
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


		foreach( $comments as $comment )
		{
			$current = array();

			$current[ 'commentId' ] = $comment->comment_id;

			$current[ 'user' ] = oneUser( $comment->User, $session );

//			$entry = new Entry;
//			$current['entry'] = $entry->oneEntry($comment->Entry);

			$current[ 'comment' ] = $comment->comment_content;
			$current[ 'commentDate' ] = $comment->comment_added_date;
			$current[ 'commentDeleted' ] = (bool)$comment->comment_deleted;

			$current['entry'] = \MobStar\ResponseHelper::oneEntryById($comment->Entry->entry_id, $session->token_user_id, true); //oneEntry($comment->Entry, $session, true);

			$return[ 'comments' ][ ][ 'comment' ] = $current;
		}

		$status_code = 200;

		//If next is true create next page link
		if( $next )
		{
			$return[ 'next' ] = "http://".$_ENV['URL']."/comment/?" . http_build_query( [ "limit" => $limit, "page" => $page + 1 ] );
		}

		if( $previous )
		{
			$return[ 'previous' ] = "http://".$_ENV['URL']."/comment/?" . http_build_query( [ "limit" => $limit, "page" => $page - 1 ] );
		}

		$response = Response::make( $return, $status_code );

		$response->header( 'X-Total-Count', $count );

		return $response;
	}

	/**
	 *
	 * @SWG\Api(
	 *    path="/comment/{entryId}",
	 *   description="Operations about comments",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Add comment",
	 *       notes="Operation for user to add a comment to an entry.",
	 *       nickname="addComment",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="comment",
	 *           description="Comment to be added to the entry.",
	 *           paramType="form",
	 *           required=true,
	 *           type="text"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="entryId",
	 *           description="Entry Id.",
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

	public function store( $entry )
	{

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		//Validate Input
		$rules = array(
			'comment' => 'required',
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

			$record = Entry::find( $entry );
			// Added for make entry for push badge count
			$notification_count = 0;
			$input = array(
						'user_id' => $record->entry_user_id,
					);

			$notificationcount = NotificationCount::firstOrNew( $input );
			if( isset( $notificationcount->id ) )
			{
				$notification_count = DB::table('notification_counts')
					->where('user_id','=',$record->entry_user_id)
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
			$prev_not = Notification::where( 'notification_user_id', '=', $record->entry_user_id, 'and' )
									->where( 'notification_entry_id', '=', $record->entry_id, 'and' )
									->where( 'notification_type', '=', 'Entry Comment', 'and' )
									->where( 'notification_details', '=', 'commented on your entry', 'and' )
									->orderBy( 'notification_updated_date', 'desc' )
									->first();

			if( !count( $prev_not ) )
			{
				Notification::create( [ 'notification_user_id'      => $record->entry_user_id,
										'notification_subject_ids'  => json_encode( [ $session->token_user_id ] ),
										'notification_details'      => 'commented on your entry',
										'notification_icon'			=> 'comment.png',
										'notification_read'         => 0,
										'notification_entry_id'     => $record->entry_id,
										'notification_type'         => 'Entry Comment',
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
				if( in_array( $session->token_user_id, $subjects ) )
				{
					$prev_not->notification_read = 0;
					$prev_not->notification_updated_date = date( 'Y-m-d H:i:s' );

					$prev_not->save();
				}
			}

			//Get input
			$input = array(
				'comment_user_id'    => $session->token_user_id,
				'comment_entry_id'   => $entry,
				'comment_added_date' => date( 'Y-m-d H:i:s' ),
				'comment_content'    => str_replace('"', '', Input::get( 'comment' ))
			);

			$comment = Comment::create( $input );
			$to = $record->entry_user_id;
			$fromId = $session->token_user_id;
			$name = getusernamebyid($fromId);
			$icon = 'http://' . $_ENV[ 'URL' ] . '/images/comment.png';
			if(!empty($name))
			{
			    $pushMessage = $name." commented on your entry.";
			    $pushData = array(
			        "badge" => (int) $notification_count,
			        "displayname"=>$name,
			        "Type"=>'Entry Comment',
			        "EntryId"=>$record->entry_id,
			        "notificationIcon"=>$icon,
			    );


			    \MobStar\SnsHelper::sendNotification( $to, $pushMessage, $pushData );
			}

			$response[ 'message' ] = "comment added";
			$status_code = 201;
		}

		return Response::make( $response, $status_code );
	}

	/**
	 *
	 * @SWG\Api(
	 *   description="Operations about Comments",
	 *   path="/comment/{comment}",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="DELETE",
	 *       summary="Remove comment",
	 *       notes="Operation for user to remove a comment",
	 *       nickname="removeComment",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="comment",
	 *           description="The comment ID.",
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
	public function destroy( $id )
	{
		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$comment = Comment::where( 'comment_id', '=', $id )->where( 'comment_user_id', '=', $session->token_user_id )->first();

		if( count( $comment ) == 0 )
		{
			$response[ 'error' ] = "comment not found";
			$status_code = 404;
		}
		else
		{
			$comment->comment_deleted = 1;
			$comment->comment_deleted_by = $session->token_user_id;
			$comment->save();
			$response[ 'message' ] = "comment removed";
			$status_code = 200;
		}

		return Response::make( $response, $status_code );
	}


	// New API for comments with pagination created on 30-June-2015
	public function index2()
	{
		$return = [ ];

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

		//Get entry
		$entry = ( Input::get( 'entry', '0' ) );
		$entry = ( !is_numeric( $entry ) ) ? 0 : $entry;

		$deleted = ( Input::get( 'delted', '0' ) );

		//$comments = Comment::with( 'User', 'Entry' );
		if($user > 0 && $entry == 0)
		{
		$comments = Comment::join('users as u', 'u.user_id', '=', 'comments.comment_user_id')
		   //->groupBy('comments.comment_entry_id')
		   ->orderBy('comments.comment_added_date', 'desc')
		   ->orderBy('u.user_user_group', 'desc')
		   ->select('comments.*')       // just to avoid fetching anything from joined table
		   ->with('User', 'Entry');

		}
		else
		{
			$comments = Comment::join('users as u', 'u.user_id', '=', 'comments.comment_user_id')
			   //->groupBy('comments.comment_entry_id')
			   ->orderBy('u.user_user_group', 'desc')
			   ->select('comments.*')       // just to avoid fetching anything from joined table
			   ->with('User', 'Entry');
		}
		if( $user )
		{
			$comments = $comments->where( 'comment_user_id', '=', $user );
		}

		if( $entry )
		{
			$comments = $comments->where( 'comment_entry_id', '=', $entry );
		}

		if( !$deleted )
		{
			$comments = $comments->where( 'comment_deleted', '=', '0' );
		}
		//$count = $comments->count();
		$count = $comments->get();
		$count = count($count);
		$comments =$comments->take( $limit )->skip( $offset )->get();
		//dd(DB::getQueryLog());


		if( $count == 0 )
		{
			$return = [ 'error' => 'No comments Found' ];
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

		$client = getS3Client();
		foreach( $comments as $comment )
		{
			$current = array();
			$current['entry'][ 'id' ] = $comment->Entry->entry_id;
			$current['entry'][ 'name' ] = $comment->Entry->entry_name;
			$current['entry'][ 'description' ] = $comment->Entry->entry_description;
			$current['entry'][ 'type' ] = $comment->Entry->entry_type;
			$current['entry'][ 'totalComments' ] = $comment->Entry->comments->count();
			$current['entry'][ 'created' ] = $comment->Entry->entry_created_date;
			$current['entry'][ 'entryFiles' ] = array();
			foreach( $comment->Entry->file as $file )
			{
			    $current['entry']['entryFiles'][] = \MobStar\ResponseHelper::entryFile( $file );
			}
			$current['entry']['videoThumb'] = \MobStar\ResponseHelper::entryThumb( $comment->Entry, $comment->Entry->file );

			$return[ 'comments' ][ ][ 'comment' ] = $current;
		}
		$status_code = 200;
		//If next is true create next page link
		if( $next )
		{
			$return[ 'next' ] = "http://".$_ENV['URL']."/comment/?" . http_build_query( [ "limit" => $limit, "page" => $page + 1 ] );
		}

		if( $previous )
		{
			$return[ 'previous' ] = "http://".$_ENV['URL']."/comment/?" . http_build_query( [ "limit" => $limit, "page" => $page - 1 ] );
		}

		$response = Response::make( $return, $status_code );

		$response->header( 'X-Total-Count', $count );

		return $response;
	}
}