<?php

class EntryFile extends \Eloquent {

    protected $table = "entry_files";
    protected $primaryKey = "entry_file_id";
    public $timestamps = false;

	protected $fillable = ['entry_file_name', 'entry_file_updated_date', 'entry_file_size'];


	public function entry()
    {
        return $this->belongsTo('Entry', 'entry_file_entry_id');
    }
}