<?php

class Star extends \Eloquent {
	protected $fillable = ['user_star_star_id', 'user_star_user_id', 'user_star_created_date'];
	protected $guarded = array();
	public $timestamps = false;
	protected $primaryKey = 'user_star_id';
	protected $table = "user_stars";


	public function User()
	{
		return $this->hasOne('User', 'user_id', 'user_star_user_id');
	}

	public function Stars()
	{
		return $this->hasOne('User', 'user_id', 'user_star_star_id');
	}

}