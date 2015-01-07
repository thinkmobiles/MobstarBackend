<?php

class UserPhone extends \Eloquent {

	protected $fillable = [
		'user_phone_user_id',
		'user_phone_number',
		'user_phone_country',
		'user_phone_verified',
		'created_at'
	];

	protected $guarded = array();
	public $timestamps = false;
	protected $primaryKey = 'user_phone_id';
	protected $table = "user_phones";


	public function User()
	{
		return $this->belongsTo('User', 'user_phone_user_id');
	}

}