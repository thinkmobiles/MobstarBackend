<?php

class Faq extends \Eloquent
{
	protected $fillable = [ "faq_user_id", "faq_question", "faq_answer", "faq_added_date" ];
	public $timestamps = false;
	protected $primaryKey = 'faq_id';
	protected $table = 'faq';

	public function user()
	{
		return $this->belongsTo( 'User', 'faq_user_id' );
	}


}