<?php

class ProfileContent extends \Eloquent
{
	protected $fillable = [ "content_description", "content_name", "content_deleted" ];
	public $timestamps = false;
	protected $primaryKey = 'content_id';

	public function user()
	{
		return $this->belongsTo( 'User', 'content_user_id' );
	}

	public function contentLikes()
	{
		return $this->hasMany( 'ContentLike', 'like_content_id' );
	}

	public function contentFile()
	{
		return $this->hasMany( 'ContentFile', 'content_file_content_id' );
	}

	public function contentComment()
	{
		return $this->hasMany( 'ContentComment', 'comment_content_id' )->where( 'comment_deleted', '=', '0' );
	}	
}