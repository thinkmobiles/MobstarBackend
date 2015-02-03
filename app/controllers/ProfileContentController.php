<?php
use MobStar\Storage\Token\TokenRepository as Token;
use Swagger\Annotations as SWG;
use Aws\S3\S3Client;
use Aws\Common\Credentials\Credentials as Creds;
use Aws\Sns\SnsClient;

class ProfileContentController extends BaseController
{
	public $valid_fields = [ "id", "userId", "type", "name", "description", "created", "modified", "contentFiles", "likes", "userName" ];

	public function __construct( ProfileContent $profilecontent, Token $token )
	{
		$this->profilecontent = $profilecontent;
		$this->token = $token;
	}
	public function store()
	{

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		//Validate Input
		$rules = array(
			'type'        => 'required',
			'name'        => 'required',
			'description' => 'required',
		);

		$validator = Validator::make( Input::get(), $rules );

		if( $validator->fails() )
		{
			$response[ 'errors' ] = $validator->messages();
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
					'content_user_id'      => $session->token_user_id,
					'content_type'         => Input::get( 'type' ),
					'content_name'         => Input::get( 'name' ),
					'content_description'  => Input::get( 'description' ),
					'content_created_date' => date( 'Y-m-d H:i:s' ),
				];

				Eloquent::unguard();
				$response[ 'content_id' ] = $this->profilecontent->create( $input )->content_id;
				$status_code = 201;
				Eloquent::reguard();				

				$dest = 'uploads';

				$filename = str_random( 12 );

				$extension = $file->getClientOriginalExtension();

				if( $input[ 'content_type' ] == 'audio' )
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
					if( $input[ 'content_type' ] == 'video' )
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

						shell_exec( '/usr/bin/ffmpeg -i ' . $file_out . $transpose . ' -vframes 1 -an -s 300x300 -ss 00:00:00.10 ' . $thumb );

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

				ContentFile::create( [
									   'content_file_name'         => $filename,
									   'content_file_content_id'     => $response[ 'content_id' ],
									   'content_file_location'     => $dest,
									   'content_file_type'         => $extension,
									   'content_file_created_date' => date( 'Y-m-d H:i:s' ),
									   'content_file_updated_date' => date( 'Y-m-d H:i:s' ),
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

					ContentFile::create( [
										   'content_file_name'         => $filename,
										   'content_file_content_id'     => $response[ 'content_id' ],
										   'content_file_location'     => $dest,
										   'content_file_type'         => $extension,
										   'content_file_created_date' => date( 'Y-m-d H:i:s' ),
										   'content_file_updated_date' => date( 'Y-m-d H:i:s' ),
									   ] );

					Eloquent::reguard();
				}
			}
			else
			{

				$response[ 'error' ] = "No file included";
				$status_code = 400;
			}

		}

		return Response::make( $response, $status_code );
	}
	public function pushmessage()
	{
		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );
		
		$arn = "arn:aws:sns:eu-west-1:830026328040:app/APNS_SANDBOX/DevelopmentPush";
		$client = getSNSClient();
		$result1 = $client->listEndpointsByPlatformApplication(array(
		    // PlatformApplicationArn is required
		    'PlatformApplicationArn' => $arn,
		));

		foreach($result1['Endpoints'] as $Endpoint){
			$EndpointArn = $Endpoint['EndpointArn']; 
			$EndpointToken = $Endpoint['Attributes'];
			foreach($EndpointToken as $key=>$newVals){
				if($key=="Token"){
					if('7d63172f1eed74ae8a8992b4c6f2d44c0e391439a6a02f0b1c156e80c432d1cb'==$newVals){
					//Delete ARN
						$result = $client->deleteEndpoint(array(
							// EndpointArn is required
							'EndpointArn' => $EndpointArn,
						));
					}
				}
			}
		}

		$endpoint = $client->createPlatformEndpoint( [
													'PlatformApplicationArn' =>
														$arn,
													'Token'                  =>'7d63172f1eed74ae8a8992b4c6f2d44c0e391439a6a02f0b1c156e80c432d1cb'
														//$device->device_registration_device_token
												] );

		$endpointDetails = $endpoint->toArray();
		try
		{
			$response = $client->publish( [
								  'TargetArn'          => $endpointDetails['EndpointArn'],
								  'MessageStructure' => 'json',
								  'Message'            => json_encode(array(
									'default' => 'Hello World!!!'
									)),
								  'Subject'            => 'MobStar',
								  'MessageAttributues' => [
									  'String' => [
										  'DataType' => 'string',
									  ]
								  ]
							  ] );
		print($EndpointArn . " - Succeeded!\n"); 
		}
		catch (Exception $e)
		{
			print($EndpointArn . " - Failed: " . $e->getMessage() . "!\n");
		}  
		//log contents	
	}	
}