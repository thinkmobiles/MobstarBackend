<?php namespace MobStar\Storage\Message2;

use MessageParticipants;
use MessageThread;
use DB;

class EloquentMessage2Repository implements Message2Repository
{

	public function get_messages( $user = 0, $deleted = false, $limit = 50, $offset = 0, $count = false )
	{

		$query = MessageParticipants::with( 'messageThread', 'otherParticipants.user' )->where( 'join_message_participant_user_id', '=', $user );
		if( $deleted )
		{
			$query = $query->where( 'join_message_participant_deleted_thread', '=', 1 );
		}
		else
		{
			$query = $query->where( 'join_message_participant_deleted_thread', '=', 0 );
		}

		$query = $query->get()->toArray();

		//break;
		return $query;
	}

	public function get_message_thread( $user = 0, $thread = 0, $deleted = false, $limit = 50, $offset = 0, $count = false )
	{

		$query = MessageParticipants::with( 'messageThread', 'otherParticipants.user' )
									->leftJoin( 'join_message_recipients', 'join_message_recipient_thread_id', '=', 'join_message_participant_message_thread_id' )
									->where( 'join_message_participant_user_id', '=', $user )
									->where( 'join_message_recipient_user_id', '=', $user );
		if( $deleted )
		{
			$query = $query->where( 'join_message_participant_deleted_thread', '=', 1 );
		}
		else
		{
			$query = $query->where( 'join_message_participant_deleted_thread', '=', 0 );
		}

		$query = $query->get()->toArray();

		//break;
		return $query;

	}

	public function get_message_thread_new( $user = 0, $thread = 0, $deleted = false, $limit = 50, $offset = 0, $count = false )
	{
		/*$query = MessageThread::with( 'messageParticipants', 'messageRecipients', 'messageRecipients');

		if($thread)
			return $query->find($thread);

		else{
			$query = $query->whereHas( 'messageParticipants', function ( $query ) use ( $user )
			{
				$query = $query->where( 'join_message_participant_user_id', '=', $user );
				return $query;
			} );

			$query = $query->whereHas( 'messageRecipients', function ( $query ) use ( $user )
			{
				$query = $query->where( 'join_message_recipient_user_id', '=', $user );
				return $query;

			} );

			return $query->get();

		}*/
		$query = MessageParticipants::with( 'messageThread', 'otherParticipants.user' )
									->leftJoin( 'join_message_recipients', 'join_message_recipient_thread_id', '=', 'join_message_participant_message_thread_id' )
									->where( 'join_message_participant_user_id', '=', $user )
									->where( 'join_message_recipient_user_id', '=', $user );
		if( $deleted )
		{
			$query = $query->where( 'join_message_participant_deleted_thread', '=', 1 );
		}
		else
		{
			$query = $query->where( 'join_message_participant_deleted_thread', '=', 0 );
		}

		$query = $query->get()->toArray();

		//break;
		return $query;
	}

	public function send_message( $input )
	{

	}

	public function delete_messages( $ids, $user )
	{

	}

	public function delete_thread( $thread, $user )
	{

	}
}