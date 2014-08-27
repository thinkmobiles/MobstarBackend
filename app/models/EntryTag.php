<?php

class EntryTag extends \Eloquent {
	protected $fillable = ["entry_tag_id", "entry_tag_entry_id", "entry_tag_tag_id", "entry_tag_created_date", "entry_tag_deleted_date", "entry_tag_deleted"];
    public $timestamps = false;
    protected $primaryKey = 'entry_tag_id';

    public function entry()
    {
        return $this->belongsTo('Entry', 'entry_tag_entry_id');
    }

    public function tag()
    {
        return $this->belongsTo('Tag', 'tag_id');
    }
}