<?php

class Comment extends \Eloquent {
	protected $fillable = ['comment_user_id', 'comment_entry_id', 'comment_content', 'comment_added_date', 'comment_deleted', 'comment_deleted_by'];
	protected $guarded = array();
	public $timestamps = false;
	protected $primaryKey = 'comment_id';
	protected $table = "comments";


	public function User()
	{
		return $this->hasOne('User', 'user_id', 'comment_user_id');
	}

	public function Entry()
	{
		return $this->hasOne('Entry', 'entry_id', 'comment_entry_id');
	}

}