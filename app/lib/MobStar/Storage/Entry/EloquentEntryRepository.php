<?php namespace MobStar\Storage\Entry;

use Entry;
use EntryFeedback;
use Tag;
use EntryTag;
use DB;

class EloquentEntryRepository implements EntryRepository
{

	public function all( $user = 0, $category = 0, $tag = 0, $exclude = 0, $order_by = 0, $order = 'desc', $limit = 50, $offset = 0, $count = false, $withAll = true)
	{
		if($withAll)
		{
			$query = Entry::with( 'category', 'vote', 'user', 'file', 'entryTag.tag', 'comments' )->where( 'entry_id', '>', '0' );
		}
		else
		{
			$query = Entry::where( 'entry_id', '>', '0' );
		}

		$query = $query->where( 'entry_deleted', '=', '0' );


		if( $user )
		{
			$query = $query->where( 'entry_user_id', '=', $user );
		}

		if( $category )
		{
			$query = $query->where( 'entry_category_id', '=', $category );
		}

		if( $count )
		{
			return $query->count();
		}

		if( $tag )
		{
			$query = $query->whereHas( 'entryTag', function ( $q ) use ( $tag )
			{
				$q->where( 'entry_tag_tag_id', '=', $tag );
			} );
		}

		if( $exclude )
		{
			$query = $query->whereNotIn( 'entry_id', $exclude );
		}

		//echo $order_by;
		if( $order_by )
		{
			$query = $query->orderBy( $order_by, $order );
		}

		return $query->take( $limit )->skip( $offset )->get();
	}


	public function allWithGeoLocation( $geoLocationFilter = 0, $userId = 0, $categoryId = 0, $tagId = 0, $exclude = array(), $order_by = 0, $order = 'desc', $limit= 50, $offset = 0, $count = false, $withAll = true)
	{
	    if( $withAll ) {
	        $query = Entry::with('category', 'vote', 'user', 'file', 'entryTag.tag', 'comments');
	        $query = $query->where( 'entry_deleted', '=', 0 );
	    } else {
	        $query = Entry::where( 'entry_deleted', '=', 0 );
	    }

	    if( $tagId )
	    {
	        $query = $query->whereHas( 'entryTag', function ( $q ) use ( $tagId )
	        {
	            $q->where( 'entry_tag_tag_id', '=', $tagId );
	        } );
	    }


	    if( $geoLocationFilter )
	    {
	        if( ! is_array( $geoLocationFilter ) ) $geoLocationFilter = array( $geoLocationFilter );
	        if( \Config::get( 'app.force_include_all_world', false ) ) $geoLocationFilter[] = 0;
	        $query->whereIn( 'entry_continent', $geoLocationFilter );
	    }


	    if( $userId )
	    {
	        $query->where( 'entry_user_id', '=', (int)$userId );
	    }

	    if( $categoryId )
	    {
	        $query->where( 'entry_category_id', '=', (int)$categoryId );
	    }

	    // process excludes
	    if( $exclude ) $query = $this->addExcludeRules( $query, $exclude );

	    // @todo we can skip adding profile entries if there is no 'category' == 7 in exclude
	    // add entries from profile to home feedback
	    if( empty( $categoryId ) && empty( $userId ) && empty( $tagId ) ) // home feedback
	    {
	        $max_media_duration_for_home_feed =
	        isset( $_ENV['MAX_MEDIA_DURATION_FOR_HOME_FEED'] )
	        ? (int)$_ENV['MAX_MEDIA_DURATION_FOR_HOME_FEED']
	        : 0;

	        if( $max_media_duration_for_home_feed > 0 )
	        {
	            // Append profile entries using 'or'
	            // but also applaing exclude rules except 'category'
	            // (we already use only 7 category

	            // remove 'category' from exclude
	            $localExclude = $exclude;
	            unset( $localExclude['category'] );

	            $self = $this;

	            $query->orWhere( function( $query )
	                use( $max_media_duration_for_home_feed, $self, $localExclude, $geoLocationFilter ) {
	                $query->where( 'entry_category_id', '=', 7 )
	                ->where( 'entry_deleted', '=', 0 )
	                ->whereIn( 'entry_type', array( 'video', 'audio' ) )
	                ->whereBetween( 'entry_duration', array( 0, $max_media_duration_for_home_feed ) );

	                if( $geoLocationFilter )
	                {
	                    if( ! is_array( $geoLocationFilter ) ) $geoLocationFilter = array( $geoLocationFilter );
	                    if( \Config::get( 'app.force_include_all_world', false ) ) $geoLocationFilter[] = 0;
	                    $query->whereIn( 'entry_continent', $geoLocationFilter );
	                }

	                $self->addExcludeRules( $query, $localExclude );
	            });
	            unset( $localExclude );
	            unset( $self );
	        }
	    }

	    if( $count )
	    {
	        return $query->count();
	    }

	    // add order by
	    if( $order_by )
	    {
	        $query->orderBy( $order_by, $order);
	    }

	    // add limit
	    if( $offset ) $query->skip( $offset );
	    if( $limit ) $query->take( $limit );

	    $entries = $query->get();

	    return $entries;
	}


