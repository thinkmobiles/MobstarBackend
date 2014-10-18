<?php

class DeviceRegistration extends \Eloquent {

	protected $fillable = [
		'device_registration_user_id',
		'device_registration_device_type',
		'device_registration_device_token',
		'device_registration_date_created'
	];

	protected $guarded = array();
	public $timestamps = false;
	protected $primaryKey = 'device_registration_id';
	protected $table = "device_registrations";


	public function User()
	{
		return $this->belongsTo('User', 'device_registration_user_id');
	}

}