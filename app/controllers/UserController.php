<?php

use Swagger\Annotations as SWG;
use MobStar\Storage\Token\TokenRepository as Token;
use Aws\S3\S3Client;
use Aws\Common\Credentials\Credentials as Creds;

/**
 * @package
 * @category
 * @subpackage
 *
 * @SWG\Resource(
 *  apiVersion=0.2,
 *  swaggerVersion=1.2,
 *  resourcePath="/user",
 *  basePath="http://api.mobstar.com"
 * )
 */
class UserController extends BaseController
{

	public function __construct( Token $token )
	{
		$this->token = $token;
	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/user",
	 *   description="Operations about users",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="Get all users",
	 *       notes="Returns a all users. API-Token is required for this method.",
	 *       nickname="getAllUsers",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="fields",
	 *           description="Accepted values for the fields parameter are: id, userName, displayName, fullName, email.",
	 *           paramType="query",
	 *           required=false,
	 *           type="comma seperated list"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="page",
	 *           description="Page of results you want to view",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="limit",
	 *           description="Maximum number of representations in response.",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer"
	 *         )
	 *       ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=404,
	 *            message="No users found"
	 *          )
	 *        )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public $valid_fields = [ "id", "userName", "displayName", "fullName", "email", "stars", "starredBy", 'coverImage', 'profileImage' ];

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{

		$client = getS3Client();

		$fields = array_values( explode( ',', Input::get( "fields" ) ) );

		if( $fields[ 0 ] == "" )
		{
			unset( $fields );
		}

		$return = [ ];
		$valid = false;

		if( !empty( $fields ) )
		{
			//Check if fields are valid
			foreach( $fields as $field )
			{
				if( !in_array( $field, $this->valid_fields ) )
				{
					$return[ 'errors' ][ ] = [ $field . " is not a valid field." ];
				}
				else
				{
					$valid = true;
				}
			}
		}

		//Get limit to calculate pagination
		$limit = ( Input::get( 'limit', '50' ) );

		//If not numeric set it to the default limit
		$limit = ( !is_numeric( $limit ) ) ? 50 : $limit;

		//Get page
		$page = ( Input::get( 'page', '1' ) );
		$page = ( !is_numeric( $page ) ) ? 1 : $page;

		//Calculate offset
		$offset = ( $page * $limit ) - $limit;

		//If page is greter than one show a previous link
		if( $page > 1 )
		{
			$previous = true;
		}
		else
		{
			$previous = false;
		}

		//Find total number to put in header
		$count = User::count();

		if( $count == 0 )
		{
			$return = [ 'error' => 'No Users Found' ];
			$status_code = 404;

			return Response::make( $return, $status_code );
		}

		//If the count is greater than the highest number of items displayed show a next link
		elseif( $count > ( $limit * $page ) )
		{
			$next = true;
		}
		else
		{
			$next = false;
		}

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$users = User::take( $limit )->skip( $offset )->get();
		foreach( $users as $user )
		{

			//check to see if fields were specified
			if( ( !empty( $fields ) ) && $valid )
			{
				$current = array();

				if( in_array( "id", $fields ) )
				{
					$current[ 'id' ] = $user->user_id;
				}

				if( in_array( 'userName', $fields ) )
				{
					$current[ 'userName' ] = $user->user_name;
				}

				if( in_array( 'displayName', $fields ) )
				{
					$current[ 'displayName' ] = $user->user_display_name;
				}

				if( in_array( 'fullName', $fields ) )
				{
					$current[ 'fullName' ] = $user->user_full_name;
				}

				if( in_array( 'email', $fields ) )
				{
					$current[ 'email' ] = $user->user_email;
				}

				if( in_array( 'profileImage', $fields ) )
				{
					$current[ 'profileImage' ] = ( !empty( $user->user_profile_image ) )
						? 'http://' . $_ENV[ 'URL' ] . '/' . $user->user_profile_image : '';
				}

				if( in_array( 'profileCover', $fields ) )
				{
					$current[ 'profileCover' ] = ( !empty( $user->user_cover_image ) )
						? 'http://' . $_ENV[ 'URL' ] . '/' . $user->user_cover_image : '';
				}

				if( in_array( 'stars', $fields ) )
				{
					$stars = [ ];

					foreach( $user->Stars as $star )
					{
						if( $star->user_star_deleted == 0 )
						{

							$stars[ ] = [ 'star_id'      => $star->user_star_star_id,
										  'star_name'    => $star->Stars->user_display_name,
										  'profileImage' => ( !empty( $star->Stars->user_profile_image ) )
											  ? 'http://' . $_ENV[ 'URL' ] . '/' . $star->Stars->user_profile_image
											  : '',
							];

						}
					}

					$current[ 'stars' ] = $stars;
				}

				if( in_array( 'starredBy', $fields ) )
				{
					$starredBy = [ ];

					foreach( $user->StarredBy as $starred )
					{
						if( $starred->user_star_deleted == 0 )
						{
							$starredBy[ ] = [ 'star_id'      => $starred->User_star_user_id,
											  'star_name'    => $starred->User->user_display_name,
											  'profileImage' => ( !empty( $starred->User->user_profile_image ) )
												  ? 'http://' . $_ENV[ 'URL' ] . '/' . $starred->User->user_profile_image
												  : '',
							];
						}

					}

					$current[ 'starredBy' ] = $starredBy;
				}

				$return[ 'users' ][ ][ 'user' ] = $current;
			}

			//if not just return all info
			else
			{
				$return[ 'users' ][ ][ 'user' ] = oneUser( $user, $session, true );
			}

		}

		$status_code = 200;

		//If next is true create next page link
		if( $next )
		{
			$return[ 'next' ] = "http://api.mobstar.com/user/?" . http_build_query( [ "limit" => $limit, "page" => $page + 1 ] );
		}

		if( $previous )
		{
			$return[ 'previous' ] = "http://api.mobstar.com/user/?" . http_build_query( [ "limit" => $limit, "page" => $page - 1 ] );
		}

		$response = Response::make( $return, $status_code );

		$response->header( 'X-Total-Count', $count );

		return $response;
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int $user
	 *
	 * @return Response
	 */

	/**
	 *
	 * @SWG\Api(
	 *   path="/user/{userIds}",
	 *   description="Operations about users",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="Get specific user/users",
	 *       notes="Returns users requested. API-Token is required for this method.",
	 *       nickname="getSpecificUsers",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="userIds",
	 *           description="ID or IDs of required users.",
	 *           paramType="path",
	 *           required=true,
	 *           type="comma seperated list"
	 *         ),
	 *		   @SWG\Parameter(
	 *           name="fields",
	 *           description="Accepted values for the fields parameter are: id, userName, displayName, fullName, email.",
	 *           paramType="query",
	 *           required=false,
	 *           type="comma seperated list"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="page",
	 *           description="Page of results you want to view",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="limit",
	 *           description="Maximum number of representations in response.",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer"
	 *         )
	 *       ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=404,
	 *            message="No users found"
	 *          )
	 *        )
	 *       )
	 *     )
	 *   )
	 * )
	 */
	public function show( $id_commas )
	{

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$client = getS3Client();

		$status_code = 200;

		// get ids
		$id = array_values( explode( ',', $id_commas ) );

		//Get fields
		$fields = array_values( explode( ',', Input::get( "fields" ) ) );

		//If no fields remove fields variable
		if( $fields[ 0 ] == "" )
		{
			unset( $fields );
		}

		$return = [ ];

		$valid = false;

		if( !empty( $fields ) )
		{
			//Check if fields are valid
			foreach( $fields as $field )
			{
				if( !in_array( $field, $this->valid_fields ) )
				{
					$return[ 'errors' ][ ] = [ $field . " is not a valid field." ];
				}
				else
				{
					$valid = true;
				}
			}
		}

		//Get limit and cursor and calculate pagination
		$limit = ( Input::get( 'limit', '50' ) );

		//If not numeric set it to the default limit
		$limit = ( !is_numeric( $limit ) ) ? 50 : $limit;

		//Get page
		$page = ( Input::get( 'page', '1' ) );
		$page = ( !is_numeric( $page ) ) ? 1 : $page;

		//Calculate offset
		$offset = ( $page * $limit ) - $limit;

		//If page is greter than one show a previous link
		if( $page > 1 )
		{
			$previous = true;
		}
		else
		{
			$previous = false;
		}

		//Get users greater than the cursor from
		$users = User::whereIn( 'user_id', $id )->take( $limit )->skip( $offset )->get();

		//Find total number to put in header
		$count = User::whereIn( 'user_id', $id )->count();

		if( $count == 0 )
		{
			$return = [ 'error' => 'No Users Found' ];
			$status_code = 404;

			return Response::make( $return, $status_code );
		}

		//If the count is greater than the highest number of items displayed show a next link
		elseif( $count > ( $limit * $page ) )
		{
			$next = true;
		}
		else
		{
			$next = false;
		}

		foreach( $users as $user )
		{

			//check to see if fields were specified and at least one is valid
			if( ( !empty( $fields ) ) && $valid )
			{
				$current = array();

				if( in_array( "id", $fields ) )
				{
					$current[ 'id' ] = $user->user_id;
				}

				if( in_array( 'userName', $fields ) )
				{
					$current[ 'userName' ] = $user->user_name;
				}

				if( in_array( 'displayName', $fields ) )
				{
					$current[ 'displayName' ] = $user->user_display_name;
				}

				if( in_array( 'fullName', $fields ) )
				{
					$current[ 'fullName' ] = $user->user_full_name;
				}

				if( in_array( 'email', $fields ) )
				{
					$current[ 'email' ] = $user->user_email;
				}

				if( in_array( 'profileImage', $fields ) )
				{
					$current[ 'profileImage' ] = ( !empty( $user->user_profile_image ) )
						? 'http://' . $_ENV[ 'URL' ] . '/' . $user->user_profile_image : '';
				}

				if( in_array( 'profileCover', $fields ) )
				{
					$current[ 'profileCover' ] = ( !empty( $user->user_cover_image ) )
						? 'http://' . $_ENV[ 'URL' ] . '/' . $user->user_cover_image : '';
				}

				if( in_array( 'stars', $fields ) )
				{
					$stars = [ ];

					foreach( $user->Stars as $star )
					{
						if( $star->user_star_deleted == 0 )
						{

							$stars[ ] = [ 'star_id'      => $star->user_star_star_id,
										  'star_name'    => $star->Stars->user_display_name,
										  'profileImage' => ( !empty( $star->Stars->user_profile_image ) )
											  ? 'http://' . $_ENV[ 'URL' ] . '/' . $star->Stars->user_profile_image
											  : '',
							];

						}
					}

					$current[ 'stars' ] = $stars;
				}

				if( in_array( 'starredBy', $fields ) )
				{
					$starredBy = [ ];

					foreach( $user->StarredBy as $starred )
					{
						if( $starred->user_star_deleted == 0 )
						{
							$starredBy[ ] = [ 'star_id'      => $starred->User_star_user_id,
											  'star_name'    => $starred->User->user_display_name,
											  'profileImage' => ( !empty( $starred->User->user_profile_image ) )
												  ? 'http://' . $_ENV[ 'URL' ] . '/' . $starred->User->user_profile_image
												  : '',
							];
						}

					}

					$current[ 'starredBy' ] = $starredBy;
				}

				if( count( $users ) > 1 )
				{
					$return[ 'users' ][ ][ 'user' ] = $current;
				}
				elseif( count( $users ) == 1 )
				{
					$return[ 'users' ][ ][ 'user' ] = $current;
				}

			}

			//if not just return all info
			else
			{


				$return[ 'users' ][ ][ 'user' ] = oneUser( $user, $session, true );


			}
		}

		//If next is true create next page link
		if( $next )
		{
			$return[ 'next' ] = "http://api.mobstar.com/user/" . $id_commas . "?" . http_build_query( [ "limit" => $limit, "page" => $page + 1 ] );
		}

		if( $previous )
		{
			$return[ 'previous' ] = "http://api.mobstar.com/user/" . $id_commas . "?" . http_build_query( [ "limit" => $limit, "page" => $page - 1 ] );
		}

		//echo $id_commas;
		$response = Response::make( $return, $status_code );

		$response->header( 'X-Total-Count', $count );

		//$response->header('LINK', "http://api.mobstar.com/user/" . $id_commas . "?limit=50&page=2; rel='next'");

		return $response;
		//return $user;
	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/user/search",
	 *   description="Operations about users",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="Serch for a user",
	 *       notes="Use the term parameter for your search term, if an email address is submitted the API will search for any users with this email address, anything other than an email address will result in a search of user names and display names. <br>API-Token is required for this method.",
	 *       nickname="getAllUsers",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="term",
	 *           description="Search term",
	 *           paramType="query",
	 *           required=false,
	 *           type="comma seperated list"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="page",
	 *           description="Page of results you want to view",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="limit",
	 *           description="Maximum number of representations in response.",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer"
	 *         )
	 *       ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=404,
	 *            message="No users found"
	 *          )
	 *        )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function search()
	{
		$term = Input::get( "term" );
		//check to see if an email was entered
		$validator = Validator::make(
			[ 'term' => $term ],
			[ 'term' => [ 'email' ] ]
		);

		//if not search by users name
		if( $validator->fails() )
		{
			$results = User::where( 'user_name', 'like', '%' . $term . '%' )->orWhere( 'user_display_name', 'like', '%' . $term . '%' )->get();
		}
		else
		{
			$results = User::where( 'user_email', '=', $term )->get();
		}

		$status_code = 200;

		if( count( $results ) == 0 )
		{
			$results = json_encode( [ 'error' => 'No Users Found' ] );
			$status_code = 404;
		}

		return Response::make( $results, $status_code );
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 *
	 * @SWG\Api(
	 *   path="/user",
	 *   description="Operations about users",
	 *   @SWG\Operations(
	 *	   @SWG\Operation(
	 *       method="POST",
	 *       summary="Add new user",
	 *       notes="Adds a new user to MobStar.",
	 *       nickname="addUsers",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="userName",
	 *           description="The registering users desired username.",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="email",
	 *           description="The registering users email addressed to be used to login.",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="fullName",
	 *           description="The full name of the registering user.",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="displayName",
	 *           description="The display name for the regisering user",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="password",
	 *           description="Password for the regisering user",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="dob",
	 *           description="The regisering user's date of birth, should be greater than 13 years from now.",
	 *           paramType="form",
	 *           required=false,
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
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=400,
	 *            message="Input validation failed"
	 *          )
	 *        )
	 *       )
	 *     )
	 *   )
	 * )
	 */
	public function store()
	{
		$rules = array(
			'userName'    => 'required|unique:users,user_name',
			'email'       => 'required|email|unique:users,user_email',
			'fullName'    => 'required',
			//'surname' 	=> 'required',
			'displayName' => 'required',
			'password'    => 'required',
			//'dob' 		=> 'required|date|before:' . date('Y-m-d', strtotime("now - 13 years")),
		);

		$messages = array(
			'userName.unique' => 'This user name is already taken.',
			'email.unique'    => 'This email address is already registered',
		);

		$validator = Validator::make( Input::all(), $rules, $messages );

		// process the login
		if( $validator->fails() )
		{

			$return = $validator->messages();

			$response = Response::make( $return, 400 );

			return $response;
		}
		else
		{

			$input = [
				'user_name'         => input::get( 'userName' ),
				'user_email'        => input::get( 'email' ),
				'user_full_name'    => input::get( 'fullName' ),
				//'user_surname' => input::get('surname'),
				'user_display_name' => input::get( 'displayName' ),
				'user_password'     => Hash::make( input::get( 'password' ) ),
				'user_dob'          => date( 'Y-m-d', strtotime( input::get( 'dob' ) ) ),
			];
			//Create the new user
			$user = User::create( $input );

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

				$login = new LoginController;
				//$login->registerSNSEndpoint( $device );

			}
			//Get team users
			$ids = [ 4, 5 ];
			//$team_users = User::where( 'user_user_group', 4 )->get();
			$team_users = User::whereIn( 'user_user_group', $ids )->get();
			//Find total number team user
			//$team_users_count = User::where( 'user_user_group', 4 )->count();
			$team_users_count = User::whereIn( 'user_user_group', $ids )->count();
			if( $team_users_count > 0 )
			{

				foreach( $team_users as $team )
				{
					$input = array(
						'user_star_user_id' => $team->user_id,
						'user_star_star_id' => $user->user_id,
						'user_star_deleted' => 0,
					);

					$star = Star::firstOrNew( $input );
					if( isset( $star->user_star_created_date ) )
					{
						continue;
					}
					else
					{
						$star->user_star_created_date = date( 'Y-m-d H:i:s' );
						$star->save();
					}
				}
			}
			/* Code for send welcome email template */
			require Config::get( 'app.home' )."/vendor/mandrill-api-php/src/Mandrill.php"; //Not required with Composer
			try {
			$mandrill = new Mandrill('NrOKeNhrpC14BpP_v8_Ffw');
			$template_name = 'welcome-email';
			$template_content = array( );
			$message = array(
				'to' => array(
					array(
						'email' => $user->user_email,
						'name' => $user->user_display_name,
						'type' => 'to'
					)
				),
				'merge' => true,
				'merge_language' => 'mailchimp',
				'merge_vars' => array(
					array(
						'rcpt' => $user->user_email,
						'vars' => array(
							array(
								'name' => 'DISPLAYNAME',
								'content' => $user->user_display_name
							)
						)
					)
				)
			);
			$async = false;
			$ip_pool = '';
			$send_at = '';
			$result = $mandrill->messages->sendTemplate($template_name, $template_content, $message, $async, $ip_pool, $send_at);
			} catch(Mandrill_Error $e) {
			}
			/* End */

			/* Code for add email into mailchimp list */
			require Config::get( 'app.home' )."/vendor/mailchimp/src/Mailchimp.php"; //Not required with Composer
			$api_key = "509cd096e6e75d9d6f656530e7d9e974-us9"; //replace with your API key
			$list_id = "bdaeca6660"; //replace with the list id you're adding the email to
			$merge_vars = array('MMERGE4'=>$user->user_name,'MMERGE5'=>$user->user_full_name,'MMERGE6'=>$user->user_display_name);
			// set up our mailchimp object, and list object
			$Mailchimp = new Mailchimp( $api_key );
			$Mailchimp_Lists = new Mailchimp_Lists( $Mailchimp );

			//$email = 'manish@spaceotechnologies.com'; //replace with a test email
			try {
				//$subscriber = $Mailchimp_Lists->subscribe( $list_id, array( 'email' => $email ) ); //pass the list id and email to mailchimp
			   // $subscriber = $Mailchimp_Lists->subscribe( $list_id, array( 'email' => $email ) ); //pass the list id and email to mailchimp

				///
				$subscriber = $Mailchimp_Lists->subscribe($list_id,
														array('email' => $user->user_email),
														$merge_vars,
														false,
														false, // for forcefully subscribe
														false,
														false
													   );

			} catch (Exception $e) {
				//mail('anil@spaceotechnologies.com','custom_code_'.time(),print_r($e->getMessage(),true));
				//You'll need to write your own code to handle exceptions
				//print_r($e->getMessage());
			}

			// check that we've succeded
			if ( !empty( $subscriber['leid'] ) ) {
				//mail('anil@spaceotechnologies.com','success_'.time(),print_r('Email Added to MailChimp',true));
				//echo 'Email Added to MailChimp';
			}
			/* End */

			//Log user in

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

				$this->token->create_session( $token );

				//Return user id and token details:
				$return = array(
					'token'           => $session_key,
					'userId'          => Auth::user()->user_id,
					'userName'        => Auth::user()->user_name,
					'userFullName'    => Auth::user()->user_full_name,
					'userDisplayName' => Auth::user()->user_display_name,
					'profileImage'    => ( !empty( Auth::user()->user_profile_image ) )
						? 'http://' . $_ENV[ 'URL' ] . '/' . Auth::user()->user_profile_image : '',
					'profileCover'    => ( !empty( Auth::user()->user_cover_image ) )
						? 'http://' . $_ENV[ 'URL' ] . '/' . Auth::user()->user_cover_image : '',
				);

				return $return;

			}
			else
			{

				// validation not successful, send back to form
				return json_encode( array( "error" => "login unsucessful" ) );

			}
		}

	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int $user
	 *
	 * @return Response
	 * @SWG\Api(
	 *   path="/user/{userId}",
	 *   description="Operations about users",
	 *   @SWG\Operations(
	 *	   @SWG\Operation(
	 *       method="PUT",
	 *       summary="Update a user",
	 *       notes="Update a current MobStar user.",
	 *       nickname="addUsers",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="userId",
	 *           description="User's ID.",
	 *           paramType="path",
	 *           required=true,
	 *           type="comma seperated list"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="fullName",
	 *           description="The full name of the registering user.",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="displayName",
	 *           description="The display name for the regisering user",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="tagline",
	 *           description="The tagline to appear on the users profile",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *		@SWG\Parameter(
	 *           name="bio",
	 *           description="The about me section on the users profile",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="password",
	 *           description="Password for the regisering user",
	 *           paramType="form",
	 *           required=false,
	 *           type="string"
	 *         )
	 *       ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=400,
	 *            message="Input validation failed"
	 *          )
	 *        )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function update( $id )
	{
		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$user = User::find( $session->token_user_id );

		$rules = array(
			//'email'		=> 'required|email|unique:users,user_email'
			//'dob' 		=> 'required|date|before:' . date('Y-m-d', strtotime("now - 13 years")),
		);

		$messages = array(
			'userName.unique' => 'This user name is already taken.',
			'email.unique'    => 'This email address is already registered',
		);

		$validator = Validator::make( Input::all(), $rules, $messages );

		if( $validator->fails() )
		{
			return $validator->messages();
		}
		else
		{
			$input = Input::get();
			if( isset( $input[ 'password' ] ) )
			{
				$user->user_password = Hash::make( Input::get( "password" ) );
			}
			if( isset( $input[ 'fullName' ] ) )
			{
				$user->user_full_name = Input::get( "fullName" );
			}
			if( isset( $input[ 'userName' ] ) &&  !empty( $input[ 'userName' ] ))
			{
				$user->user_name = Input::get( "userName" );
			}

			if( isset( $input[ 'tagline' ] ) )
			{
				$user->user_tagline = Input::get( 'tagline' );
			}

			if( isset( $input[ 'bio' ] ) )
			{
				$user->user_bio = Input::get( 'bio' );
			}

			if( isset( $input[ 'displayName' ] ) )
			{
				$user->user_display_name = Input::get( "displayName" );
			}
			if( isset( $input[ 'dob' ] ) )
			{
				$user->user_dob = date( 'Y-m-d', strtotime( input::get( 'dob' ) ) );
			}
			if( isset( $input[ 'email' ] ) )
			{
				$user->user_email = input::get( 'email' );
			}

			$user->save();

			return [ 'user' => oneUser( $user, $session, true ) ];
		}
	}

	/**
	 *
	 * @SWG\Api(
	 *     path="/user/profile",
	 *   description="Operation about Users",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Add profile pic",
	 *       notes="Operation for user to add a profile image",
	 *       nickname="removeStar",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="profileImage",
	 *           description="The image file.",
	 *           paramType="form",
	 *           required=true,
	 *           type="file"
	 *         )
	 *       ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=400,
	 *            message="Input validation failed"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function profile()
	{
		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$profile = Input::file( 'profileImage' );

		$user = User::find( $session->token_user_id );

		if( !empty( $profile ) )
		{
			$file_in = $profile->getRealPath();

			$file_out = 'profile/' . $session->token_user_id . "-" . str_random( 12 ) . ".jpg";

			$img = Image::make( $file_in );

			$img->resize( 200, 200 );

			$img->save( $_ENV[ 'PATH' ] . '/public/' . $file_out, 80 );

			$handle = fopen( $_ENV[ 'PATH' ] . '/public/' . $file_out, "r" );

			Flysystem::connection( 'awss3' )->put( $file_out,
												   fread( $handle,
														  filesize( $_ENV[ 'PATH' ] . '/public/' . $file_out ) ) );

			$user->user_profile_image = $file_out;
		}

		else
		{
			return Response::make( [ 'errors' => 'File not included' ], 401 );
		}

		$user->save();

		$return[ 'user' ] = oneUser( $user, $session, true, true );

		return Response::make( $return, 200 );

	}

	/**
	 *
	 * @SWG\Api(
	 *     path="/user/cover",
	 *   description="Operation about Users",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Add cover pic",
	 *       notes="Operation for user to add a cover image",
	 *       nickname="removeStar",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="coverImage",
	 *           description="The image file.",
	 *           paramType="form",
	 *           required=true,
	 *           type="file"
	 *         )
	 *       ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=400,
	 *            message="Input validation failed"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */
	public function cover()
	{

		$token = Request::header( "X-API-TOKEN" );
		$session = $this->token->get_session( $token );

		$cover = Input::file( 'coverImage' );

		$user = User::find( $session->token_user_id );

		if( !empty( $cover ) )
		{
			$file_in = $cover->getRealPath();

			$file_out = 'profile/' . $session->token_user_id . "-" . str_random( 12 ) . ".jpg";

			$img = Image::make( $file_in );

			$img->resize( 200, 200 );

			$img->save( $_ENV[ 'PATH' ] . '/public/' . $file_out, 80 );

			$handle = fopen( $_ENV[ 'PATH' ] . '/public/' . $file_out, "r" );

			Flysystem::connection( 'awss3' )->put( $file_out,
												   fread( $handle,
														  filesize( $_ENV[ 'PATH' ] . '/public/' . $file_out ) ) );

			$user->user_cover_image = $file_out;
		}

		else
		{
			return [ 'errors' => 'File not included' ];
		}

		$user->save();

		$stars = [ ];

		foreach( $user->Stars as $star )
		{
			if( $star->user_star_deleted == 0 )
			{

				$stars[ ] = [ 'star_id'   => $star->user_star_star_id,
							  'star_name' => $star->Stars->user_display_name,
				];

			}
		}

		$starredBy = [ ];

		foreach( $user->StarredBy as $starred )
		{
			if( $starred->user_star_deleted == 0 )
			{
				$starredBy[ ] = [ 'star_id'   => $starred->user_star_user_id,
								  'star_name' => $starred->User->user_display_name,
				];
			}

		}

		$return[ 'user' ] = oneUser( $user, $session, true );

		return Response::make( $return, 200 );
	}

	public function destroy( $user )
	{
		$user->delete();

		return Response::json( true );
	}

	public function test()
	{
//		$config = array(
//			'key' => Creds::ENV_KEY,
//			'secret' => Creds::ENV_SECRET
//		);
//
//		$client = S3Client::factory($config);
//
//		$signedUrl = $client->getObjectUrl(Config::get('app.bucket'), 'hi.txt', '+10 minutes');
//		return $signedUrl;

		$users = User::all();

		foreach( $users as $user )
		{
			$file_in = $_ENV[ 'PATH' ] . 'public/' . $user->user_profile_image;

			if(
				isset( $user->user_profile_image )
				&& file_exists( $file_in )
			)
			{
				$handle = fopen( $file_in, "r" );
				Flysystem::connection( 'awss3' )->put( $user->user_profile_image, fread( $handle, filesize( $file_in ) ) );

			}

			$file_in = '/' . $_ENV[ 'PATH' ] . $user->user_cover_image;
			if(
				isset( $user->user_cover_image )
				&& file_exists( $file_in )
			)
			{
				$handle = fopen( $file_in, "r" );
				Flysystem::connection( 'awss3' )->put( $user->user_cover_image, fread( $handle, filesize( $file_in ) ) );
			}

		}
	}

	public function passwordReset( )
	{
		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$rules = array(
			'currentPassword'		=> 'required',
			'newPassword' 		=> 'required|min:6',
			'confirmPassword'	=> 'required|same:newPassword'
		);


		$validator = Validator::make( Input::all(), $rules );

		if( $validator->fails() )
		{
			return $validator->messages();
		}
		else
		{
			$user = User::find( $session->token_user_id );

			if(Hash::check(Input::get('currentPassword'), $user->user_password))
			{
				$user->user_password = Hash::make( Input::get( "newPassword" ) );

				$user->save();

				return Response::make( ['info' => 'Password changed successfully'], 200 );

			}

			else
			{
				return Response::make( ['info' => 'Invalid password'], 300 );
			}

		}

	}



	/**
	 *
	 * @SWG\Api(
	 *   path="/user/me",
	 *   description="Operation about Users",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="Get Me",
	 *       notes="Details about current logged in user",
	 *       nickname="getMe",
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=400,
	 *            message="Input validation failed"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */
	public function me()
	{

		$token = Request::header( "X-API-TOKEN" );
		$session = $this->token->get_session( $token );

		$user = User::find($session['token_user_id']);

		$return['user'] = oneUser($user, $session, true);

		return Response::make( $return , 200 );

	}
	public function team( )
	{
		$client = getS3Client();

		$status_code = 200;

		$return = [ ];

		$valid = false;

		//Get users greater than the cursor from
		//$users = User::where( 'user_user_group', 4 )->get();
		$include = [ ];
		$include = [4,5];
		$exclude = [ ];
		$entry_rank = DB::table('entries')->where( 'entry_rank', '=', '0')->get();
		foreach( $entry_rank as $rank )
		{
			$exclude[ ] = $rank->entry_id;
		}
		$users = User::where( 'user_order', '>', 0 )->whereIn( 'user_user_group',$include )->orderBy( 'user_order', 'asc' )->get();
//		$order = 'entry_rank';
//		$dir = 'asc';
//		$query = DB::table('entries')
//		->select('entries.entry_user_id as user_id')
//		->where( 'entry_id', '>', '0' )->get();
//		$query = $query->where( 'entry_rank', '>', 0 );
//		$query = $query->where( 'entry_deleted', '=', 0 );
//		$query = $query->whereNotIn( 'entries.entry_id',$exclude );
//		$query = $query->orderBy( $order, $dir );
//		$query = $query->take( 10 );
//		$entries = $query;
//		$combined = $entries->union($team)->get();
//		$ids= [];
//		foreach( $team as $teamusers )
//		{
//			$ids[] = $teamusers->user_id;
//		}
//		$newOrderBy = implode(",",$ids);
//		$users = User::whereIn( 'user_id', $ids )->orderByRaw(DB::raw("FIELD(user_id, 544, 398, 426, 593, 386, 489, 519, 473, 557)"))->get();
//		$users = User::whereIn( 'user_id', $ids )->orderByRaw(DB::raw("FIELD(user_id, 544, 398, 426, 593, 386, 489, 557, 519, 473)"))->get();
//		$users = User::whereIn( 'user_id', $ids )->orderByRaw(DB::raw("FIELD(user_id, 473, 519, 489, 386, 593, 426, 544)"))->get();
		//Find total number to put in header
		//$count = User::where( 'user_user_group', 4 )->count();
//		$count = User::whereIn( 'user_id', $ids )->orderByRaw(DB::raw("FIELD(user_id, $newOrderBy)"))->count();
		$count = User::where( 'user_order', '>', 0 )->whereIn( 'user_user_group',$include )->orderBy( 'user_order', 'asc' )->count();
		if( $count == 0 )
		{
			$return = [ 'error' => 'No Team Users Found' ];
			$status_code = 404;

			return Response::make( $return, $status_code );
		}
		foreach( $users as $user )
		{
			$data = [ 'id'           => $user->user_id,
			'profileImage' => ( isset( $user->user_profile_image ) )
						? $client->getObjectUrl( Config::get('app.bucket'), $user->user_profile_image, '+60 minutes' ) : ''
			];
			if( ( $user->user_display_name == '' ) )
			{
				if( $user->user_facebook_id != 0 )
				{
					$data[ 'displayName' ] = $user->FacebookUser->facebook_user_display_name;
				}
				elseif( $user->user_twitter_id != 0 )
				{
					$data[ 'displayName' ] = $user->TwitterUser->twitter_user_display_name;
				}
				elseif( $user->user_google_id != 0 )
				{
					$data[ 'displayName' ] = $user->GoogleUser->google_user_display_name;
				}
			}
			else
			{
				$data[ 'displayName' ] = $user->user_display_name;
			}
			$return[ 'users' ][ ][ 'user' ] = $data;
		}
		$response = Response::make( $return, $status_code );

		$response->header( 'X-Total-Count', $count );

		return $response;
	}
	/**
	 * Display the specified resource.
	 *
	 * @param  int $user
	 *
	 * @return Response
	 */

	/**
	 *
	 * @SWG\Api(
	 *   path="/user/follow",
	 *   description="Operations about users",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Add default follow",
	 *       notes="Returns users requested. API-Token is required for this method.",
	 *       nickname="postFollowUsers",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="star",
	 *           description="ID or IDs of required users.",
	 *           paramType="query",
	 *           required=false,
	 *           type="comma seperated list"
	 *         )
	 *       ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=404,
	 *            message="No users found"
	 *          )
	 *        )
	 *       )
	 *     )
	 *   )
	 * )
	 */
	public function follow( )
	{

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		//Validate Input
		$rules = array(
			'star'    => 'required',
		);
		$messages = array(
		);

		$validator = Validator::make( Input::all(), $rules, $messages );

		// process the login
		if( $validator->fails() )
		{

			$return = $validator->messages();

			$response = Response::make( $return, 400 );

			return $response;
		}
		else
		{
			// get ids
			$id_commas = Input::get( 'star' );
			$id =  explode( ',', $id_commas );
			for( $i=0; $i<count($id); $i++ )
			{
				//Get input
				$input = array(
					'user_star_user_id' => $session->token_user_id,
					'user_star_star_id' => $id[$i],
					'user_star_deleted' => 0,
				);

				$star = Star::firstOrNew( $input );
				if( isset( $star->user_star_created_date ) )
				{
					continue;
				}
				else
				{
					$star->user_star_created_date = date( 'Y-m-d H:i:s' );
					$star->save();
				}
				$userid = $session->token_user_id;
				$name = getusernamebyid($userid);
				$to = $star->user_star_star_id;
				// For Notification Table entry
				$prev_not = Notification::where( 'notification_user_id', '=', $star->user_star_star_id, 'and' )
								->where( 'notification_entry_id', '=', $session->token_user_id, 'and' )
								->where( 'notification_details', '=', ' is now following you.', 'and' )
								->orderBy( 'notification_updated_date', 'desc' )
								->first();

				if( !count( $prev_not ) )
				{
					Notification::create( [ 'notification_user_id'      => $star->user_star_star_id,
											'notification_subject_ids'  => json_encode( [ $session->token_user_id ] ),
											'notification_details'      => ' is now following you.',
											'notification_icon'			=> 'follow.png',
											'notification_read'         => 0,
											'notification_entry_id'     => $session->token_user_id,
											'notification_type'         => 'Follow',
											'notification_created_date' => date( 'Y-m-d H:i:s' ),
											'notification_updated_date' => date( 'Y-m-d H:i:s' )
										  ] );
				}
				else
				{

					$subjects = json_decode( $prev_not->notification_subject_ids );

					if( !in_array( $session->token_user_id, $subjects ) )
					{
						array_push( $subjects, $session->token_user_id );

						$prev_not->notification_subject_ids = json_encode( $subjects );
						$prev_not->notification_read = 0;
						$prev_not->notification_updated_date = date( 'Y-m-d H:i:s' );

						$prev_not->save();
					}

				}
				// End For Notification Table entry
				// Added for make entry for push badge count
				$notification_count = 0;
				$inputbadge = array(
							'user_id' => $star->user_star_star_id,
						);

				$notificationcount = NotificationCount::firstOrNew( $inputbadge );
				if( isset( $notificationcount->id ) )
				{
					$notification_count = DB::table('notification_counts')
						->where('user_id','=',$star->user_star_star_id)
						->pluck( 'notification_count' );
					$notification_count = $notification_count + 1;
					$notificationcount->notification_count = $notification_count;
					$notificationcount->save();
				}
				else
				{
					$notificationcount->notification_count = 1;
					$notificationcount->save();
				}
				// End
				if(!empty($name))
				{
					$message = $name." is now following you.";
					// echo $message;
					// die();
					$usersDeviceData = DB::select( DB::raw("SELECT t1.* FROM
						(select device_registration_id,device_registration_device_type,device_registration_device_token,device_registration_date_created,device_registration_user_id
						from device_registrations where device_registration_device_token  != '' AND device_registration_device_token != 'mobstar'
						order by device_registration_date_created desc
						) t1 left join users u on t1.device_registration_user_id = u.user_id
						where u.user_deleted = 0
						AND u.user_id = $to
						order by t1.device_registration_date_created desc"));

					$icon = 'follow.png';
					$icon = 'http://' . $_ENV[ 'URL' ] . '/images/' . $icon;
					if(!empty($usersDeviceData))
					{
						for($k=0;$k<count($usersDeviceData);$k++)
						{
							$this->registerSNSEndpoint($usersDeviceData[$k],$message,$to,$name,$icon);
						}
					}
				}
			}
			$response[ 'message' ] = "follow successfully";
			$status_code = 201;
		}

		return Response::make( $response, $status_code );
	}
	/**
	 *
	 * @SWG\Api(
	 *   path="/user/follower",
	 *   description="Operation about Users",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Get user follower",
	 *       notes="Details about follower for passed user id",
	 *       nickname="followers",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="user",
	 *           description="ID of required users.",
	 *           paramType="query",
	 *           required=true,
	 *           type="integer"
	 *         )
	 *       ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=400,
	 *            message="Input validation failed"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */
	public function follower()
	{
		$client = getS3Client();
		$token = Request::header( "X-API-TOKEN" );
		$session = $this->token->get_session( $token );

		//Get user
		$user = ( Input::get( 'user', '0' ) );
		$user = ( !is_numeric( $user ) ) ? 0 : $user;
		// Get All users
		$alluser = ( Input::get( 'all', '0' ) );
		//Get limit to calculate pagination
		$limit = ( Input::get( 'limit', '50' ) );

		//If not numeric set it to the default limit
		$limit = ( !is_numeric( $limit ) || $limit < 1 ) ? 50 : $limit;

		//Get page
		$page = ( Input::get( 'page', '1' ) );
		$page = ( !is_numeric( $page ) ) ? 1 : $page;

		//Calculate offset
		$offset = ( $page * $limit ) - $limit;

		//If page is greter than one show a previous link
		if( $page > 1 )
		{
			$previous = true;
		}
		else
		{
			$previous = false;
		}
		$count = 0;
		/* Added By AJ */
		if( $user != 0 )
		{
			$starredBy = [ ];
			$count = DB::table( 'users' )
				->select( 'users.*','user_stars.user_star_created_date' )
				->leftJoin( 'user_stars', 'user_stars.user_star_user_id', '=', 'users.user_id' )
				->where( 'user_stars.user_star_deleted', '=', '0' )
				->where( 'users.user_deleted', '=', '0' )
				->where( 'user_stars.user_star_star_id', '=', $user )
				->groupBy('users.user_id')
				->orderBy('user_stars.user_star_created_date', 'DESC')
				->get();
			if($alluser != 0)
			{
				$results = DB::table( 'users' )
				->select( 'users.*','user_stars.user_star_created_date' )
				->leftJoin( 'user_stars', 'user_stars.user_star_user_id', '=', 'users.user_id' )
				->where( 'user_stars.user_star_deleted', '=', '0' )
				->where( 'users.user_deleted', '=', '0' )
				->where( 'user_stars.user_star_star_id', '=', $user )
				->groupBy('users.user_id')
				->orderBy('user_stars.user_star_created_date', 'DESC')->get();
			}
			else
			{
				$results = DB::table( 'users' )
				->select( 'users.*','user_stars.user_star_created_date' )
				->leftJoin( 'user_stars', 'user_stars.user_star_user_id', '=', 'users.user_id' )
				->where( 'user_stars.user_star_deleted', '=', '0' )
				->where( 'users.user_deleted', '=', '0' )
				->where( 'user_stars.user_star_star_id', '=', $user )
				->groupBy('users.user_id')
				->orderBy('user_stars.user_star_created_date', 'DESC')
				->take( $limit )->skip( $offset )->get();
			}


			//If the count is greater than the highest number of items displayed show a next link
			if(count($count)==0)
			{
				$return = [ 'error' => 'No Entries Found' ];
				$status_code = 404;
				return Response::make( $return, $status_code );
			}
			elseif( count($count) > ( $limit * $page ) )
			{
				$next = true;
			}
			else
			{
				$next = false;
			}
			foreach( $results as $starred )
			{
				$starNames = [];
				if(!empty($starred->user_id) && $starred->user_id == $session->token_user_id)
				{
					continue;
				}
				if( ( $starred->user_display_name == '' ) || ( is_null( $starred->user_name ) ) || ( is_null( $starred->user_email ) ) )
				{
					if( $starred->user_facebook_id != 0 )
					{
						$displayName = DB::table( 'facebook_users' )->where( 'facebook_user_id', '=', $starred->user_facebook_id )->pluck( 'facebook_user_display_name' );
					}
					elseif( $starred->user_twitter_id != 0 )
					{
						$displayName = DB::table( 'twitter_users' )->where( 'twitter_user_id', '=', $starred->user_twitter_id )->pluck( 'twitter_user_display_name' );
					}
					elseif( $starred->user_google_id != 0 )
					{
						$displayName = DB::table( 'google_users' )->where( 'google_user_id', '=', $starred->user_google_id )->pluck( 'google_user_display_name' );
					}
				}
				else
				{
					$displayName = $starred->user_display_name;
				}

				$starredBy[ ] = [ 'starId'       => $starred->user_id,
								  'starName'     => @$displayName,
								  'starredDate'  => $starred->user_star_created_date,
								  'profileImage' => ( isset( $starred->user_profile_image ) )
									  ? $client->getObjectUrl( Config::get('app.bucket'), $starred->user_profile_image, '+60 minutes' )
									  : '',
								  'profileCover' => ( isset( $starred->user_cover_image ) )
								  ? $client->getObjectUrl( Config::get('app.bucket'), $starred->user_cover_image, '+60 minutes' ) : '',
								  'isMyStar'  => Star::where( 'user_star_user_id', '=', $session->token_user_id )->where( 'user_star_star_id', '=', $starred->user_id )->where( 'user_star_deleted', '!=', '1' )->count(),
				];
			}
			//If next is true create next page link
			if($alluser == 0)
			{
				if( $next )
				{
					$return[ 'next' ] = url( "user/follower?user=".$user ."&". http_build_query( [ "limit" => $limit, "page" => $page + 1 ] ) );
				}

				if( $previous )
				{
					$return[ 'previous' ] = url( "user/follower?user=".$user ."&". http_build_query( [ "limit" => $limit, "page" => $page - 1 ] ) );
				}
			}
			$return[ 'starredBy' ] = $starredBy;
			$return['fans'] = count($starredBy);
			$status_code = 200;

		}
		else
		{
			$return = [ 'error' => 'No Entries Found' ];
			$status_code = 404;
		}
		$response = Response::make( $return, $status_code );
		$response->header( 'X-Total-Count', count($count) );
		/* End */
		return $response;
	}
	public function userRank()
	{
		//$entries = EntryFile::where('entry_file_entry_id', '=', '2699')->get();
		//mail('anil@spaceotechnologies.com',time(),print_r(count($entries),true));
		//die('here');
		// Rank
	    $entries_star = DB::table('entries')
		    ->select('entries.*')
		    ->join('users', 'entries.entry_user_id', '=', 'users.user_id')
		    ->where('entries.entry_deleted', '=', '0')
		    ->where(function($query)
		     {
		      	$query->where('entries.entry_rank', '!=', 0);
		     })
		    ->orderBy( 'entry_rank', 'asc' )
		    ->get();

		if(!empty($entries_star))
		{
			$users = [ ];
		    $tmp_star[ 'talents' ] = [];
		    $rank = 1;

	    	foreach( $entries_star as $entry_star )
		    {

				$entries_file_count = EntryFile::where('entry_file_entry_id', '=', $entry_star->entry_id)->count();
				if($entries_file_count <= 0)
					continue;
				if( (  $entries_file_count  < 2 ) &&  $entry_star->entry_type === 'audio' )
					continue;
				if( (  $entries_file_count  < 1 ) &&  $entry_star->entry_type === 'video' )
					continue;
				if(($entry_star->entry_category_id != 7 || $entry_star->entry_category_id != 8) && $entry_star->entry_deleted == 0)
			     {

				    if( !in_array( $entry_star->entry_user_id, $users ) )
				    {
				       $User = User::where('user_id' , '=', $entry_star->entry_user_id)->first();
				       $entries = Entry::where('entry_user_id', '=', $User->user_id)->get();
					   $stats = 100000;
						foreach($entries as $entry)
						{
							if(($entry->entry_category_id != 7 || $entry->entry_category_id != 8) && $entry->entry_deleted == 0)
							{
								if( $entry->entry_rank < $stats && $entry->entry_rank != 0 )
								{
									$stats = $entry->entry_rank;
								}
							}
						}
						if ($stats == 100000)
							$stats = 0;

						//get user's old rank
							$userRankData = User::find( $User->user_id );
							$oldrank = $userRankData->user_rank;
						//end

					   // Update user with its rank and stats
					   $user_update = User::find( $User->user_id );
					   $user_update->user_rank =$rank;
					   $user_update->user_entry_rank =$stats;
					   $user_update->save();
					   // End
					  	//get user's new rank
							$userNewRankData = User::find( $User->user_id );
							$newrank = $userNewRankData->user_rank;
						//end

						// ckeck for position of user
						/*if( $oldrank != $newrank)
						{
							$STR = "";
							$userID = $User->user_id;
							if($oldrank > 10 && $newrank < 11)
							{
								$message = "You are now in between top 10 and You are at position ".$newrank;

								$STR == $userID." ,Rank = ".$newrank ;
								//mail("anil@spaceotechnologies.com",time(),$message);
								// Added for make entry for push badge count
								$notification_count = 0;
								$inputbadge = array(
											'user_id' => $userID,
										);

								$notificationcount = NotificationCount::firstOrNew( $inputbadge );
								if( isset( $notificationcount->id ) )
								{
									$notification_count = DB::table('notification_counts')
										->where('user_id','=',$userID)
										->pluck( 'notification_count' );
									$notification_count = $notification_count + 1;
									$notificationcount->notification_count = $notification_count;
									$notificationcount->save();
								}
								else
								{
									$notificationcount->notification_count = 1;
									$notificationcount->save();
								}
								// End
								$usersDeviceData = DB::select( DB::raw("SELECT t1.* FROM
								  (select device_registration_id,device_registration_device_type,device_registration_device_token,device_registration_date_created,device_registration_user_id
								 	from device_registrations where device_registration_device_token  != '' AND device_registration_device_token != 'mobstar'
								 	order by device_registration_date_created desc
								  ) t1 left join users u on t1.device_registration_user_id = u.user_id
								  where u.user_deleted = 0
								 	AND u.user_id = $userID
								 	order by t1.device_registration_date_created desc LIMIT 1"));
								if(!empty($usersDeviceData))
								{
								 	$this->registerSNSEndpoint($usersDeviceData[0],$message);
								}
							}
							elseif ($oldrank < 11)
							{
								if($newrank < 11)
								{
									$message = "You are at position ".$newrank;
									$STR == $userID." ,Rank = ".$newrank ;
									//mail("anil@spaceotechnologies.com",time(),$message);
								}
								elseif ($newrank > 10)
								{
									$message = "You left the position from top 10.";
									$STR == $userID." ,Rank = ".$newrank ;
									//mail("anil@spaceotechnologies.com",time(),$STR);

								}
								// Added for make entry for push badge count
								$notification_count = 0;
								$inputbadge = array(
											'user_id' => $userID,
										);

								$notificationcount = NotificationCount::firstOrNew( $inputbadge );
								if( isset( $notificationcount->id ) )
								{
									$notification_count = DB::table('notification_counts')
										->where('user_id','=',$userID)
										->pluck( 'notification_count' );
									$notification_count = $notification_count + 1;
									$notificationcount->notification_count = $notification_count;
									$notificationcount->save();
								}
								else
								{
									$notificationcount->notification_count = 1;
									$notificationcount->save();
								}
								// End
								$usersDeviceData = DB::select( DB::raw("SELECT t1.* FROM
								  (select device_registration_id,device_registration_device_type,device_registration_device_token,device_registration_date_created,device_registration_user_id
								 	from device_registrations where device_registration_device_token  != '' AND device_registration_device_token != 'mobstar'
								 	order by device_registration_date_created desc
								  ) t1 left join users u on t1.device_registration_user_id = u.user_id
								  where u.user_deleted = 0
								 	AND u.user_id = $userID
								 	order by t1.device_registration_date_created desc LIMIT 1"));
								if(!empty($usersDeviceData))
								{
								 	$this->registerSNSEndpoint($usersDeviceData[0],$message);
								}
							}
						}*/
						//
					   $user1[ 'rank' ] = $rank;
				       $user1[ 'id' ] = $User->user_id;
				       $user1[ 'entry_rank' ] = $stats;
				       $tmp_star[ 'talents' ][ ][ 'talent' ] = $user1;
				       $users[ ] = $entry_star->entry_user_id;
				       $rank++;
				    }
			     }
			}
			$return['talents'] = $tmp_star[ 'talents' ];
			$status_code = 200;
		}
		else
		{
			$return = [ 'error' => 'No Entries Found' ];
			$status_code = 404;
		}
		return Response::make( $return , 200 );
	}
	public function registerSNSEndpoint( $device , $message, $to=NULL, $name=NULL,$icon = NULL)
	{
		$badge_count = 0;
		$badge_count = DB::table('notification_counts')
					->where('user_id','=',$device->device_registration_user_id)
					->pluck( 'notification_count' );
		if( $device->device_registration_device_type == "apple" )
		{
			$arn = "arn:aws:sns:eu-west-1:830026328040:app/APNS/adminpushdemo";
			//$arn = "arn:aws:sns:eu-west-1:830026328040:app/APNS_SANDBOX/adminsandbox";
		}
		else
		{
			$arn = "arn:aws:sns:eu-west-1:830026328040:app/GCM/admin-android-notification";
		}

		$sns = getSNSClient();

		$Model1 = $sns->listPlatformApplications();

		$result1 = $sns->listEndpointsByPlatformApplication(array(
			// PlatformApplicationArn is required
			'PlatformApplicationArn' => $arn,
		));
		//echo '<pre>';
		//$dtoken = 'APA91bHEx658AQzCM3xUHTVjBGJz8a_HMb65Y_2BIIPXODexYlvuCZpaJRKRchTNqQCXs_w9b0AxJbzIQOFNtYkW0bbsiXhiX7uyhGYNTYC2PBOZzAmvqnvOBBhOKNS7Jl0fdoIdNa_riOlJxQi8COrhbw0odIJKBg';
		//$dtoken = 'c39bac35f298c66d7398673566179deee27618c2036d8c82dcef565c8d732f84';
		foreach($result1['Endpoints'] as $Endpoint){
			$EndpointArn = $Endpoint['EndpointArn'];
			$EndpointToken = $Endpoint['Attributes'];
			foreach($EndpointToken as $key=>$newVals){
				if($key=="Token"){
					if($device->device_registration_device_token==$newVals){
					//if($dtoken==$newVals){
					//Delete ARN
						$result = $sns->deleteEndpoint(array(
							// EndpointArn is required
							'EndpointArn' => $EndpointArn,
						));
					}
				}
				//print_r($EndpointToken);
			}
			//print_r($Endpoint);
		}

		 $result = $sns->createPlatformEndpoint(array(
			 // PlatformApplicationArn is required
			 'PlatformApplicationArn' => $arn,
			 // Token is required
			 //'Token' => $dtoken,
			 'Token' => $device->device_registration_device_token,

		 ));

		 $endpointDetails = $result->toArray();

		 //print_r($device);echo "\n".$message."\n";print_r($result);print_r($endpointDetails);

		 //die;
		 if($device->device_registration_device_type == "apple")
		 {
			if(!empty($to) && !empty($name))
			{
				$publisharray = array(
					'TargetArn' => $endpointDetails['EndpointArn'],
					'MessageStructure' => 'json',
					 'Message' => json_encode(array(
						'default' => $message,
						//'APNS_SANDBOX' => json_encode(array(
						'APNS' => json_encode(array(
							'aps' => array(
								"sound" => "default",
								"alert" => $message,
								"badge"=> intval($badge_count),
								"userId"=>$to,
								"diaplayname"=>$name,
								"Type"=>"Follow",
								"notificationIcon"=>$icon,
							)
						)),
					))
				 );
			}
			else
			{
				$publisharray = array(
					'TargetArn' => $endpointDetails['EndpointArn'],
					'MessageStructure' => 'json',
					 'Message' => json_encode(array(
						'default' => $message,
						//'APNS_SANDBOX' => json_encode(array(
						'APNS' => json_encode(array(
							'aps' => array(
								"sound" => "default",
								"alert" => $message,
								"badge"=> intval($badge_count),
							)
						)),
					))
				 );
			}
		 }
		 else
		 {
			if(!empty($to) && !empty($name))
			{
				$publisharray = array(
					'TargetArn' => $endpointDetails['EndpointArn'],
					'MessageStructure' => 'json',
					'Message' => json_encode(array(
						'default' => $message,
						'GCM'=>json_encode(array(
							'data'=>array(
								'message'=> $message,
								"badge"=> intval($badge_count),
								"userId"=>$to,
								"diaplayname"=>$name,
								"Type"=>"Follow"
							)
						))
					))
				);
			}
			else
			{
				$publisharray = array(
					'TargetArn' => $endpointDetails['EndpointArn'],
					'MessageStructure' => 'json',
					'Message' => json_encode(array(
						'default' => $message,
						'GCM'=>json_encode(array(
							'data'=>array(
								'message'=> $message,
								"badge"=> intval($badge_count)
							)
						))
					))
				);
			}
		 }
		 try
		 {
			$sns->publish($publisharray);

			$myfile = 'sns-log.txt';
			file_put_contents($myfile, date('d-m-Y H:i:s') . ' debug log:', FILE_APPEND);
			file_put_contents($myfile, print_r($endpointDetails, true), FILE_APPEND);

			//print($EndpointArn . " - Succeeded!\n");
		 }
		 catch (Exception $e)
		 {
			//print($endpointDetails['EndpointArn'] . " - Failed: " . $e->getMessage() . "!\n");
		 }


	}
	public function uploadimage()
	{
		/*code for upload user profile and cover image*/
		$user = User::find($_POST['user_id']);

		$userprofile = Input::file( 'user_profile_image' );

		if( !empty( $userprofile ) )
		{
			$file_in = $userprofile->getRealPath();

			$file_out = 'profile/' . $_POST['user_id'] . "-" . str_random( 12 ) . ".jpg";

			$img = Image::make( $file_in );

			$img->resize( 200, 200 );

			$img->save( $_ENV[ 'PATH' ] . '/public/' . $file_out, 80 );

			$handle = fopen( $_ENV[ 'PATH' ] . '/public/' . $file_out, "r" );

			Flysystem::connection( 'awss3' )->put( $file_out,
												   fread( $handle,
														  filesize( $_ENV[ 'PATH' ] . '/public/' . $file_out ) ) );

			$user->user_profile_image = $file_out;
		}

		$usercoverprofile = Input::file( 'user_cover_image' );

		if( !empty( $usercoverprofile ) )
		{
			$file_in = $usercoverprofile->getRealPath();
			$file_out = 'profile/' . $_POST['user_id'] . "-" . str_random( 12 ) . ".jpg";

			$img = Image::make( $file_in );

			$img->resize( 200, 200 );

			$img->save( $_ENV[ 'PATH' ] . '/public/' . $file_out, 80 );

			$handle = fopen( $_ENV[ 'PATH' ] . '/public/' . $file_out, "r" );

			Flysystem::connection( 'awss3' )->put( $file_out,
												   fread( $handle,
														  filesize( $_ENV[ 'PATH' ] . '/public/' . $file_out ) ) );

			$user->user_cover_image = $file_out;
		}
		$user->save();
		header("Location:http://admin.mobstar.com/user/".$_POST['user_id']);
		exit();
		/*end of code for upload user profile and cover image*/
	}
	// Function for user analytics data capture
	public function analytic( )
	{

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		//Validate Input
		$rules = array(
			'platform'    => 'required',
			'osversion'    => 'required',
			'appversion'    => 'required',
		);
		$messages = array(
		);

		$validator = Validator::make( Input::all(), $rules, $messages );

		// process the login
		if( $validator->fails() )
		{

			$return = $validator->messages();

			$response = Response::make( $return, 400 );

			return $response;
		}
		else
		{
			$user_analytic_user_id = $session->token_user_id;
			$user_analytic_platform = Input::get( 'platform' );
			$user_analytic_os_version = Input::get( 'osversion' );
			$user_analytic_device_name = Input::get( 'devicename' );
			$user_analytic_app_version = Input::get( 'appversion' );

			$useranalytic = UserAnalytic::where( 'user_analytic_user_id', '=', $session->token_user_id )->first();
			if( $useranalytic )
			{
				$useranalytic->user_analytic_user_id = $user_analytic_user_id;
				$useranalytic->user_analytic_platform = $user_analytic_platform;
				$useranalytic->user_analytic_os_version = $user_analytic_os_version;
				$useranalytic->user_analytic_device_name = $user_analytic_device_name;
				$useranalytic->user_analytic_app_version = $user_analytic_app_version;
				$useranalytic->user_analytic_created_at = date( 'Y-m-d H:i:s' );
				$useranalytic->save();
			}
			else
			{
				UserAnalytic::create( [ 'user_analytic_user_id'     => $user_analytic_user_id,
										'user_analytic_platform'    => $user_analytic_platform,
										'user_analytic_os_version'  => $user_analytic_os_version,
										'user_analytic_device_name'	=> $user_analytic_device_name,
										'user_analytic_app_version' => $user_analytic_app_version,
										'user_analytic_created_at'  => date( 'Y-m-d H:i:s' )] );
			}
			$response[ 'message' ] = "Analytics data stored successfully";
			$status_code = 201;
		}

		return Response::make( $response, $status_code );
	}
	// End
	/**
	 *
	 * @SWG\Api(
	 *   path="/user/following",
	 *   description="Operation about Users",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Get user following",
	 *       notes="Details about following for passed user id",
	 *       nickname="followings",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="user",
	 *           description="ID of required users.",
	 *           paramType="query",
	 *           required=true,
	 *           type="integer"
	 *         )
	 *       ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=400,
	 *            message="Input validation failed"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */
	public function following()
	{
		$client = getS3Client();
		$token = Request::header( "X-API-TOKEN" );
		$session = $this->token->get_session( $token );

		//Get user
		$user = ( Input::get( 'user', '0' ) );
		$user = ( !is_numeric( $user ) ) ? 0 : $user;
		//Get limit to calculate pagination
		$limit = ( Input::get( 'limit', '50' ) );

		//If not numeric set it to the default limit
		$limit = ( !is_numeric( $limit ) || $limit < 1 ) ? 50 : $limit;

		//Get page
		$page = ( Input::get( 'page', '1' ) );
		$page = ( !is_numeric( $page ) ) ? 1 : $page;

		//Calculate offset
		$offset = ( $page * $limit ) - $limit;

		//If page is greter than one show a previous link
		if( $page > 1 )
		{
			$previous = true;
		}
		else
		{
			$previous = false;
		}
		$count = 0;
		/* Added By AJ */
		if( $user != 0 )
		{
			$stars = [ ];
			$count = DB::table( 'users' )
				->select( 'users.*','user_stars.user_star_created_date' )
				->leftJoin( 'user_stars', 'user_stars.user_star_star_id', '=', 'users.user_id' )
				->where( 'user_stars.user_star_deleted', '=', '0' )
				->where( 'users.user_deleted', '=', '0' )
				->where( 'user_stars.user_star_user_id', '=', $user )
				->groupBy('users.user_id')
				->orderBy('user_stars.user_star_created_date', 'DESC')
				->get();
			$results = DB::table( 'users' )
				->select( 'users.*','user_stars.user_star_created_date' )
				->leftJoin( 'user_stars', 'user_stars.user_star_star_id', '=', 'users.user_id' )
				->where( 'user_stars.user_star_deleted', '=', '0' )
				->where( 'users.user_deleted', '=', '0' )
				->where( 'user_stars.user_star_user_id', '=', $user )
				->groupBy('users.user_id')
				->orderBy('user_stars.user_star_created_date', 'DESC')
				->take( $limit )->skip( $offset )->get();
			//If the count is greater than the highest number of items displayed show a next link
			if(count($count)==0)
			{
				$return = [ 'error' => 'No Entries Found' ];
				$status_code = 404;
				return Response::make( $return, $status_code );
			}
			elseif( count($count) > ( $limit * $page ) )
			{
				$next = true;
			}
			else
			{
				$next = false;
			}
			foreach( $results as $starred )
			{
				if(!empty($starred->user_id) && $starred->user_id == $session->token_user_id)
				{
					continue;
				}
				if( ( $starred->user_display_name == '' ) || ( is_null( $starred->user_name ) ) || ( is_null( $starred->user_email ) ) )
				{
					if( $starred->user_facebook_id != 0 )
					{
						$displayName = DB::table( 'facebook_users' )->where( 'facebook_user_id', '=', $starred->user_facebook_id )->pluck( 'facebook_user_display_name' );
					}
					elseif( $starred->user_twitter_id != 0 )
					{
						$displayName = DB::table( 'twitter_users' )->where( 'twitter_user_id', '=', $starred->user_twitter_id )->pluck( 'twitter_user_display_name' );
					}
					elseif( $starred->user_google_id != 0 )
					{
						$displayName = DB::table( 'google_users' )->where( 'google_user_id', '=', $starred->user_google_id )->pluck( 'google_user_display_name' );
					}
				}
				else
				{
					$displayName = $starred->user_display_name;
				}

				$stars[ ] = [ 'starId'       => $starred->user_id,
								  'starName'     => @$displayName,
								  'starredDate'  => $starred->user_star_created_date,
								  'profileImage' => ( isset( $starred->user_profile_image ) )
									  ? $client->getObjectUrl( Config::get('app.bucket'), $starred->user_profile_image, '+60 minutes' )
									  : '',
								  'profileCover' => ( isset( $starred->user_cover_image ) )
								  ? $client->getObjectUrl( Config::get('app.bucket'), $starred->user_cover_image, '+60 minutes' ) : '',
								  'rank'     => DB::table('users')->where( 'user_id', '=', $starred->user_id )->pluck('user_rank'),
								  'stat'     => DB::table('users')->where( 'user_id', '=', $starred->user_id )->pluck('user_entry_rank'),
				];
			}
			//If next is true create next page link
			if( $next )
			{
				$return[ 'next' ] = url( "user/following?user=".$user ."&". http_build_query( [ "limit" => $limit, "page" => $page + 1 ] ) );
			}

			if( $previous )
			{
				$return[ 'previous' ] = url( "user/following?user=".$user ."&". http_build_query( [ "limit" => $limit, "page" => $page - 1 ] ) );
			}
			$return[ 'stars' ] = $stars;
			$status_code = 200;

		}
		else
		{
			$return = [ 'error' => 'No Entries Found' ];
			$status_code = 404;
		}
		$response = Response::make( $return, $status_code );
		$response->header( 'X-Total-Count', count($count) );
		return $response;
	}
	// Logout API created on 14-Jul-2015
	public function logout()
	{
		$token = Request::header( "X-API-TOKEN" );
		$session = $this->token->get_session( $token );
		$rules = array(
			'device'   => 'in:apple,google', // device type, must be google or apple
			'deviceToken'    => 'required' // token is required
		);
		// run the validation rules on the inputs
		$validator = Validator::make( Input::all(), $rules );

		// if the validator fails, return errors
		if( $validator->fails() )
		{
			$return = $validator->messages();
			$status_code = 401;
			$response = Response::make( $return, $status_code );
			return $response;
		}
		else
		{
			$deviceToken = Input::get( 'deviceToken' );
			$deviceType = Input::get( 'device' );
			if( isset( $deviceType ) && isset( $deviceToken ) )
			{
				$device_registration_id = DB::table('device_registrations')
						->where('device_registration_user_id', '=', $session->token_user_id)
						->where('device_registration_device_type', '=', $deviceType)
						->where('device_registration_device_token', '=', $deviceToken)
						->pluck('device_registration_id');
				if(!empty($device_registration_id))
				{
					DeviceRegistration::where('device_registration_id', '=', $device_registration_id)->delete();
					$status_code = 200;
					$response['message'] = "Logout successfully";
					return Response::make($response, $status_code);
				}
				else
				{
					$return = array( "error" => "No such deviceToken found" );
					$status_code = 401;
					$response = Response::make( $return, $status_code );
					return $response;
				}
			}
			else
			{
				$return = array( "error" => "Error while process logout" );
				$status_code = 401;
				$response = Response::make( $return, $status_code );
				return $response;
			}
		}
	}
	// End
}
