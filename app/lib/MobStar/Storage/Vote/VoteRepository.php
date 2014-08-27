<?php namespace MobStar\Storage\Vote;
 
interface VoteRepository {
	
	public function get_votes($entry = 0, $user = 0, $up = false, $down = false, $deleted=false, $limit = 50, $offset = 0, $count = false);

	public function create($input);

	public function delete_previous($delete);
}