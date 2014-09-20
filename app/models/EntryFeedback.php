<?php

class EntryFeedback extends \Eloquent {

	protected $table = "entry_feedback";
	protected $primary_key = "entry_feedback_id";
	public $timestamps = false;

	protected $fillable = ['entry_feedback_entry_id', 'entry_feedback_content', 'entry_feedback_user_id', 'entry_feedback_created_date'];


	public function entry()
	{
		return $this->belongsTo('Entry', 'entry_feedback_entry_id');
	}

	public function user()
	{
		return $this->belongsTo('User', 'entry_feedback_user_id');
	}
}