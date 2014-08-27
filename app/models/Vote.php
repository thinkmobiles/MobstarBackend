<?php

class Vote extends \Eloquent {
	protected $fillable = ["vote_user_id", "vote_entry_id", "vote_up", "vote_down", "vote_date", "vote_deleted", "vote_deleted_date"];
    public $timestamps = false;
    protected $primaryKey = 'vote_id';

	public function user()
    {
        return $this->belongsTo('User', 'vote_user_id');
    }

	public function entry()
    {
        return $this->belongsTo('Entry', 'vote_entry_id');
    }

}