<?php namespace MobStar\Storage\Entry;

use Entry;
use EntryFeedback;
use Tag;
use EntryTag;

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