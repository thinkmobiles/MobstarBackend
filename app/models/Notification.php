<?php

class Notification extends \Eloquent
{
	protected $fillable = [ "notification_user_id", "notification_subject_ids", "notification_details", "notification_read", "notification_created_date", "notification_updated_date", "notification_type", "notification_entry_id", "notification_deleted" ];
	public $timestamps = false;
	protected $primaryKey = 'notification_id';

	public function user()
	{
		return $this->belongsTo( 'User', 'notification_user_id' );
	}

	public function entry()
	{
		return $this->belongsTo( 'Entry', 'notification_entry_id' );
	}

}