<?php

use MobStar\Storage\Message2\Message2Repository as Message;
use MobStar\Storage\Entry\EntryRepository as Entry;
use MobStar\Storage\Token\TokenRepository as Token;
use Swagger\Annotations as SWG;
use Aws\Sns\SnsClient;
use Aws\Common\Credentials\Credentials as Creds;

/**
 * @package
 * @category
 * @subpackage
 *
 * @SWG\Resource(
 *  apiVersion=0.2,
 *  swaggerVersion=1.2,
 *  resourcePath="/message",
 *  basePath="http://api.mobstar.com"
 * )
 */
class Message2Controller extends BaseController
{

	public $valid_fields = [ "id", "sender", "recipient", "body", "date" ];

	public function __construct( Message $message, Token $token, Entry $entry )
	{
		$this->message = $message;
		$this->token = $token;
		$this->entry = $entry;
	}
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	/**
	 *
	 * @SWG\Api(
	 *   path="/message/",
	 *   description="Operations about messages/message thread",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="View all messages",
	 *       notes="This operation will return all message threads for a user logged in when the thread parameter is not sent, if the thread parameter is sent it will return this thread.",
	 *       nickname="allMessages",
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
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=404,
	 *            message="No messages found"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */
	public function index()
	{
		/* Commented On 13-04-2015 
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
		*/
		//Get current user
		$token = Request::header( "X-API-TOKEN" );
		$session = $this->token->get_session( $token );

		$deleted = 0;

		//Get users threads
		//$messages = $this->message->get_message_thread_new( $session[ 'token_user_id' ], $deleted, $limit, $offset, false );
		$messages = $this->message->get_message_thread_new( $session[ 'token_user_id' ], $deleted, false );
		//var_dump($messages);
		//break;

		$count = ( count( $messages ) );

		$return = [ ];

		foreach( $messages as $message )
		{

			$current = array();

			$current[ 'threadId' ] = $message->message_thread_thread_id;

			$last = -1;
			$msgread = '0';
			foreach( $message->messageRecipients as $received )
			{
				if($received->join_message_recipient_user_id != $session->token_user_id)
				{
					$msgread = $received->join_message_recipient_read;
					$newlastMessage = $received;
				}
				if( $received->join_message_recipient_created > $last )
				{
					$lastMessage = $received;
				}
				/*if($received->join_message_recipient_user_id == $session->token_user_id)
				{
					$msgs = MessageRecipients::where( 'join_message_recipient_id', '=',$received->join_message_recipient_user_id)
					->where('join_message_recipient_thread_id', '=', $message->message_thread_thread_id )
					->where('join_message_recipient_user_id','=',$session->token_user_id)->first();
					if( $msgs )
					{
						$msgs->join_message_recipient_read = '1';
						$msgs->join_message_recipient_read_date = date( "Y-m-d H:i:s" );
						$msgs->save();
					}					
				}*/
			}
			$user = User::find( $newlastMessage->join_message_recipient_user_id);			
			$current[ 'lastMessage' ][ 'messageContent' ] = $lastMessage->message->message_body;
			$current[ 'lastMessage' ][ 'messageSender' ] = oneUser( $user, $session );
			$current[ 'lastMessage' ][ 'messageReceived' ] = $lastMessage->message->message_created_date;
			$current[ 'lastMessage' ][ 'messageGroup' ] = $lastMessage->message->message_group;

			$msgread = MessageRecipients::where('join_message_recipient_user_id','=',$session->token_user_id)
								->where('join_message_recipient_thread_id','=',$message->message_thread_thread_id)
								->where('join_message_recipient_message_id','=',$lastMessage->message->message_id)
								->pluck( 'join_message_recipient_read' );

			//$current[ 'read' ] = $lastMessage->join_message_recipient_read;
			if(is_null($msgread))
			$msgread = 0;
			$current[ 'read' ] = $msgread;
			
			$current[ 'participants' ] = [ ];

			/*foreach( $message->messageParticipants as $participant )
			{
				if( $participant->user->user_id == $session->token_user_id )
				{
					continue;
				}
				$current[ 'participants' ][ ] = oneUser( $participant->user, $session, false );
			}*/
			//dd(DB::getQueryLog());
			$return[ 'threads' ][ ][ 'thread' ] = $current;
		}

		$status_code = 200;
		/* Commented On 13-04-2015
		//If the count is greater than the highest number of items displayed show a next link
		if( $count > ( $limit * $page ) )
		{
			$next = true;
		}
		else
		{
			$next = false;
		}

		//If next is true create next page link
		if( $next )
		{
			$return[ 'next' ] = "http://api.mobstar.com/message/?" . http_build_query( [ "limit" => $limit, "page" => $page + 1 ] );
		}

		if( $previous )
		{
			$return[ 'previous' ] = "http://api.mobstar.com/message/?" . http_build_query( [ "limit" => $limit, "page" => $page - 1 ] );
		}
		*/
		$response = Response::make( $return, $status_code );

		$response->header( 'X-Total-Count', $count );

		return $response;
	}
	/**
	 *
	 * @SWG\Api(
	 *   path="/message/{thread}",
	 *   description="Operations about messages/message thread",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="View all messages",
	 *       notes="This operation will return a thread and all messages the logged in user has received on this thread",
	 *       nickname="thread",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="thread",
	 *           description="Thread of messages you want to view",
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
	 *            code=404,
	 *            message="No messages found"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */
	public function show( $thread )
	{

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

		//Get current user
		$token = Request::header( "X-API-TOKEN" );
		$session = $this->token->get_session( $token );

		$deleted = 0;

		//Get users threads
		$thread = $this->message->get_message_thread_new( $session[ 'token_user_id' ], $thread, $deleted, $limit, $offset, false );

		$return = [ ];

		$current = array();

		$current[ 'threadId' ] = $thread->message_thread_thread_id;

		$receivedMessages = [ ];
		$tmp_check_msgId = [ ];
		foreach( $thread->messageRecipients as $received )
		{
			//if( $received->join_message_recipient_user_id == $session->token_user_id )
			//{
			if( !in_array( $received->join_message_recipient_message_id, $tmp_check_msgId ) )
			{
				$tmp_check_msgId[ ]  = $received->message->message_id;
				$receivedMessages[ ] = [
					'message_id'      => $received->message->message_id,
					'message'         => $received->message->message_body,
					'messageSender'   => oneUser( $received->message->sender, $session ),
					'messageReceived' => $received->message->message_created_date,
					'messageGroup'    => $received->message->message_group,
					'messageRead'     => $received->join_message_recipient_read
				];
			}
			//}
		}
		$dsort = array();
		foreach ($receivedMessages as $key => $row)
		{
			$dsort[$key] = $row['messageReceived'];
		}
		array_multisort($dsort, SORT_ASC, $receivedMessages);
		
		$current[ 'messages' ] = $receivedMessages;

		$current[ 'participants' ] = [ ];

		/*foreach( $thread->messageParticipants as $participant )
		{
			if( $participant->user->user_id == $session->token_user_id )
			{
				continue;
			}
			$current[ 'participants' ][ ] = oneUser( $participant->user,$session, false );
		}*/

		$return[ 'thread' ] = $current;

		$status_code = 200;

			//If the count is greater than the highest number of items displayed show a next link
//		if( $count > ( $limit * $page ) )
//		{
//		$next = true;
//		}
//
//		else
//		{
//			$next = false;
//		}
//
//		//If next is true create next page link
//		if( $next )
//		{
//			$return[ 'next' ] = "http://api.mobstar.com/message/?" . http_build_query( [ "limit" => $limit, "page" => $page + 1 ] );
//		}
//
//		if( $previous )
//		{
//			$return[ 'previous' ] = "http://api.mobstar.com/message/?" . http_build_query( [ "limit" => $limit, "page" => $page - 1 ] );
//		}
//
		$response = Response::make( $return, $status_code );

//		$response->header( 'X-Total-Count', $count );

		return $response;
	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/message/",
	 *   description="Operations about messages/message thread",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Send a message",
	 *       notes="This operation will send a message from the logged in user",
	 *       nickname="sendMessages",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="recipients",
	 *           description="Comma seperated list of user ID's who are to receive the message",
	 *           paramType="query",
	 *           required=true,
	 *           type="comma seperated list"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="message",
	 *           description="The message body",
	 *           paramType="query",
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
	 *            code=404,
	 *            message="No messages found"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */
public function store()
{

	//Validate Input
	$rules = array(
		'recipients' => 'required',
		'message'    => 'required',
	);

	$validator = Validator::make( Input::get(), $rules );

	if( $validator->fails() )
	{
		$response[ 'errors' ] = $validator->messages();
		$status_code = 400;
	}
	else
	{
		$recipients = Input::get( 'recipients' );
		$message = Input::get( 'message' );

		//Get current user
		$token = Request::header( "X-API-TOKEN" );
		$session = $this->token->get_session( $token );

		$recipients = array_values( explode( ',', $recipients ) );
		if(count($recipients) > 1) 
			$message_group = 1;
		else
			$message_group = 0;
		$recipArray = [ ];
		$particArray = [ ];
		
		$newThread = '';
		if($message_group == 0)
		{
			$thread_id = DB::table('join_message_participants')
						->whereIn('join_message_participant_user_id', array($session->token_user_id, $recipients[0]))
						->pluck('join_message_participant_message_thread_id');
			if(empty($thread_id))
			{
				$messageThread = MessageThread::create( [ 'message_thread_created_date' => date( 'Y-m-d H:i:s' ),'message_thread_created_by' => $session->token_user_id ] );
				$newThread = $messageThread->message_thread_thread_id;
			}
			else
			{
				$totalCount = DB::table('join_message_participants')
							->where('join_message_participant_message_thread_id','=',$thread_id,'and')
							->whereNotIn('join_message_participant_user_id',array($session->token_user_id, $recipients[0]))
							->count('join_message_participant_id');
				
				if($totalCount == 0)
				{
					$newThread = $thread_id;
				}
				elseif($totalCount > 0)
				{
					$messageThread = MessageThread::create( [ 'message_thread_created_date' => date( 'Y-m-d H:i:s' ),'message_thread_created_by' => $session->token_user_id ] );
					$newThread = $messageThread->message_thread_thread_id;
				}
			}
		}
		else
		{
			$messageThread = MessageThread::create( [ 'message_thread_created_date' => date( 'Y-m-d H:i:s' ),'message_thread_created_by' => $session->token_user_id ] );
			$newThread = $messageThread->message_thread_thread_id;
		}
		
		//$messageThread = MessageThread::create( [ 'message_thread_created_date' => date( 'Y-m-d H:i:s' ) ] );
		
		$messageOb = Message2::create(
							 [
								 'message_creator_id'   => $session->token_user_id,
								 //'message_thread_id'    => $messageThread->message_thread_thread_id,
								 'message_thread_id'    => $newThread,
								 'message_body'         => $message,
								 'message_created_date' => date( 'Y-m-d H:i:s' ),
								 'message_group'        => $message_group
							 ]
		);
		$userid = $session->token_user_id;
		$name = getusernamebyid($userid);
		$msg = $name.' messaged you.';
		//$threadid = $messageThread->message_thread_thread_id;
		$threadid = $newThread;
		$icon = '';
		foreach( $recipients as $recipient )
		{

			$particArray [ ] = [
				//'join_message_participant_message_thread_id' => $messageThread->message_thread_thread_id,
				'join_message_participant_message_thread_id' => $newThread,
				'join_message_participant_user_id'           => $recipient,
			];

			$recipArray [ ] = [
				//'join_message_recipient_thread_id'  => $messageThread->message_thread_thread_id,
				'join_message_recipient_thread_id'  => $newThread,
				'join_message_recipient_user_id'    => $recipient,
				'join_message_recipient_message_id' => (int)$messageOb->message_id,
				'join_message_recipient_created'    => 0,
				'join_message_recipient_read'       => 0,
			];
			$prev_not = Notification::where( 'notification_user_id', '=', $recipient, 'and' )
									//->where( 'notification_entry_id', '=', $messageThread->message_thread_thread_id, 'and' )
									->where( 'notification_entry_id', '=', $newThread, 'and' )
									->where( 'notification_details', '=', ' message you.', 'and' )
									->orderBy( 'notification_updated_date', 'desc' )
									->first();
			$icon = 'message.png';
			if( !count( $prev_not ) )
			{
				Notification::create( [ 'notification_user_id'      => $recipient,
										'notification_subject_ids'  => json_encode( [ $session->token_user_id ] ),
										'notification_details'      => ' messaged you.',
										'notification_icon'			=> $icon,
										'notification_read'         => 0,
										//'notification_entry_id'     => $messageThread->message_thread_thread_id,
										'notification_entry_id'     => $newThread,
										'notification_type'         => 'Message',
										'notification_created_date' => date( 'Y-m-d H:i:s' ),
										'notification_updated_date' => '0000-00-00 00:00:00' ] );
			}
			else
			{
				/*$subjects = json_decode( $prev_not->notification_subject_ids );

				if( !in_array( $session->token_user_id, $subjects ) )
				{
					array_push( $subjects, $session->token_user_id );

					$prev_not->notification_subject_ids = json_encode( $subjects );
					$prev_not->notification_read = 0;
					$prev_not->notification_updated_date = date( 'Y-m-d H:i:s' );

					$prev_not->save();
				}*/
				$subjects = json_decode( $prev_not->notification_subject_ids );

				if( !in_array( $session->token_user_id, $subjects ) )
				{
					array_push( $subjects, $session->token_user_id );

					$prev_not->notification_subject_ids = json_encode( $subjects );
					$prev_not->notification_read = 0;
					$prev_not->notification_updated_date = date( 'Y-m-d H:i:s' );

					$prev_not->save();
				}
				else
				{
					$prev_not->notification_read = 0;
					$prev_not->notification_updated_date = date( 'Y-m-d H:i:s' );
					
					$prev_not->save();
				}
			}
			if(!empty($name))
			{
				$message = $msg;
				$icon = 'http://' . $_ENV[ 'URL' ] . '/images/message.png';
				$usersDeviceData = DB::select( DB::raw("SELECT t1.* FROM 
					(select device_registration_id,device_registration_device_type,device_registration_device_token,device_registration_date_created,device_registration_user_id 
					from device_registrations where device_registration_device_token  != '' 
					order by device_registration_date_created desc
					) t1 left join users u on t1.device_registration_user_id = u.user_id 
					where u.user_deleted = 0 
					AND u.user_id = $recipient
					group by u.user_id 
					order by t1.device_registration_date_created desc"));

				if(!empty($usersDeviceData))
				{	
					//$this->registerSNSEndpoint($usersDeviceData[0], $message, $message_group, $name, $icon, $threadid);
				}
			}

		}

		array_push( $particArray, [
			//'join_message_participant_message_thread_id' => $messageThread->message_thread_thread_id,
			'join_message_participant_message_thread_id' => $newThread,
			'join_message_participant_user_id'           => $session->token_user_id,
		] );

		array_push( $recipArray, [
			//'join_message_recipient_thread_id'  => $messageThread->message_thread_thread_id,
			'join_message_recipient_thread_id'  => $newThread,
			'join_message_recipient_user_id'    => $session->token_user_id,
			'join_message_recipient_message_id' => $messageOb->message_id,
			'join_message_recipient_created'    => 1,
			'join_message_recipient_read'       => 1
		] );
		MessageParticipants::insert( $particArray );
		MessageRecipients::insert( $recipArray );
	}
}

	/**
	 *
	 * @SWG\Api(
	 *   path="/message/reply",
	 *   description="Operations about messages/message thread",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Send a message",
	 *       notes="This operation will reply to the specified thread from the logged in user",
	 *       nickname="sendMessages",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="thread",
	 *           description="Thread ID to be replied to",
	 *           paramType="query",
	 *           required=true,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="message",
	 *           description="The message body",
	 *           paramType="query",
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
	 *            code=404,
	 *            message="No messages found"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */

public function reply()
{

	//Validate Input
	$rules = array(
		'thread'  => 'required|exists:message_threads,message_thread_thread_id',
		'message' => 'required',
	);

	$validator = Validator::make( Input::get(), $rules );

	if( $validator->fails() )
	{
		$response[ 'errors' ] = $validator->messages();
		$status_code = 400;
	}
	else
	{
		$thread = Input::get( 'thread' );
		$message = Input::get( 'message' );

		//Get current user
		$token = Request::header( "X-API-TOKEN" );
		$session = $this->token->get_session( $token );

		//$recipients = MessageParticipants::where( 'join_message_participant_message_thread_id', $thread );
   	    //$recipients = MessageParticipants::where('join_message_participant_message_thread_id','=',$thread)->groupBy('join_message_participant_user_id')->get();
		$recipients = MessageParticipants::where('join_message_participant_message_thread_id','=',$thread)->groupBy('join_message_participant_user_id')->get();

		$message_group = 0;
		$messagegroup = DB::table('messages')
					->where('message_thread_id','=',$thread)
					->pluck( 'message_group' );
		if(!empty($messagegroup))
		{
			$message_group = $messagegroup;
		}
		else
		{
			if(count($recipients) > 3) 
				$message_group = 1;
			else
				$message_group = 0;
		}
		$recipArray = [ ];
		$particArray = [ ];
		/*if(count($recipients) > 1) 
			$message_group = 1;
		else
			$message_group = 0;*/
		$messageOb = Message2::create(
							 [
								 'message_creator_id'   => $session->token_user_id,
								 'message_thread_id'    => $thread,
								 'message_body'         => $message,
								 'message_created_date' => date( 'Y-m-d H:i:s' ),
								 'message_group'		=> $message_group
							 ]
		);
		
		DB::table('message_threads')
            ->where('message_thread_thread_id', $thread )
            ->update(array('message_thread_created_date' => date( 'Y-m-d H:i:s' )));
			
		$userid = $session->token_user_id;
		$name = getusernamebyid($userid);
		$msg = $name.' messaged you.';	
		$threadid = $thread;
		$icon = '';	
		foreach( $recipients as $recipient )
		{
			if( $recipient->join_message_participant_user_id == $session->token_user_id )
			{
				continue;
			}

			$particArray [ ] = [
				'join_message_participant_message_thread_id' => $thread,
				'join_message_participant_user_id'           => $recipient->join_message_participant_user_id,
			];

			$recipArray [ ] = [
				'join_message_recipient_thread_id'  => $thread,
				'join_message_recipient_user_id'    => $recipient->join_message_participant_user_id,
				'join_message_recipient_message_id' => $messageOb->message_id,
				'join_message_recipient_created'    => 0,
				'join_message_recipient_read'       => 0,
			];
			$prev_not = Notification::where( 'notification_user_id', '=', $recipient->join_message_participant_user_id, 'and' )
									->where( 'notification_subject_ids', '=', json_encode( [ $session->token_user_id ] ), 'and' )
									->where( 'notification_entry_id', '=', $thread, 'and' )
									->where( 'notification_type', '=', 'Message', 'and' )
									->orderBy( 'notification_updated_date', 'desc' )
									->first();
			$icon = 'message.png';
			if( !count( $prev_not ) )
			{
				Notification::create( [ 'notification_user_id'      => $recipient->join_message_participant_user_id,
											'notification_subject_ids'  => json_encode( [ $session->token_user_id ] ),
											'notification_details'      => ' messaged you.',
											'notification_icon'			=> $icon,
											'notification_read'         => 0,
											'notification_entry_id'     => $thread,
											'notification_type'         => 'Message',
											'notification_created_date' => date( 'Y-m-d H:i:s' ),
											'notification_updated_date' => '0000-00-00 00:00:00' ] );
			}
			else
			{	
				$subjects = json_decode( $prev_not->notification_subject_ids );
				
				if( !in_array( $session->token_user_id, $subjects ) )
				{
					array_push( $subjects, $session->token_user_id );
					$prev_not->notification_subject_ids = json_encode( $subjects );
				}
				$prev_not->notification_read = 0;
				$prev_not->notification_updated_date = date( 'Y-m-d H:i:s' );				
				$prev_not->save();				
			}
		}

		array_push( $particArray, [
			'join_message_participant_message_thread_id' => $thread,
			'join_message_participant_user_id'           => $session->token_user_id,
		] );

		array_push( $recipArray, [
			'join_message_recipient_thread_id'  => $thread,
			'join_message_recipient_user_id'    => $session->token_user_id,
			'join_message_recipient_message_id' => $messageOb->message_id,
			'join_message_recipient_created'    => 1,
			'join_message_recipient_read'       => 1
		] );

		MessageParticipants::insert( $particArray );

		MessageRecipients::insert( $recipArray );
		if(!empty($recipArray))
		{
			$icon = 'http://' . $_ENV[ 'URL' ] . '/images/message.png';
			for($i=0; $i<count($recipArray);$i++)
			{	
				$u = $recipArray[$i]['join_message_recipient_user_id'];
				$message = $msg;
				if($u != $session->token_user_id)
				{
					$usersData = DB::select( DB::raw("SELECT t1.* FROM 
								(select device_registration_id,device_registration_device_type,device_registration_device_token,device_registration_date_created,device_registration_user_id 
								from device_registrations where device_registration_device_token  != '' AND device_registration_device_token != 'mobstar' AND device_registration_device_type = 'apple'
								order by device_registration_date_created desc
								) t1 left join users u on t1.device_registration_user_id = u.user_id 
								where u.user_deleted = 0 
								AND u.user_id = $u
								group by u.user_id 
								order by t1.device_registration_date_created desc"));

					if(!empty($usersData))
					{	
							//$this->registerSNSEndpoint($usersData[0], $message, $message_group, $name, $icon, $threadid);
					}
				}
			}
		}
	}
}

	/**
	 *
	 * @SWG\Api(
	 *   path="/message/bulk",
	 *   description="Operations about messages/message thread",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Send a bulk message to multiple users, by voters or commenters for entry id, or by all users who starred logged in user",
	 *       notes="This operation will send a bulk message to users specified",
	 *       nickname="sendMessages",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="type",
	 *           description="Type of bulk message, set it to voters, allVoters, commenters, or starred",
	 *           paramType="query",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="entry",
	 *           description="Entry Id for voters or commenters",
	 *           paramType="query",
	 *           required=true,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="message",
	 *           description="The message body",
	 *           paramType="query",
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
	 *            code=404,
	 *            message="No messages found"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function bulk()
	{
		$token = Request::header( "X-API-TOKEN" );
		$session = $this->token->get_session( $token );

		//Validate Input
		$rules = array(
			'type'  => 'required|in:commenters,voters,starred,allVoters',
			'message' => 'required',
		);

		$validator = Validator::make( Input::get(), $rules );

		if( $validator->fails() )
		{
			$response[ 'errors' ] = $validator->messages();
			$status_code = 400;
		}
		else
		{
			$input = Input::get();
			if(($input['type'] == "voters" || $input['type'] == "commenters") && (!isset($input['entry']) || $input['entry'] == ''))
			{
				$response['errors'][] = "You did not specify an entry";
				$status_code = 400;
			}
			else{
				switch($input['type'])
				{
					case "voters":
						//Get all voters for an entry
						$entry = $this->entry->find($input['entry']);

						$users = [];

						foreach ($entry->vote as $vote)
						{
							$users[] = $vote->vote_user_id;
						}
						break;

					case "commenters":
						$entry = $this->entry->find($input['entry']);

						$users = [];

						foreach($entry->comments as $comment)
						{
							$users[] = $comment->user_id;
						}
						break;

					case "starred":
						$stars = Star::where('user_star_star_id', '=', $session->token_user_id)
							->where('user_star_deleted', '=', 0)->get();

						$users = [];

						foreach($stars as $star)
						{
							$users[] = $star->user_star_user_id;
						}
						break;

					case "allVoters":
						$entries = $this->entry->all($session->token_user_id, 0, 0, 0, 0, 'desc', 1000000, 0);
						$users = [];
						foreach($entries as $entry)
						{
							foreach ($entry->vote as $vote)
							{
								$users[] = $vote->vote_user_id;
							}
						}
						$users = array_unique($users);
						break;
				}

				foreach ($users as $user)
				{
					$recipients = [$user];
					$message = $input['message'];

					$recipArray = [ ];
					$particArray = [ ];

					$messageThread = MessageThread::create( [ 'message_thread_created_date' => date( 'Y-m-d H:i:s' ) ] );

					$messageOb = Message2::create(
						[
							'message_creator_id'   => $session->token_user_id,
							'message_thread_id'    => $messageThread->message_thread_thread_id,
							'message_body'         => $message,
							'message_created_date' => date( 'Y-m-d H:i:s' ),
							'message_group'		   => 0
						]
					);

					foreach( $recipients as $recipient )
					{

						$particArray [ ] = [
							'join_message_participant_message_thread_id' => $messageThread->message_thread_thread_id,
							'join_message_participant_user_id'           => $recipient,
						];

						$recipArray [ ] = [
							'join_message_recipient_thread_id'  => $messageThread->message_thread_thread_id,
							'join_message_recipient_user_id'    => $recipient,
							'join_message_recipient_message_id' => (int)$messageOb->message_id,
							'join_message_recipient_created'    => 0,
							'join_message_recipient_read'       => 0,
						];

					}
					if($input['type'] != 'allVoters' && $input['type'] != 'starred' && $input['type'] != 'voters' && $input['type'] != 'commenters')
					{
						array_push( $particArray, [
							'join_message_participant_message_thread_id' => $messageThread->message_thread_thread_id,
							'join_message_participant_user_id'           => $session->token_user_id,
						] );

						array_push( $recipArray, [
							'join_message_recipient_thread_id'  => $messageThread->message_thread_thread_id,
							'join_message_recipient_user_id'    => $session->token_user_id,
							'join_message_recipient_message_id' => $messageOb->message_id,
							'join_message_recipient_created'    => 1,
							'join_message_recipient_read'       => 1
						] );
					}
					MessageParticipants::insert( $particArray );

					MessageRecipients::insert( $recipArray );
					/*if(!empty($recipArray))
					{
						for($i=0; $i<count($recipArray);$i++)
						{	
							$u = $recipArray[$i]['join_message_recipient_user_id'];
							if($u != $session->token_user_id)
							{
								$usersData = DB::select( DB::raw("SELECT t1.* FROM 
											(select device_registration_id,device_registration_device_type,device_registration_device_token,device_registration_date_created,device_registration_user_id 
											from device_registrations where device_registration_device_token  != '' AND device_registration_device_token != 'mobstar' AND device_registration_device_type = 'apple'
											order by device_registration_date_created desc
											) t1 left join users u on t1.device_registration_user_id = u.user_id 
											where u.user_deleted = 0 
											AND u.user_id = $u
											group by u.user_id 
											order by t1.device_registration_date_created desc"));

								if(!empty($usersData))
								{	
										$this->registerSNSEndpoint($usersData[0]);
								}
							}
						}
					}*/
				}

				$response['info'] = "Message sent successfully to " . count($users) . " users";
				$status_code = 201;
			}
		}

		return Response::make($response, $status_code);
	}
	public function deleteThread()
	{
		$rules = array(
			'threadId'  => 'required|exists:message_threads,message_thread_thread_id',
		);

		$validator = Validator::make( Input::get(), $rules );
		
		if( $validator->fails() )
		{
			$response[ 'errors' ] = $validator->messages();
			$status_code = 400;
		}
		else
		{
			$threadId = Input::get( 'threadId' );

			//Get current user
			$token = Request::header( "X-API-TOKEN" );
			$session = $this->token->get_session( $token );

			$threads = Message2::where( 'message_thread_id','=', $threadId )
			->where('message_deleted','=',0)->get();

			foreach( $threads as $thread )
			{
				if( $thread->message_deleted == 1 )
				{
					continue;
				}
				else
				{
					$thread->message_deleted = 1;

					$thread->save();
				}
			}

			$messageRecipientThread = MessageRecipients::where( 'join_message_recipient_thread_id','=', $threadId )
			->get();
			foreach ($messageRecipientThread as $thread) 
			{
				$thread->delete();
			}

			$messageParticipantThread = MessageParticipants::where( 'join_message_participant_message_thread_id','=', $threadId )
			->get();
			foreach ($messageParticipantThread as $thread) 
			{
				$thread->delete();
			}

			$messagesThread = Message2::where( 'message_thread_id','=', $threadId )
			->where('message_deleted','=',1)->get();
			foreach ($messagesThread as $thread) 
			{
				$thread->delete();
			}

			$threadToDelete = MessageThread::where('message_thread_thread_id','=',$threadId)
			->get();
			foreach ($threadToDelete as $thread) 
			{
				$thread->delete();
			}

			
			$response['message'] = "Thread deleted successfully.";
			$status_code = 200;
		}

		return Response::make($response, $status_code);
	}
	public function msgcount()
	{
		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );		
		$count = DB::table( 'join_message_recipients' )
					->select( 'join_message_recipients.*' )
					->where( 'join_message_recipients.join_message_recipient_user_id', '=', $session->token_user_id )
					->where('join_message_recipients.join_message_recipient_read', '=', 0)
					->count();	
		$return[ 'notifications' ]= $count;

		$status_code = 200;

		$response = Response::make( $return, $status_code );

		$response->header( 'X-Total-Count', $count );

		return $response;
	}
	public function read()
	{
		$rules = array(
			'threadId'  => 'required|exists:message_threads,message_thread_thread_id',
		);
		$validator = Validator::make( Input::get(), $rules );

		if( $validator->fails() )
		{
			$response[ 'errors' ] = $validator->messages();
			$status_code = 400;
		}
		else
		{
			$threadId = Input::get( 'threadId' );

			//Get current user
			$token = Request::header( "X-API-TOKEN" );
			$session = $this->token->get_session( $token );

			
			$messageToRead = MessageRecipients::where('join_message_recipient_user_id','=',$session->token_user_id)
								->where('join_message_recipient_thread_id','=',$threadId)
								->get();
			foreach ($messageToRead as $msg) 
			{
				if( $msg->join_message_recipient_read == 1 )
				{
					continue;
				}
				else
				{
					$msg->join_message_recipient_read = 1;
					$msg->save();
				}
			}					
			$response['message'] = "Thread read successfully.";
			$status_code = 200;			
		}
		$response = Response::make( $response, $status_code );
		return $response;
	}
	public function registerSNSEndpoint( $device , $message, $message_group, $name, $icon, $threadid)
	{
		
		if( $device->device_registration_device_type == "apple" )
		{
			$arn = "arn:aws:sns:eu-west-1:830026328040:app/APNS/adminpushdemo";
			//$arn = "arn:aws:sns:eu-west-1:830026328040:app/APNS_SANDBOX/adminsandbox";
		}
		else
		{
			$arn = "arn:aws:sns:eu-west-1:830026328040:app/GCM/admin-android-notification";
		}

		$sns = getSNSClient();

		$Model1 = $sns->listPlatformApplications();  
		
		$result1 = $sns->listEndpointsByPlatformApplication(array(
			// PlatformApplicationArn is required
			'PlatformApplicationArn' => $arn,
		));
		//echo '<pre>';
		//$dtoken = 'APA91bHEx658AQzCM3xUHTVjBGJz8a_HMb65Y_2BIIPXODexYlvuCZpaJRKRchTNqQCXs_w9b0AxJbzIQOFNtYkW0bbsiXhiX7uyhGYNTYC2PBOZzAmvqnvOBBhOKNS7Jl0fdoIdNa_riOlJxQi8COrhbw0odIJKBg';
		//$dtoken = 'c39bac35f298c66d7398673566179deee27618c2036d8c82dcef565c8d732f84';
		foreach($result1['Endpoints'] as $Endpoint){
			$EndpointArn = $Endpoint['EndpointArn']; 
			$EndpointToken = $Endpoint['Attributes'];
			foreach($EndpointToken as $key=>$newVals){
				if($key=="Token"){
					if($device->device_registration_device_token==$newVals){
					//if($dtoken==$newVals){
					//Delete ARN
						$result = $sns->deleteEndpoint(array(
							// EndpointArn is required
							'EndpointArn' => $EndpointArn,
						));
					}
				}
				//print_r($EndpointToken);
			}
			//print_r($Endpoint);
		}

		 $result = $sns->createPlatformEndpoint(array(
			 // PlatformApplicationArn is required
			 'PlatformApplicationArn' => $arn,
			 // Token is required
			 //'Token' => $dtoken,
			 'Token' => $device->device_registration_device_token,

		 ));

		 $endpointDetails = $result->toArray();
		 
		 //print_r($device);echo "\n".$message."\n";print_r($result);print_r($endpointDetails);

		 //die;
		 if($device->device_registration_device_type == "apple")
		 {	
			 $publisharray = array(
			 	'TargetArn' => $endpointDetails['EndpointArn'],
			 	'MessageStructure' => 'json',
			 	 'Message' => json_encode(array(
					'default' => $message,
					//'APNS_SANDBOX' => json_encode(array(
					'APNS' => json_encode(array(
						'aps' => array(
							"sound" => "default",
							"alert" => $message,
							"badge"=> intval(0),
							"messageGroup"=>$message_group,
       						"diaplayname"=>$name,
							"notificationIcon"=>$icon,
       						"entry_id"=>$threadid,
							"Type"=>'Message',
						)						
					)),
				))
			 );
			 
		 }
		 else
		 {
			 $publisharray = array(
			 	'TargetArn' => $endpointDetails['EndpointArn'],
			 	'MessageStructure' => 'json',
			 	'Message' => json_encode(array(
					'default' => $message,
					'GCM'=>json_encode(array(
						'data'=>array(
							'message'=> $message
						)
					))
				))
			 );
		 }
		 try
		 {
			$sns->publish($publisharray);

			$myfile = 'sns-log.txt';
			file_put_contents($myfile, date('d-m-Y H:i:s') . ' debug log:', FILE_APPEND);
			file_put_contents($myfile, print_r($endpointDetails, true), FILE_APPEND);

			//print($EndpointArn . " - Succeeded!\n");    
		 }   
		 catch (Exception $e)
		 {
			//print($endpointDetails['EndpointArn'] . " - Failed: " . $e->getMessage() . "!\n");
		 }
	}
	public function showParticipants()
	{
		//Validate Input
		$rules = array(
			'thread'  => 'required|numeric|exists:message_threads,message_thread_thread_id',
		);

		$validator = Validator::make( Input::get(), $rules );
		$return = array();

		if( $validator->fails() )
		{
			$return[ 'errors' ] = $validator->messages()->all();
			$status_code = 400;
		}
		else
		{
			$threadId = Input::get( 'thread' );

			//Get current user
			$token = Request::header( "X-API-TOKEN" );
			$session = $this->token->get_session( $token );		
			
			$participants = MessageParticipants::where( 'join_message_participant_message_thread_id', '=', $threadId,'and')
										->where( 'join_message_participant_user_id','!=', $session->token_user_id,'and')
										->where( 'join_message_participant_deleted_thread', '=', 0)
										->groupBy( 'join_message_participant_user_id')
										->get();		
										// ->orderBy( 'join_message_participant_id', 'desc' )					
							
			$current[ 'participants' ] = [ ];
			foreach ($participants as $participant) 
			{
				$current[ 'participants' ][] = particUser( $participant->user, $session, false );
			}
			$response['message'] = "Display thread participants successfully.";
			$return[ 'thread' ] = $current;
			$status_code = 200;
		}		
		$response = Response::make( $return, $status_code );
		return $response;
	}	
}