<?php

use Swagger\Annotations as SWG;
use MobStar\Storage\Token\TokenRepository as Token;

/**
 * @package
 * @category
 * @subpackage
 *
 * @SWG\Resource(
 *  apiVersion=0.2,
 *  swaggerVersion=1.2,
 *  resourcePath="/notification",
 *  basePath="http://api.mobstar.com"
 * )
 */
class NotificationController extends BaseController
{

	public function __construct( Token $token )
	{
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
	 *   path="/notification/",
	 *   description="Operation about notifications",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="Show all notifications",
	 *       notes="Show all notifications for logged in user.",
	 *       nickname="allNotifications",
	 *       @SWG\Parameters(
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
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function index()
	{

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

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

		//Find total number to put in header
		$count = Notification::where( 'notification_user_id', '=', $session->token_user_id )->where('notification_deleted', '=', 0)->count();
		/*$count = DB::table( 'notifications' )
					->select( 'notifications.*', 'entries.entry_id', 'entries.entry_name' )
					->leftJoin('entries', 'entries.entry_id', '=', 'notifications.notification_entry_id')
					->where( 'notifications.notification_user_id', '=', $session->token_user_id )
					->where( 'notifications.notification_deleted', '=', 0 )
					->where( 'entries.entry_deleted', '=', '0' )
					->count();*/

		//If the count is greater than the highest number of items displayed show a next link
		if( $count > ( $limit * $page ) )
		{
			$next = true;
		}
		else
		{
			$next = false;
		}

		$notifications = Notification::where( 'notification_user_id', '=', $session->token_user_id )->where('notification_deleted', '=', 0)->latest('notification_updated_date')->take( $limit )->skip( $offset )->groupBy('notification_entry_id')->get();
		//$notifications = Notification::where( 'notification_user_id', '=', $session->token_user_id )->where('notification_deleted', '=', 0)->latest('notification_updated_date')->take( $limit )->skip( $offset )->get();
		/*$notifications = DB::table( 'notifications' )
					->select( 'notifications.*', 'entries.entry_id', 'entries.entry_name' )
					->leftJoin('entries', 'entries.entry_id', '=', 'notifications.notification_entry_id')
					->where( 'notifications.notification_user_id', '=', $session->token_user_id )
					->where( 'notifications.notification_deleted', '=', 0 )
					->where( 'entries.entry_deleted', '=', '0' )
					->latest('notifications.notification_updated_date')->take( $limit )->skip( $offset )->get();*/

		$return[ 'notifications' ] = [ ];

		foreach( $notifications as $notification )
		{
			///////
			$type = $notification->notification_type;
			$condition = '';
			$entryIdsArray = array();
			
			if($type == 'Message')
			{
				$condition = @$notification->notification_entry_id;				
			}
			else
			{
				$condition = @$notification->entry->entry_id;
			}
			
			if(!empty(@$condition))
			{
				$current = [ ];

				$subjects = json_decode( $notification->notification_subject_ids, true );

				$subject_count = count( $subjects );

				if( $subject_count > 3 )
				{
					$line = "and " . ( $subject_count - 2 ) . " others have " . trim($notification->notification_details);
				}
				else
				{
					if( $subject_count == 3 )
					{
						$line = "and " . ( $subject_count - 2 ) . " other have " . trim($notification->notification_details);
					}
					elseif( $subject_count == 2 )
					{
						$line = "have " .trim($notification->notification_details);
					}
					else
					{
						//$line = "has " .trim($notification->notification_details);
						if($notification->notification_type == 'Message')
						{
							$line = trim($notification->notification_details);
						}
						else
						{
							$line = "has " .trim($notification->notification_details);
						}
					}
				}

				$name_ids = [ ];

				array_push( $name_ids, array_pop( $subjects ) );

				if( isset( $subjects ) )
				{
					array_push( $name_ids, array_pop( $subjects ) );
				}

				$names = User::whereIn( 'user_id', $name_ids )->get();

				$nameArray = [ ];
				foreach( $names as $name )
				{
					//$nameArray[ ] = $name->user_display_name;
					$nameArray[ ] = getusernamebyid($name->user_id);
				}

				if( $subject_count > 2 )
				{
					$names = implode( ', ', $nameArray );
				}
				else
				{
					$names = implode( ' and ', $nameArray );
				}

				$line = $names. ' ' .$line;
				$icon = ( !empty( $notification->notification_icon ) ) ? 'http://' . $_ENV[ 'URL' ] . '/images/' . $notification->notification_icon : '';
				$current[ 'notificationId' ] = $notification->notification_id;
				$current[ 'notificationContent' ] = $line;
				$current[ 'notificationIcon' ] = $icon;
				if($notification->notification_type == 'Message')
				{
					$current[ 'notificationDate' ] = $notification->notification_created_date;
				}
				else
				{
					$current[ 'notificationDate' ] = $notification->notification_updated_date;	
				}
				$current[ 'notificationRead' ] = ($notification->notification_read == 1);
				if($notification->notification_type == 'Message')
				{
					$current[ 'notificationType' ] = $notification->notification_type;
					$current['entry']['entry_id'] = @$notification->notification_entry_id;
					$user = DB::table('messages')
								->where('message_thread_id','=',$notification->notification_entry_id)
								->orderBy( 'message_created_date', 'desc' )
								->first();
					$userid = $user->message_creator_id;
					$displayname = getusernamebyid($userid);
					$current['entry']['entry_name'] = $displayname;

					/*$countThread = DB::table('join_message_recipients')
								->where('join_message_recipient_thread_id','=',$notification->notification_entry_id)
								->count();*/					
					$message_group = 0;
					$message_group = DB::table('messages')
								->where('message_thread_id','=',$notification->notification_entry_id)
								->pluck( 'message_group' );
								
					/*if($countThread >= 2 )
					{
						$message_group = 1;
					}
					else
					{
						$message_group = 0;
					}*/
					$current[ 'messageGroup' ] = $message_group;
					
				}
				else
				{
					$current[ 'notificationType' ] = $notification->notification_type;
					$current['entry']['entry_id'] = @$notification->entry->entry_id;
					$current['entry']['entry_name'] = @$notification->entry->entry_name;
				}
				/*$current[ 'notificationType' ] = $notification->notification_type;
				//$current['entry']['entry_id'] = @$notification->entry->entry_id;
				$current['entry']['entry_id'] = @$notification->entry_id;
				//$current['entry']['entry_name'] = @$notification->entry->entry_name;
				$current['entry']['entry_name'] = @$notification->entry_name;*/

				$return[ 'notifications' ][] = $current;
			}
			else
			{
				continue;
			}
		}

		$status_code = 200;

		//If next is true create next page link
		if( $next )
		{
			$return[ 'next' ] = "http://api.mobstar.com/notification/?" . http_build_query( [ "limit" => $limit, "page" => $page + 1 ] );
		}

		if( $previous )
		{
			$return[ 'previous' ] = "http://api.mobstar.com/notification/?" . http_build_query( [ "limit" => $limit, "page" => $page - 1 ] );
		}

		$response = Response::make( $return, $status_code );

		$response->header( 'X-Total-Count', $count );

		return $response;
	}

	/**
 *
 * @SWG\Api(
 *   path="/notification/count",
 *   description="Operation about notifications",
 *   @SWG\Operations(
 *     @SWG\Operation(
 *       method="GET",
 *       summary="Get the total number of unread notifications for a user",
 *       notes="Show notification count.",
 *       nickname="allNotifications",
 *       @SWG\ResponseMessages(
 *          @SWG\ResponseMessage(
 *            code=401,
 *            message="Authorization failed"
 *          )
 *       )
 *     )
 *   )
 * )
 */

	public function count()
	{

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		//Find total number to put in header
		/*$count = Notification::where( 'notification_user_id', '=', $session->token_user_id )
			->where('notification_read', '=', 0)
			->where('notification_deleted', '=', 0)
			->count();*/
		$count = DB::table( 'notifications' )
					->select( 'notifications.*', 'entries.entry_id', 'entries.entry_name' )
					->leftJoin('entries', 'entries.entry_id', '=', 'notifications.notification_entry_id')
					->where( 'notifications.notification_user_id', '=', $session->token_user_id )
					->where('notifications.notification_read', '=', 0)
					->where( 'notifications.notification_deleted', '=', 0 )
					->where( 'entries.entry_deleted', '=', '0' )
					->count();	

		$return[ 'notifications' ]= $count;

		$status_code = 200;

		$response = Response::make( $return, $status_code );

		$response->header( 'X-Total-Count', $count );

		return $response;
	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/notification/",
	 *   description="Operation about notifications",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="DELETE",
	 *       summary="Remove notification",
	 *       notes="Operation for user to remove a notification from their list ",
	 *       nickname="removeNotification",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="notification",
	 *           description="The notificaiton ID.",
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

	public function delete($id)
	{
		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$notification = Notification::where('notification_id', '=', $id)->where('notification_user_id', '=', $session->token_user_id)->first();
		if(!is_null($notification))
		{
			$notification->notification_deleted = 1;

			$notification->save();

			$response =Response::make( ['info' => 'notification deleted'], 200);
		}
		else
			$response =Response::make( ['info' => 'notification not found'], 404);

		return $response;
	}
	
	/**
	*
	* @SWG\Api(
	*   path="/notification/markread",
	*   description="Operations about notification",
	*   @SWG\Operations(
	*     @SWG\Operation(
	*       method="POST",
	*       summary="Mark notifcation read",
	*       notes="Read all resulted notification.",
	*       nickname="markreadNotifications",
	*       @SWG\Parameters(
	*         @SWG\Parameter(
	*           name="notificationIds",
	*           description="ID or IDs of required notifications.",
	*           paramType="query",
	*           required=true,
	*           type="comma seperated list"
	*         )
	*       ),
	*       @SWG\ResponseMessages(
	*          @SWG\ResponseMessage(
	*            code=401,
	*            message="Authorization failed"
	*          ),
	*          @SWG\ResponseMessage(
	*            code=404,
	*            message="No users found"
	*          )
	*        )
	*       )
	*     )
	*   )
	* )
	*/
	public function markread( )
	{

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		//Validate Input
		$rules = array(
			'notificationIds'    => 'required',			
		);
		$messages = array(			
		);

		$validator = Validator::make( Input::all(), $rules, $messages );

		// process the login
		if( $validator->fails() )
		{

			$return = $validator->messages();

			$response = Response::make( $return, 400 );

			return $response;
		}
		else
		{
			// get ids
			$id_commas = Input::get( 'notificationIds' );
			$id =  explode( ',', $id_commas );
			for( $i=0; $i<count($id); $i++ )
			{
				$notification = Notification::where('notification_id', '=', $id[$i])->where('notification_user_id', '=', $session->token_user_id)->first();
				if(!is_null($notification))
				{
					$notification->notification_read = 1;
					$notification->save();
				}
				else
				{
					continue;
				}			
			}
			$response[ 'message' ] = "Notification read successfully";
			$status_code = 201;
		}

		return Response::make( $response, $status_code );	
	}
}