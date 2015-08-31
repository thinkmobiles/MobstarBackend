<?php namespace MobStar\Storage\Vote;

use Vote;
use Entry;
use DB;

class EloquentVoteRepository implements VoteRepository {

	public function get_votes($entry = 0, $user = 0, $up = false, $down = false, $deleted=false, $limit = 50, $offset = 0, $count = false){
		$excludeCategory = array();
		$excludeCategory = [7,8];
		$entry_category = Entry::whereIn( 'entry_category_id', $excludeCategory )->get();
		foreach( $entry_category as $c )
		{
			$exclude[ ] = $c->entry_id;
		}
		$entry_deleted = Entry::where('entry_deleted', '=', '1')->get();
		foreach( $entry_deleted as $d )
		{
			$exclude[ ] = $d->entry_id;
		}
		$query = Vote::with('user', 'entry')->where('vote_id', '>', 0);

		$query->whereNotIn( 'vote_entry_id', $exclude );

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


	public function getTotalVotesForEntries( $entries = 0 )
	{
	  $returnOne = false; // whether return votes for one entry or for array of entries

	  $query = DB::table( 'votes' )->select( DB::raw('
	    vote_entry_id,
	    sum( if( vote_up > 0, 1, 0 ) ) as votes_up,
	    sum( if( vote_down > 0, 1, 0 ) ) as votes_down'
	  ));

	  if( $entries )
	  {
	    if( is_array( $entries ) ) {
	      $query->whereIn( 'vote_entry_id', $entries );
	    }
	    else
	    {
	      $query->where( 'vote_entry_id', '=', $entries );
	      $returnOne = true; // return array of entries
	    }
	  }

	  $query->where( 'vote_deleted', '=', 0 );
	  $query->groupBy( 'vote_entry_id' );

	  $rows = $query->get();

	  if( $returnOne ) {
	    switch( count( $rows ) )
	    {
	      case 1:
	        return array_pop( $rows );
	      case 0:
	        return false; // entry not found
	      default:
	        error_log( 'something wrong when getting total votes for entry '.$enntries );
	        return false;
	    }
	  }
	  else
	  {
	    // return array indexed with entry_id
	    $ret = array();
	    foreach( $rows as $row ) $ret[ $row->vote_entry_id ] = $row;
	    return $ret;
	  }
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