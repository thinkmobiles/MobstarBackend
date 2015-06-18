<?php

class UserAnalytic extends \Eloquent {

	protected $fillable = [
		'user_analytic_user_id',
		'user_analytic_platform',
		'user_analytic_os_version',
		'user_analytic_device_name',
		'user_analytic_app_version',
		'user_analytic_created_at'
	];

	protected $guarded = array();
	public $timestamps = false;
	protected $primaryKey = 'user_analytic_id';
	protected $table = "user_analytics";


	public function User()
	{
		return $this->belongsTo('User', 'user_analytic_user_id');
	}

}