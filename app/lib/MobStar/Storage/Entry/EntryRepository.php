<?php namespace MobStar\Storage\Entry;
 
interface EntryRepository {
	public function all($user = 0, $category = 0, $exclude = 0, $order_by = 0, $order = 'desc', $limit = 50, $offset = 0, $count = false);


	public function all_include_deleted($user = 0, $category = 0, $exclude = 0, $order_by = 0, $order = 'desc', $limit = 50, $offset = 0, $count = false);

	public function whereIn($ids, $user = 0, $category = 0, $limit = 50, $offset = 0, $count = false);

	public function create($input);

	public function addTag($tags, $id, $user_id);

}