	public function allComplexExclude_convertToIds($user = 0, $category = 0, $tag = 0, $exclude = 0, $order_by = 0, $order = 'desc', $limit = 50, $offset = 0, $count = false, $withAll = true)
	{
	  if( ! $this->isExcludeArrayOfIds( $exclude ) )
	  {
	    $exclude = $this->getExcludeIdsForComplexExclude( $exclude ); // get ids for exclude

	    // @todo we can skip adding profile entries if there is no 'category' == 7 in exclude
	    if( empty( $category ) && empty( $user ) && empty( $tag ) ) // home feedback
	    {
	      $max_media_duration_for_home_feed =
	        isset( $_ENV['MAX_MEDIA_DURATION_FOR_HOME_FEED'] )
	          ? (int)$_ENV['MAX_MEDIA_DURATION_FOR_HOME_FEED']
	          : 0;

	      if( $max_media_duration_for_home_feed > 0 )
	      {
	        // remove profile entries from exclude
	        $include_entries = DB::table('entries')
	          ->where( 'entry_category_id', '=', 7 )
	          ->whereIn( 'entry_type', array( 'video', 'audio' ) )
	          ->whereBetween( 'entry_duration', array( 0, $max_media_duration_for_home_feed ) )
	          ->lists( 'entry_id' );
	        $exclude_hash = array_flip( $exclude );
	        foreach( $include_entries as $entry_id )
	        {
	          unset( $exclude_hash[ $entry_id ] );
	        }
	        $exclude = array_keys( $exclude_hash );
	      }
	    }
	  }

	  if($withAll)
	  {
	    $query = Entry::with( 'category', 'vote', 'user', 'file', 'entryTag.tag', 'comments' )->where( 'entry_id', '>', '0' );
	  }
	  else
	  {
	    $query = Entry::where( 'entry_id', '>', '0' );
	  }

	  $query = $query->where( 'entry_deleted', '=', '0' );


	  if( $user )
	  {
	    $query = $query->where( 'entry_user_id', '=', $user );
	  }

	  if( $category )
	  {
	    $query = $query->where( 'entry_category_id', '=', $category );
	  }

	  if( $tag )
	  {
	    $query = $query->whereHas( 'entryTag', function ( $q ) use ( $tag )
	    {
	      $q->where( 'entry_tag_tag_id', '=', $tag );
	    } );
	  }

	  if( $exclude )
	  {
	    $query = $query->whereNotIn( 'entry_id', $exclude );
	  }

	  if( $count )
	  {
	    return $query->count();
	  }

	  //echo $order_by;
	  if( $order_by )
	  {
	    $query = $query->orderBy( $order_by, $order );
	  }

	  return $query->take( $limit )->skip( $offset )->get();
	}


