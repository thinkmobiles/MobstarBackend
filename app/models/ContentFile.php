<?php

class ContentFile extends \Eloquent {

    protected $table = "content_files";
    protected $primaryKey = "content_file_id";
    public $timestamps = false;

	protected $fillable = ['content_file_name', 'content_file_updated_date', 'content_file_size'];


	public function profilecontent()
    {
        return $this->belongsTo('ProfileContent', 'content_file_content_id');
    }
}