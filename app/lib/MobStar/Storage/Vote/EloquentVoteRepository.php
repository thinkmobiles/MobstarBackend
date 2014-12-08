<?php namespace MobStar\Storage\Vote;

use Vote;

class EloquentVoteRepository implements VoteRepository {
	
	public function get_votes($entry = 0, $user = 0, $up = false, $down = false, $deleted=false, $limit = 50, $offset = 0, $count = false){
		$query = Vote::with('user', 'entry')->where('vote_id', '>', 0);

		if($entry)
			$query->where('vote_entry_id', '=', $entry);

		if($user)
			$query->where('vote_user_id', '=', $user);

		if($up)
			$query->where('vote_up', '=', '1');

		if($down)
			$query->where('vote_down', '=', '1');

		if($deleted !== 0)
			$query->where('vote_deleted', '=', '1');
		else
			$query->where('vote_deleted', '=', '0');

		if($count)
			return $query->count();

		$query->orderBy('vote_id', 'desc');

		return $query->take($limit)->skip($offset)->get();
	}


	public function for_entries($entries,$up = false, $down = false, $limit = 0, $offset = 0, $order = 'vote_created_date',  $count = false){
		$query = Vote::with('user', 'entry')->where('vote_id', '>', 0);

		$query->whereIn('vote_entry_id', $entries);

		if($up)
			$query->where('vote_up', '=', '1');

		if($down)
			$query->where('vote_down', '=', '1');

		$query->where('vote_deleted', '=', '0');

		if($count)
			return $query->count();

		//TODO: figure out a way to order by user name
//
//		if($order = 'user_display_name')
//		{
		//closure goes here
//			$query = $query->with()
//		}
		$query->orderBy($order, 'desc');

		return $query->take($limit)->skip($offset)->get();
	}



	public function create($input)
	{
		return Vote::create($input);
	}

	public function delete_previous($delete){
		Vote::where('vote_user_id', '=', $delete['vote_user_id'])
			->where('vote_entry_id', '=', $delete['vote_entry_id'])
			->update(['vote_deleted' => 1, 'vote_deleted_date' => date('Y-m-d H:i:s')]);	
	}

}