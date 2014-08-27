<?php


class Category extends \Eloquent  {

    protected $table = "categories";
    public $primaryKey = "category_id";

    public function mentors(){
    	//return $this->belongsToMany('Mentor', 'join_mentors_categories', 'join_mentors_categories_mentor_id', 'join_mentors_categories_category_id');
    	return $this->belongsToMany('Mentor', 'join_mentors_categories', 'join_mentors_categories_category_id', 'join_mentors_categories_mentor_id');
    }


    public function subCategories(){
    	//return $this->belongsToMany('Mentor', 'join_mentors_categories', 'join_mentors_categories_mentor_id', 'join_mentors_categories_category_id');
    	return $this->belongsToMany('SubCategory', 'join_categories_sub_categories', 'join_categories_sub_categories_category', 'join_categories_sub_categories_sub_category');
    }
}