	private function isExcludeArrayOfIds( $exclude )
	{
	  if( empty( $exclude ) ) return false;
	  if( ! is_array( $exclude ) ) return true; // simple entry id to exclude

	  if( array_key_exists( 'category', $exclude)
	    or array_key_exists( 'excludeVotes', $exclude)
	    or array_key_exists( 'notPopular', $exclude))
	  {
	    return false;
	  }

	  return true;
	}


	// $exclude is not array of ids, but has keys like 'category' or 'excludeVotes'
	public function allComplexExclude( $userId = 0, $categoryId = 0, $tagId = 0, $exclude = array(), $order_by = 0, $order = 'desc', $limit= 50, $offset = 0, $count = false, $withAll = true )
	{

	  if( $withAll ) {
	    $query = Entry::with('category', 'vote', 'user', 'file', 'entryTag.tag', 'comments');
	    $query = $query->where( 'entry_deleted', '=', 0 );
	  } else {
	    $query = Entry::where( 'entry_deleted', '=', 0 );
	  }

	  if( $tagId )
	  {
	    $query = $query->whereHas( 'entryTag', function ( $q ) use ( $tagId )
	    {
	      $q->where( 'entry_tag_tag_id', '=', $tagId );
	    } );
	  }

	  if( $userId )
	  {
	    $query->where( 'entry_user_id', '=', (int)$userId );
	  }

	  if( $categoryId )
	  {
	    $query->where( 'entry_category_id', '=', (int)$categoryId );
	  }

      // process excludes
      if( $exclude ) $query = $this->addExcludeRules( $query, $exclude );

      // @todo we can skip adding profile entries if there is no 'category' == 7 in exclude
	  // add entries from profile to home feedback
	  if( empty( $categoryId ) && empty( $userId ) && empty( $tagId ) ) // home feedback
	  {
	    $max_media_duration_for_home_feed =
	      isset( $_ENV['MAX_MEDIA_DURATION_FOR_HOME_FEED'] )
	      ? (int)$_ENV['MAX_MEDIA_DURATION_FOR_HOME_FEED']
	      : 0;

	    if( $max_media_duration_for_home_feed > 0 )
	    {
	      // Append profile entries using 'or'
	      // but also applaing exclude rules except 'category'
	      // (we already use only 7 category

	      // remove 'category' from exclude
	      $localExclude = $exclude;
	      unset( $localExclude['category'] );

	      $self = $this;

	      $query->orWhere( function( $query )
	        use( $max_media_duration_for_home_feed, $self, $localExclude ) {
	        $query->where( 'entry_category_id', '=', 7 )
	          ->where( 'entry_deleted', '=', 0 )
	          ->whereIn( 'entry_type', array( 'video', 'audio' ) )
	          ->whereBetween( 'entry_duration', array( 0, $max_media_duration_for_home_feed ) );

	        $self->addExcludeRules( $query, $localExclude );
	      });
	      unset( $localExclude );
	      unset( $self );
	    }
	  }

	  if( $count )
	  {
	    return $query->count();
	  }

	  // add order by
	  if( $order_by )
	  {
	    $query->orderBy( $order_by, $order);
	  }

	  // add limit
	  if( $offset ) $query->skip( $offset );
	  if( $limit ) $query->take( $limit );

	  $entries = $query->get();

	  return $entries;
	}


	public function addExcludeRules( $query, $exclude = array() )
	{
	  if( empty( $exclude ) or (!is_array( $exclude )) ) return $query;

	  foreach( $exclude as $field => $value )
	  {
	    switch( $field )
	    {
	      case 'category': // exclude category
	        if( is_array( $value ) )
	        {
	          $query->whereNotIn( 'entry_category_id', $value );
	        } else {
	          $query->where( 'entry_category_id', '<>', $value );
	        }
	        break;

	      case 'excludeVotes': // $value is loggined user id
	        if( empty( $value ) ) continue;
	        // skip entries, voted down by user
	        $query->whereNotExists( function( $query ) use( $value ) {
	          $query->select( 'vote_entry_id' )
	          ->from( 'votes' )
	          ->where( 'vote_user_id', '=', $value )
	          ->where( 'vote_deleted', '=', 0 )
	          ->where( 'vote_down', '>', 0 )
	          ->whereRaw( 'vote_entry_id = entry_id' );
	        });
	        break;

	      case 'notPopular':
	        if( empty( $value ) ) continue;
	        $query->where( 'entry_rank', '<>', 0 );
	        break;

	      default:
	        error_log( 'skipping unknown exclude field: '.$field );
	    }
	  }

	  return $query;
	}


