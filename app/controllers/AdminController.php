<?php

use MobStar\Storage\Entry\EntryRepository as Entry;
use MobStar\Storage\Token\TokenRepository as Token;

class AdminController extends BaseController
{
	public function __construct( Entry $entry, Token $token )
	{
		$this->entry = $entry;
		$this->token = $token;
	}

	public function index()
	{
		$entries = $this->entry->all_include_deleted(0,0,0,'entry_id');
		$data[ 'entries' ] = [ ];

		$client = getS3Client();

		foreach( $entries as $entry )
		{
			if( count( $entry->file ) == 0 )
			{
				continue;
			}
			$new = [ ];

			foreach( $entry->file as $file )
			{
				if( $entry->entry_type == 'video' )
				{
					if( $file->entry_file_type == 'mp4' )
					{
						$new[ 'entry_file' ] = $client->getObjectUrl( 'mobstar-1', $file->entry_file_name . "." . $file->entry_file_type, '+60 minutes' );
						$new[ 'entry_image' ] = $client->getObjectUrl( 'mobstar-1', 'thumbs/' . $file->entry_file_name . '-thumb.jpg', '+60 minutes' );

					}
				}
				elseif( $entry->entry_type == 'audio' )
				{
					if( $file->entry_file_type == 'mp3' )
					{
						$new[ 'entry_file' ] = $client->getObjectUrl( 'mobstar-1', $file->entry_file_name . "." . $file->entry_file_type, '+60 minutes' );
					}

					else
					{
						if( $file->entry_file_type == 'jpg' || $file->entry_file_type == 'png' )
						{
							$new[ 'entry_image' ] = $client->getObjectUrl( 'mobstar-1', $file->entry_file_name . "." . $file->entry_file_type, '+60 minutes' );
						}
					}
				}
				elseif( $entry->entry_type == 'image' )
				{
					if( $file->entry_file_type == 'jpg' || $file->entry_file_type == 'png' )
					{
						$new[ 'entry_image' ] = $client->getObjectUrl( 'mobstar-1', $file->entry_file_name . "." . $file->entry_file_type, '+60 minutes' );
					}
				}
			}

			$new[ 'entry_display_name' ] = $entry->user->user_display_name;
			$new[ 'entry_name' ] = $entry->entry_description;
			$new[ 'entry_type' ] = $entry->entry_type;
			$new[ 'entry_id' ] = $entry->entry_id;
			$new[ 'entry_deleted' ] = $entry->entry_deleted;

			$data[ 'entries' ][ ] = $new;

		}

		return View::make( 'admin.home', $data );
	}
}