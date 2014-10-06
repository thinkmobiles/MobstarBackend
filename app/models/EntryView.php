<?php

class EntryView extends \Eloquent {

	protected $table = "entry_views";
	protected $primary_key = "entry_view_id";
	public $timestamps = false;

	protected $fillable = ['entry_view_entry_id', 'entry_view_user_id', 'entry_view_date'];


	public function entry()
	{
		return $this->belongsTo('Entry', 'entry_view_entry_id');
	}
}