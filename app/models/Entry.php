<?php

class Entry extends \Eloquent {
	protected $fillable = ["entry_description", "entry_category_id", "entry_sub_category_id", "entry_name"];
    public $timestamps = false;
    protected $primaryKey = 'entry_id';

	public function user()
    {
        return $this->belongsTo('User', 'entry_user_id');
    }

	public function category()
    {
        return $this->belongsTo('Category', 'entry_category_id');
    }

	public function subcategory()
    {
        return $this->belongsTo('SubCategory', 'entry_sub_category_id');
    }

    public function vote()
    {
        return $this->hasMany('Vote', 'vote_entry_id');
    }

    public function file()
    {
        return $this->hasMany('EntryFile', 'entry_file_entry_id');
    }

    public function reports()
    {
        return $this->hasMany('EntryReport', 'entry_file_entry_id');
    }

    public function entryTag()
    {
        return $this->hasMany('EntryTag', 'entry_tag_entry_id');
    }

}