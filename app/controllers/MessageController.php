<?php

use MobStar\Storage\Message\MessageRepository as Message;
use MobStar\Storage\Token\TokenRepository as Token;
use Swagger\Annotations as SWG;

class MessageController extends BaseController {

	public $valid_fields = ["id", "sender", "recipient", "body", "date"];

	public function __construct(Message $message, Token $token)
	{
	  $this->message = $message;
	  $this->token = $token;
	}

	public function index()
	{
	    markDeprecated( __METHOD__ );

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
					$current['id'] = $message->message_id;
					$current['sender']['userId'] = $message->message_sender_id;
					$current['sender']['userName'] = $message->sender->user_name;
					$current['sender']['userDisplayName'] = $message->sender->user_display_name;
					$current['sender']['messageDeleted'] = ($message->message_sender_deleted == 1) ? true : false;


					$current['recipient']['userId'] = $message->message_recipient_id;
					$current['recipient']['userName'] = $message->recipient->user_name;
					$current['recipient']['userDisplayName'] = $message->recipient->user_display_name;
					$current['recipient']['messageDeleted'] = ($message->message_recipient_deleted == 1) ? true : false;

					$current['body'] = $message->message_body;
					$current['date'] = $message->message_created_date;
					//print_r($current);
					$return['messages'][]['message'] = $current;
				}
			}
		}
		else
		{
			//Get users threads
			$messages = $this->message->get_messages($session['token_user_id'], $deleted, $limit, $offset, false);
			$count = (count($messages));

			foreach ($messages as $message){

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
					$current['id'] = $message->message_id;
					$current['sender']['userId'] = $message->message_sender_id;
					$current['sender']['userName'] = $message->sender_user_name;
					$current['sender']['userDisplayName'] = $message->sender_display_name;


					$current['recipient']['userId'] = $message->message_recipient_id;
					$current['recipient']['userName'] = $message->recipient_user_name;
					$current['recipient']['userDisplayName'] = $message->recipient_display_name;

					$current['message_body'] = $message->message_body;
					$current['message_date'] = $message->message_created_date;
					//print_r($current);
					$return['messages'][]['message'] = $current;
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
			$return['next'] = "http://".$_ENV['URL']."/vote/?" . http_build_query(["limit" => $limit, "page" => $page+1]);

		if($previous)
			$return['previous'] = "http://".$_ENV['URL']."/vote/?" . http_build_query(["limit" => $limit, "page" => $page-1]);

		$response = Response::make($return, $status_code);

		$response->header('X-Total-Count', $count);

		return $response;
	}



	public  function store()
	{
	    markDeprecated( __METHOD__ );

		$token =  Request::header("X-API-TOKEN");

		$session = $this->token->get_session($token);

		//Validate Input
		$rules = array(
			'recipient'  	=> 'required|numeric',
			'body'			=> 'required'
		);

		$validator = Validator::make(Input::get(), $rules);

		if ($validator->fails())
		{
			//var_dump($validator->messages());
			$response['errors'] = $validator->messages()->all();
			$status_code = 400;
		}
		else
		{


			//Get input
			$input = [
				'message_sender_id' => $session->token_user_id,
				'message_recipient_id' => Input::get('recipient'),
				'message_body' =>  Input::get('body'),
				'message_created_date' => date('Y-m-d H:i:s'),
				];
			$this->message->send_message($input);
			$response['message'] = "Message sent";
			$status_code = 201;
		}

		return Response::make($response, $status_code);
	}



	public function destroy()
	{
	    markDeprecated( __METHOD__ );

		$input = Input::get();

		if(!isset($input['id']) && !isset($input['thread'])){
			$response['error'] = "You must specify a thread or Id(s) to be deleted";
			$status_code = 400;
			return Response::make($response, $status_code);
		}

		$token =  Request::header("X-API-TOKEN");

		$session = $this->token->get_session($token);

		$messages = 0;

		if(isset($input['id']))
		{
			$ids = array_values(explode(',',$input['id']));

			$messages = $this->message->delete_messages($ids, $session['token_user_id']);
		}

		if(isset($input['thread']))
		{
			if(!$messages)
				$messages = $this->message->delete_thread($input['thread'], $session['token_user_id']);
			else
				$messages = $messages + $this->message->delete_thread($input['thread'], $session['token_user_id']);
		}

		$response['message'] = $messages . " message(s) deleted";
		$status_code = 200;

		return Response::make($response, $status_code);
	}

}