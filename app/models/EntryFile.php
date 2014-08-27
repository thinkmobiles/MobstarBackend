<?php

class EntryFile extends \Eloquent {

    protected $table = "entry_files";
    protected $primary_key = "entry_file_id";	
    public $timestamps = false;

	protected $fillable = [];


	public function entry()
    {
        return $this->belongsTo('Entry', 'entry_file_entry_id');
    }
}