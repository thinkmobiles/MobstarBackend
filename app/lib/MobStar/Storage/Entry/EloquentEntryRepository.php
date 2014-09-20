<?php namespace MobStar\Storage\Entry;

use Entry;
use EntryFeedback;
use Tag;
use EntryTag;

class EloquentEntryRepository implements EntryRepository
{

	public function all( $user = 0, $category = 0, $tag = 0, $order_by = 0, $order = 'desc', $limit = 50, $offset = 0, $count = false )
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
		$query = Entry::with( 'category', 'file', 'vote', 'user', 'entryTag.tag', 'comment' )->whereIn( 'entry_id', $ids );

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

}