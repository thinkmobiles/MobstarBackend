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
		$entries = $this->entry->all();
		$data['entries']= [];

		$client = getS3Client();

		foreach($entries as $entry)
		{
			$new = [];

			foreach($entry->file as $file)
			{
				if($entry->entry_type == 'video')
				{
					if($file->entry_file_type == 'mp4')
					{
						$new[ 'entry_file' ] = $client->getObjectUrl( 'mobstar-1', $file->entry_file_name . "." . $file->entry_file_type, '+60 minutes' );
					}
					else if($file->entry_file_type == 'jpg' || $file->entry_file_type == 'png')
					{
						$new['entry_image'] = $client->getObjectUrl( 'mobstar-1', $file->entry_file_name . "." . $file->entry_file_type, '+60 minutes' );
					}
				}
				elseif($entry->entry_type == 'audio')
				{
					if($file->entry_file_type == 'mp3')
					{
						$new['entry_file'] = $client->getObjectUrl( 'mobstar-1', $file->entry_file_name . "." . $file->entry_file_type, '+60 minutes' );
					}

					else if($file->entry_file_type == 'jpg' || $file->entry_file_type == 'png')
					{
						$new['entry_image'] = $client->getObjectUrl( 'mobstar-1', $file->entry_file_name . "." . $file->entry_file_type, '+60 minutes' );
					}
				}
				elseif($entry->entry_type == 'image')
				{
					if($file->entry_file_type == 'jpg' || $file->entry_file_type == 'png')
					{
						$new['entry_image'] = $client->getObjectUrl( 'mobstar-1', $file->entry_file_name . "." . $file->entry_file_type, '+60 minutes' );
					}
				}
			}

			$new['entry_display_name'] = $entry->user->user_display_name;
			$new['entry_name'] = $entry->description;
			$new['entry_type'] = $entry->entry_type;

			$data['entries'][] = $new;

		}

		return View::make('admin.home', $data);
	}
}