	public function getExcludeIdsForComplexExclude( $exclude )
	{
	  if( empty( $exclude ) or (!is_array( $exclude )) ) return array();

	  $query = DB::table('entries');

	  foreach( $exclude as $field => $value )
	  {
	    switch( $field )
	    {
	      case 'category': // exclude category
	        if( is_array( $value ) )
	        {
	          $query->orWhereIn( 'entry_category_id', $value );
	        } else {
	          $query->orWhere( 'entry_category_id', '==', $value );
	        }
	        break;

	      case 'excludeVotes': // $value is loggined user id
	        if( empty( $value ) ) continue;
	        // add entries, voted down by user
	        $query->orWhereExists( function( $query ) use( $value ) {
	          $query->select( 'vote_entry_id' )
	          ->from( 'votes' )
	          ->where( 'vote_user_id', '=', $value )
	          ->where( 'vote_deleted', '=', 0 )
	          ->where( 'vote_down', '>', 0 )
	          ->whereRaw( 'vote_entry_id = entry_id' );
	        });
	        break;

	      case 'notPopular':
	        // add not popular entries
	        if( empty( $value ) ) continue;
	        $query->orWhere( 'entry_rank', '=', 0 );
	        break;

	      default:
	        error_log( 'skipping unknown exclude field: '.$field );
	    }
	  }

	  return $query->lists( 'entry_id' );

	}


	public function rerankall( $user = 0, $category = 0, $tag = 0, $exclude = 0, $order_by = 0, $order = 'desc', $limit = 50, $offset = 0, $count = false, $withAll = true)
	{
		if($withAll)
		{
			$query = Entry::with( 'category', 'user', 'file', 'entryTag.tag' )->where( 'entry_id', '>', '0' );
		}
		else
		{
			$query = Entry::where( 'entry_id', '>', '0' );
		}

		$query = $query->where( 'entry_deleted', '=', '0' );


		if( $user )
		{
			$query = $query->where( 'entry_user_id', '=', $user );
		}

		if( $category )
		{
			$query = $query->where( 'entry_category_id', '=', $category );
		}

		if( $count )
		{
			return $query->count();
		}

		if( $tag )
		{
			$query = $query->whereHas( 'entryTag', function ( $q ) use ( $tag )
			{
				$q->where( 'entry_tag_tag_id', '=', $tag );
			} );
		}

		if( $exclude )
		{
			$query = $query->whereNotIn( 'entry_id', $exclude );
		}

		//echo $order_by;
		if( $order_by )
		{
			$query = $query->orderBy( $order_by, $order );
		}

		return $query->take( $limit )->skip( $offset )->get();
	}
	public function all_include_deleted( $user = 0, $category = 0, $tag = 0, $exclude = 0, $order_by = 0, $order = 'desc', $limit = 50, $offset = 0, $count = false )
	{
		$query = Entry::with( 'category', 'vote', 'user', 'file', 'entryTag.tag', 'comments' )->where( 'entry_id', '>', '0' );

		if( $user )
		{
			$query = $query->where( 'entry_user_id', '=', $user );
		}

		//echo $order_by;
		if( $order_by )
		{
			$query = $query->orderBy( $order_by, $order );
		}

		if( $category )
		{
			$query = $query->where( 'entry_category_id', '=', $category );
		}

		if( $count )
		{
			return $query->count();
		}

		if( $tag )
		{
			$query = $query->whereHas( 'entryTag', function ( $q ) use ( $tag )
			{
				$q->where( 'entry_tag_tag_id', '=', $tag );
			} );
		}

		if( $exclude )
		{
			$query = $query->whereNotIn( 'entry_id', $exclude );
		}

		return $query->take( $limit )->skip( $offset )->get();
	}

