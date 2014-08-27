<?php namespace MobStar\Storage\Message;

use Message;
use DB;

class EloquentMessageRepository implements MessageRepository {
	
	
	public function get_messages($user = 0, $deleted=false, $limit = 50, $offset = 0, $count = false)
	{
		$query = DB::select("select t1.*, sender.user_name as sender_user_name, sender.user_display_name as sender_display_name, recipient.user_name as recipient_user_name, recipient.user_display_name as recipient_display_name
			 from messages t1 
				JOIN (SELECT message_id, message_body, max(message_created_date) message_created_date, message_sender_id, message_recipient_id, 
					CASE message_sender_id WHEN " . $user . " THEN message_recipient_id 
					ELSE message_sender_id END as thread 
					from messages 
					where message_sender_id = " . $user . " or message_recipient_id = " . $user . " group by thread) t2
				ON t1.message_created_date = t2.message_created_date
				LEFT JOIN users as sender on sender.user_id = t1.message_sender_id
				LEFT JOIN users as recipient on recipient.user_id = t1.message_recipient_id
				ORDER BY message_created_date DESC");


		//print_r($query);
		return $query;		

	}

	public function get_message_thread($user = 0, $sender = 0, $deleted=false, $limit = 50, $offset = 0, $count = false)
	{
		$query = Message::with('sender', 'recipient');
		$var[0] = $user;
		$var[1] = $sender;

		$query = $query->where(function($query) use ($var){
			$query = $query->where(function($query) use ($var){
				$query->where('message_recipient_id', '=', $var[0])
					->where('message_sender_id', '=', $var[1]);
			});


			$query = $query->orWhere(function($query) use ($var){
				$query->where('message_sender_id', '=', $var[0])
					->where('message_recipient_id', '=', $var[1]);
			});

		});

		$query = $query->orderBy('message_created_date', 'desc');

		if(!$deleted){
			$query = $query->where(function($query) use ($var){
				$query->where('message_sender_deleted', '=', '0');
				$query->orWhere('message_recipient_deleted', '=', '0');
			});
		}
		if($count)
			return $query->count();

		return $query->take($limit)->skip($offset)->get();

	}

	public function send_message($input)
	{
		return Message::create($input);

	}

	public function delete_messages($ids, $user)
	{	
		$id_comma = implode(',',$ids); 
		$date = date('Y-m-d H:i:s');

		$query = DB::update(
		    DB::raw(
		                "UPDATE messages SET message_sender_deleted = 
		                (
	                        CASE 
	                            WHEN message_sender_id = ?
	                            THEN 1
	                            ELSE 0
	                        END
		                ) 
		    			, 
		    			message_sender_deleted_date = 
		                (
	                        CASE 
	                            WHEN message_sender_id = ?
	                            THEN ?
	                            ELSE NULL
	                        END
		                ) 
		    			, message_recipient_deleted = 
		    			(
		    				CASE 
	                            WHEN message_recipient_id = ?
	                            THEN 1
	                            ELSE 0
	                        END
		    			)
		    			, 
		    			message_recipient_deleted_date = 
		                (
	                        CASE 
	                            WHEN message_recipient_id = ?
	                            THEN ?
	                            ELSE NULL
	                        END
		                )
		                    WHERE message_id in (" . $id_comma . ")"
		    ),

		    array($user, $user, $date, $user, $user, $date)
		);

	return $query;

	}

	public function delete_thread($thread, $user)
	{	
		$date = date('Y-m-d H:i:s');

		$query = DB::update(
		    DB::raw(
		                "UPDATE messages SET message_sender_deleted = 
		                (
	                        CASE 
	                            WHEN message_sender_id = ?
	                            THEN 1
	                            ELSE 0
	                        END
		                ) 
		    			, 
		    			message_sender_deleted_date = 
		                (
	                        CASE 
	                            WHEN message_sender_id = ?
	                            THEN ?
	                            ELSE NULL
	                        END
		                ) 
		    			, message_recipient_deleted = 
		    			(
		    				CASE 
	                            WHEN message_recipient_id = ?
	                            THEN 1
	                            ELSE 0
	                        END
		    			)
		    			, 
		    			message_recipient_deleted_date = 
		                (
	                        CASE 
	                            WHEN message_recipient_id = ?
	                            THEN ?
	                            ELSE NULL
	                        END
		                )
		                    WHERE 
		                    (
		                    	message_recipient_id = ? AND message_sender_id = ?
		                    )
							OR 
							(
								message_sender_id = ? AND message_recipient_id = ?
							)"
		    ),

		    array($user, $user, $date, $user, $user, $date, $user, $thread, $user, $thread)
		);	
	
	return $query;
	
	}

}