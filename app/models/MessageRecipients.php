<?php

class MessageRecipients extends \Eloquent {

	protected $fillable = ["join_message_recipient_message_id", "join_message_recipient_user_id", "join_message_recipient_created", "join_message_recipient_read", "join_message_recipient_read_date", "join_message_recipient_deleted", "join_message_recipient_deleted_date"];

    protected $primaryKey = 'join_message_recipient_id';

    protected $table = 'join_message_recipients';
    
    public $timestamps = false;

	public function messageThread(){
		return $this->hasOne('MessageThread', 'join_message_recipient_message_thread_id', 'message_thread_thread_id');
	}

    public function message(){
        return $this->hasOne('Message2', 'message_id', 'join_message_recipient_message_id');
    }

    public function user(){
        $this->belongsTo('User', 'join_message_recipient_user_id', 'user_id');
    }

}