<?php

class TwitterUser extends \Eloquent {
	protected $fillable = ['twitter_user_id', 'twitter_user_display_name'];
	protected $table = "twitter_users";
	protected $primaryKey = "twitter_user_id";
	//protected $guarded = array('twitter_user_id');
	public $timestamps = false;

	public function User(){
		return $this->belongsTo('User', 'user_twitter_id');
	}
}