<?php

use Swagger\Annotations as SWG;

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
	 *   produces="['application/json']",
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
			'password' => 'required|alphaNum|min:3' // password can only be alphanumeric and has to be greater than 3 characters
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

				Token::create( $token );

				//Return user id and token details:
				$return = array(
					'token'           => $session_key,
					'userId'          => Auth::user()->user_id,
					'userName'        => Auth::user()->user_name,
					'userFullName'    => Auth::user()->user_full_name,
					'userDisplayName' => Auth::user()->user_display_name,
					'userGroup'       => Auth::user()->group->user_group_name,
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
	 *   produces="['application/json']",
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
	 *           name="displayName",
	 *           description="Name from facebook profile",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="email",
	 *           description="Email address from facebook profile",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="dob",
	 *           description="Date of birth from facebook profile",
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
			'email'       => 'required',
			'dob'         => 'required',
			'gender'      => 'required',
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
			$user = User::firstOrNew( array( 'user_facebook_id' => Input::get( 'userId' ) ) );

			$user->user_facebook_id = Input::get( 'userId' );
			$user->user_facebook_display_name = Input::get( 'displayName' );
			$user->user_display_name = Input::get( 'displayName' );
			$user->user_facebook_email = Input::get( 'email' );
			$user->user_facebook_dob = date( 'Y-m-d', strtotime( Input::get( 'dob' ) ) );
			$user->user_facebook_gender = Input::get( 'gender' );

			$user->save();

			//Create Session
			$session_key = str_random( 40 );

			$token = array(
				'token_value'        => $session_key,
				'token_created_date' => date( "Y-m-d H:i:s" ),
				'token_valid_until'  => date( "Y-m-d H:i:s", strtotime( "now + 1 hour" ) ),
				'token_user_id'      => $user->user_id,
				'token_type'         => 'Native'
			);

			Token::create( $token );

			//Return user id and token details not using auth library:
			$return = array(
				'token'           => $session_key,
				'userId'          => $user->user_id,
				'userName'        => null,
				'userFullName'    => null,
				'userDisplayName' => $user->user_display_name,
				'userGroup'       => $user->user_group_name,
			);

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
	 *   produces="['application/json']",
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

			$twitter_user->save();

			$user = User::firstOrNew( array( 'user_twitter_id' => $twitter_user->twitter_user_id ) );
			$user->user_user_group = 3;

			$user->save();

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

			//Return user id and token details not using auth library:
			// $return = array(
			// 	'token' => $session_key,
			// 	'userId' => $user->user_id,
			// 	'userName' => null,
			// 	'userFullName' => null,
			// 	'userDisplayName' => $user->user_display_name,
			// 	'userGroup' => $user->user_group_name,
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
	 *   produces="['application/json']",
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

			$google_user->save();

			$user = User::firstOrNew( array( 'user_google_id' => $google_user->google_user_id ) );

			$user->user_user_group = 3;

			$user->save();

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

			$status_code = 200;

		}

		$response = Response::make( $return, $status_code );

		return $response;

	}

}