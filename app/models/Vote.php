<?php

class Vote extends \Eloquent {
	protected $fillable = ["vote_user_id", "vote_entry_id", "vote_up", "vote_down", "vote_date", "vote_deleted", "vote_deleted_date"];
    public $timestamps = false;
    protected $primaryKey = 'vote_id';

	public function user()
    {
        return $this->hasOne('User', 'user_id', 'vote_user_id');
    }

	public function entry()
    {
        return $this->hasOne('Entry', 'entry_id', 'vote_entry_id');
    }

}