	public function find( $id )
	{
		return Entry::find( $id );
	}

	public function create( $input )
	{
		return Entry::create( $input );
	}

	public function whereIn( $ids, $user = 0, $category = 0, $limit = 50, $offset = 0, $count = false )
	{
		$query = Entry::with( 'category', 'file', 'vote', 'user', 'entryTag.tag', 'comments' )->whereIn( 'entry_id', $ids );

		$query = $query->where( 'entry_deleted', '=', '0' );

		if( $user )
		{
			$query = $query->where( 'entry_user_id', '=', $user );
		}

		if( $category )
		{
			$query = $query->where( 'entry_category_id', '=', $category );
		}

		if( $count )
		{
			return $query->count();
		}

		return $query->take( $limit )->skip( $offset )->get();
	}

	public function update( $update, $id, $user_id )
	{
		$entry = Entry::where( 'entry_id', '=', $id )->where( 'entry_user_id', '=', $user_id )->first();

		if( !$entry )
		{
			return false;
		}

		$entry->update( $update );
		$entry->save();

		return true;
	}

	public function addTag( $tags, $id, $user_id )
	{

		// foreach ($tags as $tag)
		// {

		$tag = Tag::firstOrNew( array( 'tag_name' => $tags ) );

		if( is_null( $tag->tag_created_date ) )
		{
			$tag->tag_created_date = date( 'Y-m-d H:i:s' );
		}

		if( is_null( $tag->tag_added_by ) )
		{
			$tag->tag_added_by = $user_id;
		}

		$tag->save();

		$entryTag = EntryTag::firstOrNew( [
											  'entry_tag_tag_id'   => $tag->tag_id,
											  'entry_tag_entry_id' => $id,
											  'entry_tag_added_by' => $user_id
										  ] );

		if( is_null( $entryTag->entry_tag_created_date ) )
		{
			$entryTag->entry_tag_created_date = date( 'Y-m-d H:i:s' );
		}

		$entryTag->save();

		return true;
		// }
	}

	public function feedback( $user = 0, $entry = 0, $order_by = 0, $order = 'desc', $limit = 50, $offset = 0, $count = false )
	{
		$query = EntryFeedback::with( 'user', 'entry', 'entry.category', 'entry.vote', 'entry.user', 'entry.file', 'entry.entryTag.tag', 'entry.comments' )->where( 'entry_feedback_id', '>', '0' );

		if( $user )
		{
			$query = $query->where( 'entry_feedback_user_id', '=', $user );
		}

		if( $entry )
		{
			$query = $query->where( 'entry_feedback_entry_id', '=', $entry );
		}

		//echo $order_by;
		if( $order_by )
		{
			$query = $query->orderBy( $order_by, $order );
		}

		if( $count )
		{
			return $query->count();
		}

		return $query->take( 50 )->skip( 0 )->get();
	}

	public function search( $term )
	{

		$tags = explode( ' ', $term );

		$tag_id = Tag::whereIn( 'tag_name', $tags )->lists( 'tag_id' );

		$query = Entry::whereRaw(
			"MATCH(entry_name, entry_description) AGAINST(? IN BOOLEAN MODE)",
			array( $term )
		)->where( 'entry_deleted', '=', 0 );

		if( count( $tag_id ) > 0 )
		{
			$query = $query->orWhereHas( 'entryTag', function ( $q ) use ( $tag_id )
			{
				$q->whereIn( 'entry_tag_tag_id', $tag_id )->where( 'entry_deleted', '=', 0 );
			} );
		}

		return $query->get();
	}

	public function undecided( $count )
	{

	}

	public function delete( $id )
	{
		$entry = Entry::find( $id );

		$entry->entry_deleted = 1;

		$entry->save();
	}

	public function undelete( $id )
	{
		$entry = Entry::find( $id );

		$entry->entry_deleted = 0;

		$entry->save();
	}

}