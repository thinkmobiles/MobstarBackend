<?php

use Swagger\Annotations as SWG;
use Aws\Sns\SnsClient;

/**
 * @package
 * @category
 * @subpackage
 *
 * @SWG\Resource(
 *  apiVersion=0.2,
 *  swaggerVersion=1.2,
 *  resourcePath="/login",
 *  basePath="http://api.mobstar.com"
 * )
 */
class LoginController extends BaseController
{

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */

	/**
	 *
	 * @SWG\Api(
	 *   path="/login",
	 *   description="Operations to log user in or out",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Log user in",
	 *       nickname="logIn",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="email",
	 *           description="Registered users email address",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *			@SWG\Parameter(
	 *           name="password",
	 *           description="Registered users password",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *			@SWG\Parameter(
	 *           name="deviceToken",
	 *           description="Device token for push notifications",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *			@SWG\Parameter(
	 *           name="device",
	 *           description="Device token for push notifications",
	 *           paramType="form",
	 *           required=true,
	 *             enum="['apple', 'google']",
	 *           type="string"
	 *         )
	 *       ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          )
	 *        )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function index()
	{
		// validate the info, create rules for the inputs
		$rules = array(
			'email'    => 'required|email', // make sure the email is an actual email
			'password' => 'required|alphaNum|min:3', // password can only be alphanumeric and has to be greater than 3 characters
			'device'   => 'in:apple,google', // device type, must be google or apple
//			'deviceToken'    => 'required' // token is required
		);

		// run the validation rules on the inputs
		$validator = Validator::make( Input::all(), $rules );

		// if the validator fails, return errors
		if( $validator->fails() )
		{
			$return = $validator->messages();
			$status_code = 401;
		}
		else
		{

			// create our user data for the authentication
			$userdata = array(
				'user_email' => Input::get( 'email' ),
				'password'   => Input::get( 'password' )
			);

			// attempt to do the login
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

				$deviceToken = Input::get( 'deviceToken' );
				$deviceType = Input::get( 'device' );

				if( isset( $deviceType ) && isset( $deviceToken ) )
				{

					$device = DeviceRegistration::firstOrNew(
						[ 'device_registration_device_token' => $deviceToken ]
					);

					$device->device_registration_user_id = Auth::user()->user_id;
					$device->device_registration_device_type = $deviceType;
					$device->device_registration_device_token = $deviceToken;
					$device->device_registration_date_created = date( "Y-m-d H:i:s" );

					$device->save();

					$this->registerSNSEndpoint( $device );

				}

				Token::create( $token );
				$isVerifiedPhone = DB::table('user_phones')->where('user_phone_user_id', '=', Auth::user()->user_id)->pluck('user_phone_verified');
				if(empty($isVerifiedPhone))
				{
					$isVerifiedPhone = '0';
				}
				else
				{
					$isVerifiedPhone = $isVerifiedPhone;
				}
				//Return user id and token details:
				$return = array(
					'token'           => $session_key,
					'userId'          => Auth::user()->user_id,
					'userName'        => Auth::user()->user_name,
					'userFullName'    => Auth::user()->user_full_name,
					'userDisplayName' => Auth::user()->user_display_name,
					'userTagline'     => Auth::user()->user_tagline,
					'profileImage'    => ( !empty( Auth::user()->user_profile_image ) )
						? 'http://' . $_ENV[ 'URL' ] . '/' . Auth::user()->user_profile_image : '',
					'profileCover'    => ( !empty( Auth::user()->user_cover_image ) )
						? 'http://' . $_ENV[ 'URL' ] . '/' . Auth::user()->user_cover_image : '',
					'userPhone'     => $isVerifiedPhone,	
				);

				$status_code = 200;

			}
			else
			{

				// validation not successful, send back to form	
				$return = array( "error" => "You have provided wrong credentials" );

				$status_code = 401;

			}

		}
		$response = Response::make( $return, $status_code );

		return $response;

	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/login/facebook",
	 *   description="Log in with facebook account",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Log user in with Facebook",
	 *       nickname="facebook",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="userId",
	 *           description="Facebook user ID",
	 *           paramType="form",
	 *           required=true,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="userName",
	 *           description="Name from facebook profile",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="displayName",
	 *           description="Name from facebook profile",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="gender",
	 *           description="Gender, from facebook profile",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="fullName",
	 *           description="Full Name from facebook profile",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *			@SWG\Parameter(
	 *           name="deviceToken",
	 *           description="Device token for push notifications",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *			@SWG\Parameter(
	 *           name="device",
	 *           description="Device token for push notifications",
	 *           paramType="form",
	 *           required=true,
	 *             enum="['apple', 'google']",
	 *           type="string"
	 *         )
	 *       ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          )
	 *        )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function facebook()
	{
		// validate the info, create rules for the inputs
		$rules = array(
			'userId'      => 'required',
			'displayName' => 'required',
			'userName'    => 'required',
			'gender'      => 'required',
			'fullName'    => 'required',
		);

		// run the validation rules on the inputs
		$validator = Validator::make( Input::all(), $rules );

		// if the validator fails, return errors
		if( $validator->fails() )
		{
			$return = $validator->messages();
			$status_code = 401;
		}
		else
		{

			//Check if this user has created an account by checking if the users id exists already in the user_social_id column

			$facebook_user = FacebookUser::firstOrNew( array( 'facebook_user_facebook_id' => Input::get( 'userId' ) ) );

			$facebook_user->facebook_user_display_name = Input::get( 'displayName' );
			$facebook_user->facebook_user_user_name = Input::get( 'userName' );
			$facebook_user->facebook_user_email = Input::get( 'email' );
			$facebook_user->facebook_user_gender = Input::get( 'gender' );
			$facebook_user->facebook_user_full_name = Input::get( 'fullName' );

			$facebook_user->save();

			$user = User::firstOrNew( array( 'user_facebook_id' => $facebook_user->facebook_user_id ) );

			$user->save();

			$deviceToken = Input::get( 'deviceToken' );
			$deviceType = Input::get( 'device' );

			if( isset( $deviceType ) && isset( $deviceToken ) )
			{

				$device = DeviceRegistration::firstOrNew(
					[ 'device_registration_device_token' => Input::get( 'deviceToken' ) ]
				);

				$device->device_registration_user_id = $user->user_id;
				$device->device_registration_device_type = $deviceType;
				$device->device_registration_device_token = $deviceToken;
				$device->device_registration_date_created = date( "Y-m-d H:i:s" );

				$device->save();

				$this->registerSNSEndpoint( $device );

			}

			//Create Session
			$session_key = str_random( 40 );

			$token = array(
				'token_value'        => $session_key,
				'token_created_date' => date( "Y-m-d H:i:s" ),
				'token_valid_until'  => date( "Y-m-d H:i:s", strtotime( "now + 1 hour" ) ),
				'token_user_id'      => $user->user_id,
				'token_type'         => 'Facebook'
			);

			$session = Token::create( $token );

			//Return user id and token details not using auth library:

			$return = getUserProfile( $user, $session );
			if(!empty($return))
			{
				$isVerifiedPhone = DB::table('user_phones')->where('user_phone_user_id', '=', $return['userId'])->pluck('user_phone_verified');
				if(empty($isVerifiedPhone))
				{
					$isVerifiedPhone = '0';
				}
				else
				{
					$isVerifiedPhone = $isVerifiedPhone;
				}
				$return['userPhone'] = $isVerifiedPhone;
			}
			$status_code = 200;

		}

		$response = Response::make( $return, $status_code );

		return $response;

	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/login/twitter",
	 *   description="Log in with twitter account",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Log user in with Twitter",
	 *       nickname="facebook",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="userId",
	 *           description="Twitter user ID",
	 *           paramType="form",
	 *           required=true,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="displayName",
	 *           description="Name from twitter profile",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="fullName",
	 *           description="Full Name from twitter profile",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *			@SWG\Parameter(
	 *           name="deviceToken",
	 *           description="Device token for push notifications",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *			@SWG\Parameter(
	 *           name="device",
	 *           description="Device token for push notifications",
	 *           paramType="form",
	 *           required=true,
	 *             enum="['apple', 'google']",
	 *           type="string"
	 *         )
	 *       ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          )
	 *        )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function twitter()
	{
		// validate the info, create rules for the inputs
		$rules = array(
			'userId'      => 'required',
			'displayName' => 'required',
			'fullName'    => 'required',
			'userName'    => 'required',
		);

		// run the validation rules on the inputs
		$validator = Validator::make( Input::all(), $rules );

		// if the validator fails, return errors
		if( $validator->fails() )
		{
			$return = $validator->messages();
			$status_code = 401;
		}
		else
		{

			//Check if this user has created an account by checking if the users id exists already in the user_social_id column
			$twitter_user = TwitterUser::firstOrNew( array( 'twitter_user_twitter_id' => Input::get( 'userId' ) ) );

			$twitter_user->twitter_user_twitter_id = Input::get( 'userId' );
			$twitter_user->twitter_user_display_name = Input::get( 'displayName' );
			$twitter_user->twitter_user_full_name = Input::get( 'fullName' );
			$twitter_user->twitter_user_user_name = Input::get( 'userName' );

			$twitter_user->save();

			$user = User::firstOrNew( array( 'user_twitter_id' => $twitter_user->twitter_user_id ) );

			$user->save();

			$deviceToken = Input::get( 'deviceToken' );
			$deviceType = Input::get( 'device' );

			if( isset( $deviceType ) && isset( $deviceToken ) )
			{

				$device = DeviceRegistration::firstOrNew(
					[ 'device_registration_device_token' => Input::get( 'deviceToken' ) ]
				);

				$device->device_registration_user_id = $user->user_id;
				$device->device_registration_device_type = $deviceType;
				$device->device_registration_device_token = $deviceToken;
				$device->device_registration_date_created = date( "Y-m-d H:i:s" );

				$device->save();

				$this->registerSNSEndpoint( $device );

			}

			//Create Session
			$session_key = str_random( 40 );

			$token = array(
				'token_value'        => $session_key,
				'token_created_date' => date( "Y-m-d H:i:s" ),
				'token_valid_until'  => date( "Y-m-d H:i:s", strtotime( "now + 1 hour" ) ),
				'token_user_id'      => $user->user_id,
				'token_type'         => 'Twitter'
			);

			$session = new Token( $token );

			$session->save();

			//var_dump($session);

			$return = getUserProfile( $user, $session );
			if(!empty($return))
			{
				$isVerifiedPhone = DB::table('user_phones')->where('user_phone_user_id', '=', $return['userId'])->pluck('user_phone_verified');
				if(empty($isVerifiedPhone))
				{
					$isVerifiedPhone = '0';
				}
				else
				{
					$isVerifiedPhone = $isVerifiedPhone;
				}
				$return['userPhone'] = $isVerifiedPhone;
			}
			//Return user id and token details not using auth library:
			// $return = array(
			// 	'token' => $session_key,
			// 	'userId' => $user->user_id,
			// 	'userName' => null,
			// 	'userFullName' => null,
			// 	'userDisplayName' => $user->user_display_name,
			// 	);

			$status_code = 200;

		}

		$response = Response::make( $return, $status_code );

		return $response;

	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/login/google",
	 *   description="Log in with Google account",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Log user in with Google",
	 *       nickname="facebook",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="userId",
	 *           description="Google user ID",
	 *           paramType="form",
	 *           required=true,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="displayName",
	 *           description="Name from Google profile",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="userName",
	 *           description="Email address from Google profile",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="fullName",
	 *           description="Full Name from Google profile",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *			@SWG\Parameter(
	 *           name="deviceToken",
	 *           description="Device token for push notifications",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *			@SWG\Parameter(
	 *           name="device",
	 *           description="Device token for push notifications",
	 *           paramType="form",
	 *           required=true,
	 *             enum="['apple', 'google']",
	 *           type="string"
	 *         )
	 *       ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          )
	 *        )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function google()
	{
		// validate the info, create rules for the inputs
		$rules = array(
			'userId'      => 'required',
			'displayName' => 'required',
			'userName'    => 'required',
			'fullName'    => 'required',
		);

		// run the validation rules on the inputs
		$validator = Validator::make( Input::all(), $rules );

		// if the validator fails, return errors
		if( $validator->fails() )
		{
			$return = $validator->messages();
			$status_code = 401;
		}
		else
		{

			//Check if this user has created an account by checking if the users id exists already in the user_social_id column
			$google_user = GoogleUser::firstOrNew( array( 'google_user_google_id' => Input::get( 'userId' ) ) );

			$google_user->google_user_google_id = Input::get( 'userId' );
			$google_user->google_user_display_name = Input::get( 'displayName' );
			$google_user->google_user_user_name = Input::get( 'userName' );
			$google_user->google_user_full_name = Input::get( 'fullName' );

			$google_user->save();

			$user = User::firstOrNew( array( 'user_google_id' => $google_user->google_user_id ) );

			$user->save();

			$deviceToken = Input::get( 'deviceToken' );
			$deviceType = Input::get( 'device' );

			if( isset( $deviceType ) && isset( $deviceToken ) )
			{

				$device = DeviceRegistration::firstOrNew(
					[ 'device_registration_device_token' => Input::get( 'deviceToken' ) ]
				);

				$device->device_registration_user_id = $user->user_id;
				$device->device_registration_device_type = $deviceType;
				$device->device_registration_device_token = $deviceToken;
				$device->device_registration_date_created = date( "Y-m-d H:i:s" );

				$device->save();

				$this->registerSNSEndpoint( $device );

			}

			//var_dump($user);

			//Create Session
			$session_key = str_random( 40 );

			$token = array(
				'token_value'        => $session_key,
				'token_created_date' => date( "Y-m-d H:i:s" ),
				'token_valid_until'  => date( "Y-m-d H:i:s", strtotime( "now + 1 hour" ) ),
				'token_user_id'      => $user->user_id,
				'token_type'         => 'Google'
			);

			$session = new Token( $token );

			$session->save();

			$return = getUserProfile( $user, $session );
			if(!empty($return))
			{
				$isVerifiedPhone = DB::table('user_phones')->where('user_phone_user_id', '=', $return['userId'])->pluck('user_phone_verified');
				if(empty($isVerifiedPhone))
				{
					$isVerifiedPhone = '0';
				}
				else
				{
					$isVerifiedPhone = $isVerifiedPhone;
				}
				$return['userPhone'] = $isVerifiedPhone;
			}
			$status_code = 200;

		}

		$response = Response::make( $return, $status_code );

		return $response;

	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/login/forgotpassword",
	 *   description="Request a password reset link",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Sends the email address a reset password link",
	 *       nickname="password",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="email",
	 *           description="Email address to send reset link to",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         )
	 *       ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          )
	 *        )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function password()
	{
		// validate the info, create rules for the inputs
		$rules = array(
			'email' => 'required|email', // make sure the email is an actual email
		);

		// run the validation rules on the inputs
		$validator = Validator::make( Input::all(), $rules );

		// if the validator fails, return errors
		if( $validator->fails() )
		{
			$return = $validator->messages();
			$status_code = 401;
		}
		else
		{

			//$user = User::where( 'user_email', '=', Input::get( 'email' ) )->count();
			$user = User::where( 'user_email', '=', Input::get( 'email' ) )->first();
			if( $user )
			{
				$temporarypassword = str_random( 6 );
				$user->user_password = Hash::make( $temporarypassword );
				$user->save();
				//create token to send to user

				//	echo "yes";
				$data = [ ];

				Mail::send( 'emails.password', array('temporarypassword'=>$temporarypassword), function ( $message )
				{
					$message->from( 'do-not-reply@mobstar.com', 'MobStar' )->subject( 'Password Reset' );;

					$message->to( Input::get( 'email' ) );
				} );

				//do email stuff here

				$return = [ 'notice' => 'link sent' ];

				$status_code = 200;
			}
			else
			{
				// validation not successful, send back to form
				$return = array( "error" => "User not found" );

				$status_code = 404;
			}
		}

		$response = Response::make( $return, $status_code );

		return $response;

	}

	public function registerSNSEndpoint( $device )
	{
		if( $device->device_registration_device_type == "apple" )
		{
			$arn = "arn:aws:sns:eu-west-1:830026328040:app/APNS/com.mobstar.prod";
		}
		else
		{
			$arn = "arn:aws:sns:eu-west-1:830026328040:app/GCM/Mobstar-Android";
		}

		$client = getSNSClient();

		$endpoint = $client->createPlatformEndpoint( [
													'PlatformApplicationArn' =>
														$arn,
													'Token'                  =>
														$device->device_registration_device_token
												] );

		$endpointDetails = $endpoint->toArray();

		$response = $client->publish( [
							  'TargetArn'          => $endpointDetails['EndpointArn'],
							  'Message'            => 'Welcome to Push Notifications',
							  'Subject'            => 'MobStar',
							  'MessageAttributues' => [
								  'String' => [
									  'DataType' => 'string',
								  ]
							  ]
						  ] );

		//log contents
		try{
			$myfile = $_ENV['PATH'] . 'public/sns-log.txt';
			file_put_contents($myfile, date('d-m-Y H:i:s') . ' debug log:', FILE_APPEND);
			file_put_contents($myfile, print_r($endpointDetails, true), FILE_APPEND);
		}
		catch(\League\Flysystem\Exception $ex){

		}

	}
	public function verifyphonenumber()
	{
		// validate the info, create rules for the inputs
		$rules = array(
			'userId'    => 'required',
			'vPhoneNo' => 'required',
			'countryCode'   => 'required'
		);
		
		// run the validation rules on the inputs
		$validator = Validator::make( Input::all(), $rules );

		// if the validator fails, return errors
		if( $validator->fails() )
		{
			$return = $validator->messages();
			$status_code = 401;
		}
		else
		{
			//Create verification code
			$iVerificationCode = mt_rand(1111, 9999);
			$user_phone_user_id = Input::get( 'userId' );
			$user_phone_number = Input::get( 'vPhoneNo' );
			$user_phone_country = Input::get( 'countryCode' );			
			if( isset( $user_phone_user_id ) && isset( $user_phone_number ) && isset( $user_phone_country ))
			{
				$phone = UserPhone::firstOrNew(
					[ 'user_phone_user_id' => $user_phone_user_id ]
				);

				$phone->user_phone_user_id = $user_phone_user_id;
				$phone->user_phone_number = $user_phone_number;
				$phone->user_phone_country = $user_phone_country;
				$phone->user_phone_verification_code = $iVerificationCode;
				$phone->user_phone_verified = '0';
				$phone->created_at = date( "Y-m-d H:i:s" );

				if($phone->save())
				{
					require "/var/www/api/vendor/twilio/Services/Twilio.php";
					// set your AccountSid and AuthToken from www.twilio.com/user/account
					//$AccountSid = "AC77fca1e17b7508e848be713a6994893c";
					$AccountSid = "AC1651a9f031f9450afb7e5bac05434970";
					//$AuthToken = "4ade7277e9e0c53f4c37c5f02ef83fe7";
					$AuthToken = "1c4f9735e694ee813d0cca2eab5c2eba";
					$client = new Services_Twilio($AccountSid, $AuthToken);
					try {
						$message = $client->account->messages->create(array(
						"From" => "+353768886622",
						"To" => "+".$user_phone_country.$user_phone_number,
						"Body" => "Mobstar! Verification Code ".$iVerificationCode."",
						));
					}
					catch (Services_Twilio_RestException $e) {
						$userphone = UserPhone::find($phone->user_phone_id);
						$userphone->delete();
						$return = json_encode( [ 'error' => $e->getMessage() ] );
						$status_code = 404;
						$response = Response::make( $return, $status_code );
						return $response;
					}	
				}
				$phonedata = DB::table('user_phones')->where('user_phone_user_id', '=', $user_phone_user_id)->first();
				if(!empty($phonedata))
				{
					/*$return = array(
					'verificationCode'=> $iVerificationCode,
					'userId'          => $phonedata->user_phone_user_id,
					'vPhoneNo'        => $phonedata->user_phone_number,
					'countryCode'     => $phonedata->user_phone_country,
					'userPhone'       => $phonedata->user_phone_verified,	
					);*/
					$return = [ 'notice' => 'success' ];
					$status_code = 200;
				}
				else
				{
					$return = json_encode( [ 'error' => 'No Entries Found' ] );
					$status_code = 404;
				}
			}			
			else
			{
				// validation not successful, send back to form	
				$return = array( "error" => "You have provided wrong details" );
				$status_code = 401;
			}
		}
		$response = Response::make( $return, $status_code );
		return $response;
	}
	public function verifycode()
	{
		// validate the info, create rules for the inputs
		$rules = array(
			'userId'    => 'required',
			'verificationCode' => 'required',
		);
		
		// run the validation rules on the inputs
		$validator = Validator::make( Input::all(), $rules );

		// if the validator fails, return errors
		if( $validator->fails() )
		{
			$return = $validator->messages();
			$status_code = 401;
		}
		else
		{
			//Create verification code
			$user_phone_user_id = Input::get( 'userId' );
			$user_phone_verification_code = Input::get( 'verificationCode' );
			if( isset( $user_phone_user_id ) && isset( $user_phone_verification_code ))
			{
				//$phonedata = DB::table('user_phones')->where('user_phone_user_id', '=', $user_phone_user_id)->first();
				$phonedata = UserPhone::where( 'user_phone_user_id', '=', $user_phone_user_id )->first();
				if( $phonedata )
				{
					if($phonedata->user_phone_verification_code == $user_phone_verification_code)
					{
						$phonedata->user_phone_verified = '1';
						$phonedata->updated_at = date( "Y-m-d H:i:s" );
						$phonedata->save();
						
						$return = [ 'notice' => 'Phone number verified successfully' ];
						$status_code = 200;	
					}
					else
					{
						$return = array( "error" => "Invalid verification code, please try again" );
						$status_code = 404;
					}
				}
				else
				{
					$return = array( "error" => "User not found" );
					$status_code = 404;
				}
			}
			else
			{
				// validation not successful, send back to form	
				$return = array( "error" => "You have provided wrong details" );
				$status_code = 401;
			}
		}
		$response = Response::make( $return, $status_code );
		return $response;
	}
}