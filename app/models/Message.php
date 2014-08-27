<?php

class Message extends \Eloquent {
	protected $fillable = ["message_sender_id", "message_recipient_id", "message_body", "message_sender_deleted", "message_sender_deleted_date", "message_recipient_deleted", "message_recipient_deleted_date", "message_created_date"];
    public $timestamps = false;
    protected $primaryKey = 'message_id';

    public function sender()
    {
        return $this->belongsTo('User', 'message_sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo('User', 'message_recipient_id');
    }

}