<?php

class Group extends \Eloquent {
	protected $fillable = [];
	protected $table = "user_groups";

	public function user(){
		return $this->hasMany('User', 'user_user_group', 'user_group_id');
	}
}