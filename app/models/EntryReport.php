<?php

class EntryReport extends \Eloquent {

    protected $table = "entry_reports";
    protected $primary_key = "entry_report_id";	
    public $timestamps = false;

	protected $fillable = ['entry_report_entry_id', 'entry_report_report_reason', 'entry_report_user_id', 'entry_report_created_date'];


	public function entry()
    {
        return $this->belongsTo('Entry', 'entry_report_entry_id');
    }
}