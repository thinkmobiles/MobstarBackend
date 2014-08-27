<?php


class Mentor extends \Eloquent  {

    protected $primaryKey = "mentor_id";
    protected $fillable = array('mentor_id', 'mentor_display_name');

    public function categories(){
    	return $this->belongsToMany('Category', 'join_mentors_categories', 'join_mentors_categories_mentor_id', 'join_mentors_categories_category_id');
    }
}
