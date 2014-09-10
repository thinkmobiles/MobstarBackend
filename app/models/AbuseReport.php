<?php

class AbuseReport extends \Eloquent {
	protected $fillable = ['abuse_report_user_id', 'abuse_report_details', 'abuse_report_created', 'abuse_report_read'];
	protected $guarded = array();
	public $timestamps = false;
	protected $primaryKey = 'abuse_report_id';
	protected $table = "abuse_reports";


	public function User()
	{
		return $this->belongsTo('User', 'abuse_report_user_id');
	}

}