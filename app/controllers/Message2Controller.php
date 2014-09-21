<?php

use MobStar\Storage\Message2\Message2Repository as Message;
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

	public function __construct( Message $message, Token $token )
	{
		$this->message = $message;
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
	 *           name="fields",
	 *           description="Accepted values for the fields parameter are: id, sender, recipient, body, date.",
	 *           paramType="query",
	 *           required=false,
	 *           type="comma seperated list"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="thread",
	 *           description="The thread you want to view, this is the ID of the user the correspondance is with.",
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
	 *            message="No messages found"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */
	public function index()
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
		$messages = $this->message->get_message_thread_new( $session[ 'token_user_id' ], $deleted, $limit, $offset, false );
		//var_dump($messages);
		//break;

		$count = ( count( $messages ) );

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
			$current[ 'lastMessage' ][ 'messageSender' ] = oneUser( $lastMessage->message->sender );

			$current[ 'read' ] = $lastMessage->join_message_recipient_read;
			$current[ 'participants' ] = [ ];

			foreach( $message->messageParticipants as $participant )
			{
				if( $participant->user->user_id == $session->token_user_id )
				{
					continue;
				}
				$current[ 'participants' ][ ] = oneUser( $participant->user, false );
			}

			$return[ 'threads' ][ ][ 'thread' ] = $current;
		}

		$status_code = 200;

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

		$response = Response::make( $return, $status_code );

		$response->header( 'X-Total-Count', $count );

		return $response;
	}
}