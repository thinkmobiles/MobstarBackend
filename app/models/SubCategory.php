<?php


class SubCategory extends \Eloquent  {

    protected $primaryKey = "sub_category_id";
    protected $table = "sub_categories";
    //protected $fillable = array('mentor_id', 'mentor_display_name');

    public function categories(){
    	return $this->belongsToMany('Category', 'join_categories_sub_categories', 'join_categories_sub_categories_sub_category', 'join_categories_sub_categories_category');
    }
}
