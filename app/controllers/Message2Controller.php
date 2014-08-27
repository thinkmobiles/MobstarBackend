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
class Message2Controller extends BaseController {

	public $valid_fields = ["id", "sender", "recipient", "body", "date"];

	public function __construct(Message $message, Token $token)
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
     *   produces="['application/json']",
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

		$fields = array_values(explode(',',Input::get("fields")));
		
		if ($fields[0] == "")
			unset($fields);
		
		$return = [];
		$valid = false;

		if(!empty($fields))
		{	
			//Check if fields are valid
			foreach($fields as $field)
			{
				if(!in_array($field, $this->valid_fields))
					$return['errors'][] = [$field . " is not a valid field."];
				else
					$valid = true;
			}

		}

		//Get limit to calculate pagination 
		$limit = (Input::get('limit', '50'));

		//If not numeric set it to the default limit
		$limit = (!is_numeric($limit) || $limit < 1) ? 50 : $limit;

		//Get page
		$page = (Input::get('page', '1'));
		$page = (!is_numeric($page)) ? 1 : $page;

		//Calculate offset
		$offset = ($page * $limit) - $limit;

		//If page is greter than one show a previous link
		if($page > 1)
			$previous = true;
		else 
			$previous = false;

		//Get current user
		$token =  Request::header("X-API-TOKEN");
		$session = $this->token->get_session($token);
		
		//Get Recipient
		$thread = (Input::get('thread', '0'));
		$thread = (!is_numeric($thread)) ? 0 : $thread;
		
		//Get Deleted
		$deleted = (Input::get('delted', '0'));
		if($thread)
		{
			//Get a single thread and all messages within it
			$messages = $this->message->get_message_thread($session['token_user_id'], $thread, $deleted, $limit, $offset, false);
			$count = $this->message->get_message_thread($session['token_user_id'], $thread, $deleted, $limit, $offset, true);
			var_dump($messages);
			break;

			foreach ($messages as $message){
				
				$current = array();

				//check to see if fields were specified and at least one is valid
				if((!empty($fields)) && $valid)
				{	
					if(in_array("id",$fields))
						$current['id'] = $message->message_id;
					if(in_array("sender",$fields)){
						$current['sender']['userId'] = $message->message_sender_id;
						$current['sender']['userName'] = $message->sender->user_name;
						$current['sender']['userDisplayName'] = $message->sender->user_display_name;
						$current['sender']['messageDeleted'] = ($message->message_sender_deleted == 1) ? true : false;
					}

					if(in_array("recipient",$fields)){
						$current['recipient']['userId'] = $message->message_recipient_id;
						$current['recipient']['userName'] = $message->recipient->user_name;
						$current['recipient']['userDisplayName'] = $message->recipient->user_display_name;
						$current['recipient']['messageDeleted'] = ($message->message_recipient_deleted == 1) ? true : false;
					}

					if(in_array("body",$fields))
						$current['body'] = $message->message_body;
					if(in_array("date",$fields))
						$current['date'] = $message->message_created_date;
					//print_r($current);
					$return['messages'][]['message'] = $current; 

					$return['votes'][]['votes'] = $current; 
				}
				else 
				{
					//print_r($message);
					$current['id'] = $message['join_message_participant_message_thread_id'];
					$current['threadName'] =  $message['join_message_participant_thread_name'];
					$current['userLeftThread'] =  ($message['join_message_participant_left_thread']) ? true : false;
					$current['threadCreated'] =  $message['message_thread']['message_thread_created_date'];

					$participant = null;
					$current['participants'] = array();

					foreach ($message['other_participants'] as $message_participant) {
						$participant['userId'] = $message_participant['join_message_participant_user_id'];
						$participant['leftThread'] =  ($message_participant['join_message_participant_left_thread']) ? true : false;
						$participant['userDisplayName'] =  $message_participant['user']['user_display_name'];

						$current['participants'][] = $participant;
					}

					$return['thread'] = $current; 
				}
			}
		}
		else
		{	
			//Get users threads
			$messages = $this->message->get_messages($session['token_user_id'], $deleted, $limit, $offset, false);
			//var_dump($messages);
			//break;

			$count = (count($messages));

			foreach ($messages as $message)
			{
				
				$current = array();

				//check to see if fields were specified and at least one is valid
				if((!empty($fields)) && $valid)
				{	

					if(in_array("id",$fields))
						$current['id'] = $message->vote_id;

					if(in_array("user",$fields))
					{
						$current['user']['userId'] = $message->vote_user_id;
						$current['user']['userEmail'] = $message->user()->getResults()->user_email;
						$current['user']['userName'] = $message->user()->user_name;
						$current['user']['userDisplayName'] = $message->user()->user_display_name;
					}

					if(in_array("entry",$fields)){
						$current['entry']['entryId'] = $message->vote_entry_id;
						$current['entry']['entryName'] = $message->entry()->getResults()->entry_name;
						$current['entry']['entryDescription'] = $message->entry()->getResults()->entry_description;
					}

					if(in_array("type",$fields))
					{
						if($message['vote_up'] == 1 && $message['vote_down'] == 0)
							$current['type'] = "Upvote";
						elseif($message['vote_up'] == 0 && $message['vote_down'] == 1)
							$current['type'] = "Downvote";
						else
							$current['type'] = "Error";
					}

					if(in_array("date",$fields))
						$current['date'] = $message->vote_created_date;

					$return['votes'][]['votes'] = $current; 
				}
				else 
				{
					//print_r($message);
					$current['id'] = $message['join_message_participant_message_thread_id'];
					$current['threadName'] =  $message['join_message_participant_thread_name'];
					$current['userLeftThread'] =  ($message['join_message_participant_left_thread']) ? true : false;
					$current['threadCreated'] =  $message['message_thread']['message_thread_created_date'];

					$participant = null;
					$current['participants'] = array();

					foreach ($message['other_participants'] as $message_participant) {
						$participant['userId'] = $message_participant['join_message_participant_user_id'];
						$participant['leftThread'] =  ($message_participant['join_message_participant_left_thread']) ? true : false;
						$participant['userDisplayName'] =  $message_participant['user']['user_display_name'];

						$current['participants'][] = $participant;
					}

					$return['threads'][]['thread'] = $current; 
				}
			}
		}
		
		$status_code = 200;


		//If the count is greater than the highest number of items displayed show a next link
		if($count > ($limit * $page))
			$next = true;
		else
			$next = false;

		//If next is true create next page link
		if($next)
			$return['next'] = "http://api.mobstar.com/vote/?" . http_build_query(["limit" => $limit, "page" => $page+1]);

		if($previous)
			$return['previous'] = "http://api.mobstar.com/vote/?" . http_build_query(["limit" => $limit, "page" => $page-1]);

		$response = Response::make($return, $status_code);

		$response->header('X-Total-Count', $count);

		return $response;
	}
}