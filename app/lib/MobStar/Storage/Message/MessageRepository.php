<?php namespace MobStar\Storage\Message;
 
interface MessageRepository {
	
	public function get_messages($user = 0, $deleted=false, $limit = 50, $offset = 0, $count = false);

	public function get_message_thread($user = 0, $sender = 0, $deleted=false, $limit = 50, $offset = 0, $count = false);

	public function send_message($input);

	public function delete_messages($ids, $user);

	public function delete_thread($thread, $user);
}