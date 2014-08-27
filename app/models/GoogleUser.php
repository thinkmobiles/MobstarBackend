<?php

class GoogleUser extends \Eloquent {
	protected $fillable = ['google_user_id', 'google_user_display_name', 'google_user_user_name'];
	protected $table = "google_users";
	protected $primaryKey = "google_user_id";
	//protected $guarded = array('twitter_user_id');
	public $timestamps = false;

	public function User(){
		return $this->belongsTo('User', 'user_google_id');
	}
}