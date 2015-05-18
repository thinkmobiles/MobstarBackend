<?php

class NotificationCount extends \Eloquent
{
	protected $fillable = [ "user_id", "notification_count"];
	protected $primaryKey = 'id';

	public function user()
	{
		return $this->belongsTo( 'User', 'user_id' );
	}
}