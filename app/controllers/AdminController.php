<?php

use MobStar\Storage\Entry\EntryRepository as Entry;

class AdminController extends BaseController
{
	public function __construct( Entry $entry )
	{
		$this->entry = $entry;
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
					if( strtolower($file->entry_file_type) == 'jpg' || strtolower($file->entry_file_type) == 'png' )
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

	public function addEntry(){
		return View::make('admin.addEntry');
	}

	public function insertEntry(){

		//Validate Input
		$rules = array(
			'type'        => 'required',
			'description' => 'required',
		);

		$validator = Validator::make( Input::get(), $rules );

		if( $validator->fails() )
		{
			$response[ 'errors' ] = $validator->messages();
			var_dump($response);
			$status_code = 400;
		}
		else
		{

			$file = Input::file( 'file1' );

			if( !empty( $file ) )
			{

				//Will need to do some work here to upload file to CDN

				//Get input
				$input = [
					'entry_user_id'      => 301,
					'entry_category_id'  => 1,
					'entry_type'         => Input::get( 'type' ),
					'entry_name'         => 'Mobstar Team',
					'entry_language'     => Input::get( 'language' ),
					'entry_description'  => Input::get( 'description' ),
					'entry_created_date' => date( '2015-m-d H:i:s' ),
					'entry_deleted'			=> (Input::get('enabled') == 1) ? 0 : 1
				];

				Eloquent::unguard();
				$response[ 'entry_id' ] = $this->entry->create( $input )->entry_id;
				$status_code = 201;
				Eloquent::reguard();

				$tags = Input::get( 'tags' );

				if( isset( $tags ) )
				{
					$tags = array_values( explode( ',', $tags ) );

					foreach( $tags as $tag )
					{
						$this->entry->addTag( trim( $tag ), $response[ 'entry_id' ], 301 );
					}
				}

				$dest = 'uploads';

				$filename = str_random( 12 );

				$extension = $file->getClientOriginalExtension();

				//old method was just to move the file, and not encode it
//				$file->move($dest, $filename . '.' . $extension);

				if( $input[ 'entry_type' ] == 'audio' )
				{
					$file_in = $file->getRealPath();
					$file_out = $_ENV[ 'PATH' ] . 'public/uploads/' . $filename . '.mp3';

					// Transcode Audio
					shell_exec( '/usr/bin/ffmpeg -i ' . $file_in . ' -strict -2 ' . $file_out );

					$extension = 'mp3';

					$handle = fopen( $file_out, "r" );

					Flysystem::connection( 'awss3' )->put( $filename . "." . $extension, fread( $handle, filesize( $file_out ) ) );

					unlink( $file_out );

				}
				else
				{
					if( $input[ 'entry_type' ] == 'video' )
					{

						$file_in = $file->getRealPath();

						$file_out = $_ENV[ 'PATH' ] . 'public/uploads/' . $filename . '.mp4';

						// Transcode Video
						shell_exec( '/usr/bin/ffmpeg -i ' . $file_in . ' -vf scale=306:306 -strict -2 ' . $file_out . ' 2>' . $_ENV[ 'PATH' ] . 'public/uploads/' . $filename . '-log.txt' );
						$file->move( $_ENV[ 'PATH' ] . 'public/uploads/', $filename . '-uploaded.' . $extension );

						$extension = 'mp4';

						$handle = fopen( $file_out, "r" );

						Flysystem::connection( 'awss3' )->put( $filename . "." . $extension, fread( $handle, filesize( $file_out ) ) );

						$thumb = $_ENV[ 'PATH' ] . 'public/uploads/' . $filename . '-thumb.jpg';

						exec( '/usr/bin/ffprobe 2>&1 ' . $file_out . ' | grep "rotate          :"', $rotation );

						if( isset( $rotation[ 0 ] ) )
						{
							$rotation = substr( $rotation[ 0 ], 17 );
						}

						$contents = file_get_contents( $_ENV[ 'PATH' ] . 'public/uploads/' . $filename . '-log.txt' );
						preg_match( "#rotate.*?([0-9]{1,3})#im", $contents, $rotationMatches );

						$transpose = '';

						if( count( $rotationMatches ) > 0 )
						{
							switch( $rotationMatches[ 1 ] )
							{
								case '90':
									$transpose = ' -vf transpose=1';
									break;
								case '180':
									$transpose = ' -vf vflip,hflip';
									break;
								case '270':
									$transpose = ' -vf transpose=2';
									break;
							}
						}

						shell_exec( '/usr/bin/ffmpeg -i ' . $file_out . $transpose . ' -vframes 1 -an -vf scale=300:-1 -ss 00:00:00.10 ' . $thumb );

						$handle = fopen( $thumb, "r" );

						Flysystem::connection( 'awss3' )->put( "thumbs/" . $filename . "-thumb.jpg", fread( $handle, filesize( $thumb ) ) );

//						unlink($file_out);
//						unlink($thumb);
					}
					else
					{
						//File is an image

						$file_in = $file->getRealPath();

						$file_out = $_ENV[ 'PATH' ] . "public/uploads/" . $filename . '.' . $extension;

						$image = Image::make( $file_in );

						$image->widen( 350 );

						$image->save( $file_out );

						$handle = fopen( $file_out, "r" );

						Flysystem::connection( 'awss3' )->put( $filename . "." . $extension,
															   fread( $handle,
																	  filesize( $file_out ) ) );
					}
				}

				Eloquent::unguard();

				EntryFile::create( [
									   'entry_file_name'         => $filename,
									   'entry_file_entry_id'     => $response[ 'entry_id' ],
									   'entry_file_location'     => $dest,
									   'entry_file_type'         => $extension,
									   'entry_file_created_date' => date( 'Y-m-d H:i:s' ),
									   'entry_file_updated_date' => date( 'Y-m-d H:i:s' ),
								   ] );

				Eloquent::reguard();

				$file = Input::file( 'file2' );

				if( !empty( $file ) && $file->isValid() )
				{
					$dest = 'uploads';

					$file_in = $file->getRealPath();

					$extension = ".jpg";

					$file_out = $_ENV[ 'PATH' ] . "public/uploads/" . $filename . '.' . $extension;

					$image = Image::make( $file_in );

					$image->widen( 350 );

					$image->save( $file_out, 80 );

					$handle = fopen( $file_out, "r" );

					Flysystem::connection( 'awss3' )->put( $filename . "." . $extension,
														   fread( $handle,
																  filesize( $file_out ) ) );

					unlink( $file_out );

					Eloquent::unguard();

					EntryFile::create( [
										   'entry_file_name'         => $filename,
										   'entry_file_entry_id'     => $response[ 'entry_id' ],
										   'entry_file_location'     => $dest,
										   'entry_file_type'         => $extension,
										   'entry_file_created_date' => date( 'Y-m-d H:i:s' ),
										   'entry_file_updated_date' => date( 'Y-m-d H:i:s' ),
									   ] );

					Eloquent::reguard();
				}
			}
			else
			{

				$response[ 'error' ] = "No file included";
				$status_code = 400;

			}

			return Redirect::to( 'admin' );

		}
	}
}