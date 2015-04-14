<?php

class MessageThread extends \Eloquent {
	protected $fillable = ["message_thread_thread_id", "message_thread_created_data"];
    protected $primaryKey = 'message_thread_thread_id';

    protected $table = 'message_threads';
    
    public $timestamps = false;

	public function messageRecipients(){
		return $this->hasMany('MessageRecipients', 'join_message_recipient_thread_id')->orderBy('join_message_recipient_id', 'desc');
	}

    public function messageParticipants(){
        return $this->hasMany('MessageParticipants', 'join_message_participant_message_thread_id', 'message_thread_thread_id');
    }
	public function scopeOrdered($query)
	{
		return $query->orderBy('message_created_date', 'DESC')->get();
	}
}