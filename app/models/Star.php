<?php

class Star extends \Eloquent {
	protected $fillable = ['user_star_star_id'];
	protected $guarded = array();
	public $timestamps = false;
	protected $primaryKey = 'user_star_id';
	protected $table = "user_stars";


	public function User()
	{
		return $this->hasOne('User', 'user_id', 'user_star_user_id');
	}

	public function Star()
	{
		return $this->hasOne('User', 'user_id', 'user_star_star_id');
	}

}