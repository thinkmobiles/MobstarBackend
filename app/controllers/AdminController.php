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
		$entries = $this->entry->all_include_deleted(0,0,0,0,'entry_id');
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

	public function login()
	{
		return View::make( 'admin.login');
	}

	public function validate()
	{
		$rules = array(
			'email'    => 'required|email', // make sure the email is an actual email
			'password' => 'required|alphaNum|min:3', // password can only be alphanumeric and has to be greater than 3 characters
		);

		// run the validation rules on the inputs
		$validator = Validator::make( Input::all(), $rules );

		// if the validator fails, return errors
		if( !$validator->fails() )
		{
			$userdata = array(
				'user_email' => Input::get( 'email' ),
				'password'   => Input::get( 'password' )
			);

			if( Auth::attempt( $userdata ) )
			{

				//Create Session
				$session_key = str_random( 40 );
				$token = array(
					'token_value'        => $session_key,
					'token_created_date' => date( "Y-m-d H:i:s" ),
					'token_valid_until'  => date( "Y-m-d H:i:s", strtotime( "now + 1 hour" ) ),
					'token_user_id'      => Auth::user()->user_id
				);

				Token::create( $token );

				Session::put( 'pass', $session_key );

				return Redirect::to( 'admin' );
			}
		}

		return Redirect::to('admin/login');

	}
}