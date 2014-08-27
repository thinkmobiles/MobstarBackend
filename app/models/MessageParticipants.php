<?php

class MessageParticipants extends \Eloquent {
	protected $fillable = ["message_sender_id", "message_recipient_id", "message_body", "message_sender_deleted", "message_sender_deleted_date", "message_recipient_deleted", "message_recipient_deleted_date", "message_created_date"];
    
    protected $primaryKey = 'join_message_participant_id';

    protected $table = 'join_message_participants';
    
    public $timestamps = false;

    public function messageThread(){
        return $this->hasOne('MessageThread', 'message_thread_thread_id', 'join_message_participant_message_thread_id');
    }

    public function otherParticipants(){
        return $this->hasMany('MessageParticipants', 'join_message_participant_message_thread_id', 'join_message_participant_message_thread_id');
    }

    public function user(){
    	return $this->belongsTo('User', 'join_message_participant_user_id');
    }

}