<?php

class Message2 extends \Eloquent {
    
    public $timestamps = false;
    protected $primaryKey = 'message_id';
	protected $fillable = ['message_creator_id', 'message_thread_id', 'message_body', 'message_created_date'];
    protected $table = 'messages';

    public function thread(){
        $this->belongsTo('MessageThread', 'message_thread_id', 'message_thread_thread_id');
    }

	public function participants(){
		return $this->belongsToMany('Participants', 'join_message_recipients', 'join_message_recipient_user_id', 'message_id');
	}

    public function recipients(){
        return $this->hasMany('MessageRecipients', 'join_message_recipient_message_id', 'message_id')->orderBy('join_message_recipient_id', 'asc');
    }

	public function sender(){
		return $this->hasOne('User', 'user_id', 'message_creator_id');
	}

}