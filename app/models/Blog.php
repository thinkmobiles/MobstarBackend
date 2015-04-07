<?php


class Blog extends \Eloquent  {

	protected $table = "blogs";
    protected $primaryKey = "iBlogId";
    protected $fillable = array('vBlogTitle', 'vBlogHeader', 'vBlogImage', 'txDescription', 'tsCreatedAt');

}
