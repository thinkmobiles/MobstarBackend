<?php

class Feedback extends \Eloquent {
	protected $fillable = ['feedback_user_id', 'feedback_details', 'feedback_created_date', 'feedback_read'];
	protected $guarded = array();
	public $timestamps = false;
	protected $primaryKey = 'feedback_id';
	protected $table = "feedback";


	public function User()
	{
		return $this->belongsTo('User', 'feedback_user_id');
	}

}