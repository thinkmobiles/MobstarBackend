<?php

class FacebookUser extends \Eloquent {
	protected $fillable = ['facebook_user_facebook_id', 'facebook_user_display_name', 'facebook_user_user_name'];
	protected $table = "facebook_users";
	protected $primaryKey = "facebook_user_id";
	//protected $guarded = array('twitter_user_id');
	public $timestamps = false;

	public function User(){
		return $this->belongsTo('User', 'facebook_user_id');
	}
}