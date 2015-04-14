<?php

use MobStar\Storage\Message2\Message2Repository as Message;
use MobStar\Storage\Entry\EntryRepository as Entry;
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

			foreach( $message->messageRecipients as $received )
			{
				if( $received->join_message_recipient_user_id == $session->token_user_id && $received->join_message_recipient_created > $last )
				{
					$lastMessage = $received;
				}
			}

			$current[ 'lastMessage' ][ 'messageContent' ] = $lastMessage->message->message_body;
			$current[ 'lastMessage' ][ 'messageSender' ] = oneUser( $lastMessage->message->sender, $session );
			$current[ 'lastMessage' ][ 'messageReceived' ] = $lastMessage->message->message_created_date;


			$current[ 'read' ] = $lastMessage->join_message_recipient_read;
			$current[ 'participants' ] = [ ];

			foreach( $message->messageParticipants as $participant )
			{
				if( $participant->user->user_id == $session->token_user_id )
				{
					continue;
				}
				$current[ 'participants' ][ ] = oneUser( $participant->user, $session, false );
			}

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
					'message'         => $received->message->message_body,
					'messageSender'   => oneUser( $received->message->sender, $session ),
					'messageReceived' => $received->message->message_created_date,
					'messageRead'     => $received->join_message_recipient_read
				];
			}
			//}
		}

		$current[ 'messages' ] = $receivedMessages;

		$current[ 'participants' ] = [ ];

		foreach( $thread->messageParticipants as $participant )
		{
			if( $participant->user->user_id == $session->token_user_id )
			{
				continue;
			}
			$current[ 'participants' ][ ] = oneUser( $participant->user,$session, false );
		}

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

		$recipArray = [ ];
		$particArray = [ ];

		$messageThread = MessageThread::create( [ 'message_thread_created_date' => date( 'Y-m-d H:i:s' ) ] );

		$messageOb = Message2::create(
							 [
								 'message_creator_id'   => $session->token_user_id,
								 'message_thread_id'    => $messageThread->message_thread_thread_id,
								 'message_body'         => $message,
								 'message_created_date' => date( 'Y-m-d H:i:s' )
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

		$recipients = MessageParticipants::where( 'join_message_participant_message_thread_id', $thread );

		$recipArray = [ ];
		$particArray = [ ];

		$messageOb = Message2::create(
							 [
								 'message_creator_id'   => $session->token_user_id,
								 'message_thread_id'    => $thread,
								 'message_body'         => $message,
								 'message_created_date' => date( 'Y-m-d H:i:s' )
							 ]
		);

		foreach( $recipients as $recipient )
		{
			if( $recipient->join_message_participant_user_id == $session->token_user_id )
			{
				continute;
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
							'message_created_date' => date( 'Y-m-d H:i:s' )
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

					MessageParticipants::insert( $particArray );

					MessageRecipients::insert( $recipArray );
				}

				$response['info'] = "Message sent successfully to " . count($users) . " users";
				$status_code = 201;
			}
		}

		return Response::make($response, $status_code);
	}
}