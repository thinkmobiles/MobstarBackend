<?php

class MessageRecipients extends \Eloquent {

	protected $fillable = ["message_sender_id", "message_recipient_id", "message_body", "message_sender_deleted", "message_sender_deleted_date", "message_recipient_deleted", "message_recipient_deleted_date", "message_created_date"];

    protected $primaryKey = 'join_recipient_id';

    protected $table = 'join_message_recipients';
    
    public $timestamps = false;

    public function messageThread(){
        $this->belongsTo('MessageThread', 'join_message_recipient_message_thread_id', 'message_thread_thread_id');
    }

    public function message(){
        $this->belongsTo('Message2', 'join_message_recipient_message_id', 'message_id');
    }

    public function user(){
        $this->belongsTo('User', 'join_message_recipient_user_id', 'user_id');
    }

}