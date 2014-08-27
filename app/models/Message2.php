<?php

class Message2 extends \Eloquent {
    
    public $timestamps = false;
    protected $primaryKey = 'message_id';

    protected $table = 'messages';

    public function thread(){
        $this->belongsTo('MessageThread', 'message_thread_id', 'message_thread_thread_id');
    }

    public function recipients(){
        $this->hasMany('MessageRecipients', 'message_id', 'join_message_recipient_message_id');
    }

}