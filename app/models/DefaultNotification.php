<?php


class DefaultNotification extends \Eloquent  {

	protected $table = "default_notification";
    protected $primaryKey = "iDefaultNotificationId";
    protected $fillable = array('iDefaultNotificationId', 'vDefaultNotificationTitle');

    // public function categories(){
    // 	return $this->belongsToMany('Category', 'join_mentors_categories', 'join_mentors_categories_mentor_id', 'join_mentors_categories_category_id');
    // }
}
