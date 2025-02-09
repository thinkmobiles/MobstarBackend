<?php

class Entry extends \Eloquent
{
	protected $fillable = [ "entry_description", "entry_category_id", "entry_sub_category_id", "entry_name", "entry_deleted" ];
	public $timestamps = false;
	protected $primaryKey = 'entry_id';

	public function user()
	{
		return $this->belongsTo( 'User', 'entry_user_id' );
	}

	public function category()
	{
		return $this->belongsTo( 'Category', 'entry_category_id' );
	}

	public function subcategory()
	{
		return $this->belongsTo( 'SubCategory', 'entry_sub_category_id' );
	}

	public function vote()
	{
		return $this->hasMany( 'Vote', 'vote_entry_id' );
	}

	public function file()
	{
		return $this->hasMany( 'EntryFile', 'entry_file_entry_id' );
	}

	public function reports()
	{
		return $this->hasMany( 'EntryReport', 'entry_file_entry_id' );
	}

	public function entryTag()
	{
		return $this->hasMany( 'EntryTag', 'entry_tag_entry_id' );
	}

	public function comments()
	{
		return $this->hasMany( 'Comment', 'comment_entry_id' )->where( 'comment_deleted', '=', '0' );
	}

	public function entryViews()
	{
		return $this->hasMany( 'EntryView', 'entry_view_entry_id' );
	}


	public static function addView( $entryId, $userId, $date = NULL )
	{
	    $view = new EntryView;
	    $view->entry_view_entry_id = $entryId;
	    $view->entry_view_user_id = $userId;
	    $view->entry_view_date = isset( $date ) ? $date : date( 'Y-m-d H:i:s' );

	    if ( ! $view->save() )
	        return false;

	    $viewTypeColumn = $userId == 1 ? 'entry_views_added' : 'entry_views';

	    \DB::table( 'entries' )->where( 'entry_id', $entryId )->increment( $viewTypeColumn );

	    return true;
	}


	public function viewsTotal()
	{
	    return $this->entry_views + $this->entry_views_added;
	}


	public function oneEntry( $entry, $session, $includeUser = false )
	{

		$client = getS3Client();

		$current = array();

		$up_votes = 0;
		$down_votes = 0;
		foreach( $entry->vote as $vote )
		{
			if( $vote->vote_up == 1 && $vote->vote_deleted == 0 )
			{
				$up_votes++;
			}
			elseif( $vote->vote_down == 1 && $vote->vote_deleted == 0 )
			{
				$down_votes++;
			}

		}

		$current[ 'id' ] = $entry->entry_id;
		if( $entry->entry_splitVideoId ) $current['splitVideoId'] = $entry->entry_splitVideoId;
		$current[ 'category' ] = $entry->category->category_name;
		$current[ 'type' ] = $entry->entry_type;

		if( $includeUser )
		{
			$current[ 'user' ][ 'userId' ] = $entry->entry_user_id;
			$current[ 'user' ][ 'userName' ] = $entry->User->user_name;
			$current[ 'user' ][ 'displayName' ] = $entry->User->user_display_name;
			$current[ 'user' ][ 'email' ] = $entry->User->user_email;
			$current[ 'user' ][ 'profileImage' ] = ( !empty( $entry->User->user_profile_image ) )
				? $_ENV[ 'URL' ] . "/" . $entry->User->user_profile_cover : "";
			$current[ 'user' ][ 'profileCover' ] = ( !empty( $entry->User->user_profile_cover ) )
				? $_ENV[ 'URL' ] . "/" . $entry->User->user_profile_cover : "";
			$current[ 'user' ][ 'isMyStar' ] = Star::where( 'user_star_user_id', '=', $session->token_user_id )->where( 'user_star_star_id', '=', $entry->entry_user_id )->count();
		}

		$current[ 'name' ] = $entry->entry_name;
		$current[ 'description' ] = $entry->entry_description;
		$current[ 'created' ] = $entry->entry_created_date;
		$current[ 'modified' ] = $entry->entry_modified_date;

		$current[ 'tags' ] = array();
		foreach( $entry->entryTag as $tag )
		{
			$current[ 'tags' ][ ] = Tag::find( $tag->entry_tag_tag_id )->tag_name;
		}

		//break;

		$current[ 'entryFiles' ] = array();
		foreach( $entry->file as $file )
		{
			$url = $client->getObjectUrl(Config::get('app.bucket'), $file->entry_file_name . "." . $file->entry_file_type, '+60 minutes');
			$current[ 'entryFiles' ][ ] = [
				'fileType' => $file->entry_file_type,
				'filePath' => $url ];
		}

		$current[ 'upVotes' ] = $up_votes;
		$current[ 'downVotes' ] = $down_votes;
		$current[ 'rank' ] = $entry->entry_rank;
		$current[ 'language' ] = $entry->entry_language;
		// /print_r($entry);

		if( $entry->entry_deleted )
		{
			$current[ 'deleted' ] = true;
		}
		else
		{
			$current[ 'deleted' ] = false;
		}

		return $current;
	}
}