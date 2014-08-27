<?php

class Tag extends \Eloquent {
    protected $fillable = ["tag_id", "tag_name", "tag_created_date", "tag_crated_by"];

    public $timestamps = false;
    protected $primaryKey = 'tag_id';

    public function entry_tag()
    {
        return $this->hasMany('EntryTag', 'tag_id', 'entry_tag_tag_id');
    }
    
}