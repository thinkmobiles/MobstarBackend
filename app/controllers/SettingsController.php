<?php

use MobStar\Storage\Token\TokenRepository as Token;
use Swagger\Annotations as SWG;

/**
 * @package
 * @category
 * @subpackage
 *
 * @SWG\Resource(
 *  apiVersion=0.2,
 *  swaggerVersion=1.2,
 *  basePath="http://api.mobstar.com"
 * )
 */
class SettingsController extends BaseController
{

	public function __construct( Token $token )
	{
		$this->token = $token;
	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/settings/account",
	 *   description="Operations for settings",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="Get the current users linked accounts details",
	 *       notes="Returns user account details for the logged in user",
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=404,
	 *            message="No entries found"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function account()
	{
		$return = [ ];

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$user = User::find( $session->token_user_id );
		$return[ 'user' ][ 'id' ] = $session->token_user_id;
		$return[ 'user' ][ 'userContinent' ] = $user->user_continent;

		if( $user->user_facebook_id != 0 )
		{
			$facebook_user = FacebookUser::find( $user->user_facebook_id );

			$return[ 'user' ][ 'facebook' ] = [
				'id'          => $facebook_user->facebook_user_facebook_id,
				'displayName' => $facebook_user->facebook_user_display_name,
				'userName'    => $facebook_user->facebook_user_user_name,
				'email'       => $facebook_user->facebook_user_email,
				'gender'      => $facebook_user->facebook_user_gender,
				'fullName'    => $facebook_user->facebook_user_full_name,
			];
		}
		else
		{
			$return[ 'user' ][ 'facebook' ] = false;
		}

		if( $user->user_twitter_id != 0 )
		{
			$twitter_user = TwitterUser::find( $user->user_twitter_id );

			$return[ 'user' ][ 'twitter' ] = [
				'id'          => $twitter_user->twitter_user_twitter_id,
				'displayName' => $twitter_user->twitter_user_display_name,
				'userName'    => $twitter_user->twitter_user_user_name,
				'fullName'    => $twitter_user->twitter_user_full_name,
			];
		}
		else
		{
			$return[ 'user' ][ 'twitter' ] = false;
		}

		if( $user->user_google_id != 0 )
		{
			$google_user = GoogleUser::find( $user->user_google_id );

			$return[ 'user' ][ 'google' ] = [
				'id'          => $google_user->google_user_google_id,
				'displayName' => $google_user->google_user_display_name,
				'userName'    => $google_user->google_user_user_name,
				'fullName'    => $google_user->google_user_full_name,
			];
		}
		else
		{
			$return[ 'user' ][ 'google' ] = false;
		}

		$response = Response::make( $return, 200 );

		return $response;
	}


	/**
	 *
	 * @SWG\Api(
	 *   path="/settings/account",
	 *   description="Operations for settings",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Add a new account to users linked accounts",
	 *       notes="",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="userId",
	 *           description="Google/Facebook/Twitter user ID",
	 *           paramType="form",
	 *           required=true,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="displayName",
	 *           description="Name from Google/Facebook/Twitter profile",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="userName",
	 *           description="Email address from Google/Facebook/Twitter profile",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="fullName",
	 *           description="Full Name from Google/Facebook/Twitter profile",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="email",
	 *           description="Email address from facebook profile",
	 *           paramType="form",
	 *           required=false,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="gender",
	 *           description="Gender, from facebook profile",
	 *           paramType="form",
	 *           required=false,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="type",
	 *           description="Current or All time.",
	 *           paramType="query",
	 *           required=true,
	 *           type="string",
	 *           enum="['google','facebook','twitter']"
	 *         )
	 * 		 ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=404,
	 *            message="No entries found"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function addAccount()
	{
	    markDead( __METHOD__ );

		$type = Input::get( 'type' );

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$user = User::find( $session->token_user_id );

		try
		{
			switch( $type )
			{
				case "facebook":
					$facebook_user = FacebookUser::firstOrNew( array( 'facebook_user_facebook_id' => Input::get( 'userId' ) ) );

					$facebook_user->facebook_user_display_name = Input::get( 'displayName' );
					$facebook_user->facebook_user_user_name = Input::get( 'userName' );
					$facebook_user->facebook_user_email = Input::get( 'email' );
					$facebook_user->facebook_user_gender = Input::get( 'gender' );
					$facebook_user->facebook_user_full_name = Input::get( 'fullName' );

					$facebook_user->save();

					$user->user_facebook_id = $facebook_user->facebook_user_id;
					$user->save();
					$return = [ 'info' => 'Facebook user added' ];
					break;

				case "twitter":
					$twitter_user = TwitterUser::firstOrNew( array( 'twitter_user_twitter_id' => Input::get( 'userId' ) ) );

					$twitter_user->twitter_user_twitter_id = Input::get( 'userId' );
					$twitter_user->twitter_user_display_name = Input::get( 'displayName' );
					$twitter_user->twitter_user_full_name = Input::get( 'fullName' );
					$twitter_user->twitter_user_user_name = Input::get( 'userName' );

					$twitter_user->save();

					$user->user_twitter_id = $twitter_user->twitter_user_id;
					$user->save();
					$return = [ 'info' => 'Twitter user added' ];
					break;

				case "twitter":
					$google_user = GoogleUser::firstOrNew( array( 'google_user_google_id' => Input::get( 'userId' ) ) );

					$google_user->google_user_google_id = Input::get( 'userId' );
					$google_user->google_user_display_name = Input::get( 'displayName' );
					$google_user->google_user_user_name = Input::get( 'userName' );
					$google_user->google_user_full_name = Input::get( 'fullName' );

					$user->user_google_id = $google_user->google_user_id;
					$user->save();
					$return = [ 'info' => 'Google user added' ];

					break;

				default:
					$return = [ 'error' => 'Nothing added - incorrect type, please select "facebook", "twitter" or "google"' ];
			}

		}
		catch( Exception $ex )
		{
			return [ 'error' => "An error occurred, nothing was added" ];
		}

		return $return;
	}


	public function setUserContinent()
	{
	    $return = [ ];

	    $token = Request::header( "X-API-TOKEN" );

	    $session = $this->token->get_session( $token );

	    $user = User::find( $session->token_user_id );

	    if( empty( $user ) ) // must never happens
	    {
	        error_log( 'User, registered in session, not found. User id: '.$session->token_user_id );
	        $return['error'] = 'User not found';
	        $return['userContinent'] = 0;
	        return Response::make( $return, 400 );
	    }

	    // get continents ids
	    // continet 0 (all world) is not allowed to set manualy
	    $continentIds = DB::table('continents')->where( 'continent_id', '<>', 0 )->lists( 'continent_id');

	    // validate input fields
	    $validator = Validator::make( Input::get(), array(
	        'userContinent' => 'required|numeric|in:'.implode( ',', $continentIds )
	    ));

	    if( $validator->fails() )
	    {
	        $return['errors'] = $validator->messages();
	        $return['userContinent'] = $user->user_continent;
	        return Response::make( $return, 400 );
	    }

	    $newUserContinent = (int)Input::get( 'userContinent' );

	    $user->user_continent = $newUserContinent;

	    $user->save();

	    // update user entries without continent
	    DB::table( 'entries' )
	       ->where( 'entry_user_id', '=', $user->user_id )
	       ->where( 'entry_continent', '=', 0 )
	       ->update( array( 'entry_continent' => $user->user_continent ) );

	    $return['userContinent'] = $user->user_continent;

	    return Response::make( $return, 200 );
	}


	public function setUserContinentFilter()
	{
	    $return = [];

	    $token = Request::header( "X-API-TOKEN" );

	    $session = $this->token->get_session( $token );

	    $user = User::find( $session->token_user_id );

	    $currentFilter = $user->getContinentFilter();

	    // get continents ids
	    $continentIds = DB::table('continents')->where( 'continent_id', '<>', 0 )->lists( 'continent_id');

	    // validate input fields
	    $validator = Validator::make( Input::get(), array(
	        'continentFilter' => 'required',
	    ));

	    if( $validator->fails() )
	    {
	        $return['errors'] = $validator->messages();
	        $return['continentFilter'] = $currentFilter; // return current user filter
	        return Response::make( $return, 400 );
	    }

	    $newFilter = $this->makeContinentFilterFromParam( Input::get( 'continentFilter' ), $continentIds );

	    // validate continent ids. Also verifies that 0 continent id is not present
	    $invalidIds = array();
	    foreach( $newFilter as $id )
	    {
	        if( ! in_array( $id, $continentIds ) ) $invalidIds[] = $id;
	    }
	    if( $invalidIds )
	    {
	        $return['error'] = 'Invalid continent ids: '.implode( ', ', $invalidIds );
	        $return['continentFilter'] = $currentFilter;
	        return Response::make( $return, 400 );
	    }

	    // input validated, save new filter
	    $user->setContinentFilter( $newFilter );

	    $user->save();

	    $return['continentFilter'] = $user->getContinentFilter();

	    return Response::make( $return, 200 );
	}


	public function getUserContinentFilter()
	{
	    $return = [];

	    $token = Request::header( "X-API-TOKEN" );

	    $session = $this->token->get_session( $token );

	    $user = User::find( $session->token_user_id );

	    $return['continentFilter'] = $user->getContinentFilter();

	    return Response::make( $return, 200 );
	}


	private function makeContinentFilterFromParam( $param, array $allIds = null )
	{
	    if( $param )
	    {
	        $filter = json_decode( $param );
	        if( ! $filter ) $filter = array();
	    }
	    else
	    {
	        $filter = array();
	    }

	    // if there is set all continent ids, collapse them to empty array
	    $isAllIds = true;
	    if( $filter && $allIds )
	    {
            foreach( $allIds as $id )
            {
                if( ! in_array( $id, $filter ) )
                {
                    $isAllIds = false;
                    break;
                }
            }
	    }

	    if( $isAllIds ) $filter = array();
	    else $filter = array_unique( $filter );

	    return $filter;
	}


	public function setUserCategoryFilter()
	{
	    $return = [];

	    $token = Request::header( "X-API-TOKEN" );

	    $session = $this->token->get_session( $token );

	    $user = User::find( $session->token_user_id );

	    $currentFilter = $user->getCategoryFilter();

	    // get continents ids
	    $categoryIds = DB::table('categories')
	       ->whereNotIn( 'category_id', array(7,8) )
	       ->where( 'category_active', '>', 0)
	       ->lists( 'category_id');

	    // validate input fields
	    $validator = Validator::make( Input::get(), array(
	        'categoryFilter' => 'required',
	    ));

	    if( $validator->fails() )
	    {
	        $return['errors'] = $validator->messages();
	        $return['categoryFilter'] = $currentFilter; // return current user filter
	        return Response::make( $return, 400 );
	    }

	    $newFilter = $this->makeCategoryFilterFromParam( Input::get( 'categoryFilter' ), $categoryIds );

	    // validate category ids. Also verifies that 0 category id is not present
	    $invalidIds = array();
	    foreach( $newFilter as $id )
	    {
	        if( ! in_array( $id, $categoryIds ) ) $invalidIds[] = $id;
	    }
	    if( $invalidIds )
	    {
	        $return['error'] = 'Invalid category ids: '.implode( ', ', $invalidIds );
	        $return['categoryFilter'] = $currentFilter;
	        return Response::make( $return, 400 );
	    }

	    // input validated, save new filter
	    $user->setCategoryFilter( $newFilter );

	    $user->save();

	    $return['categoryFilter'] = $user->getCategoryFilter();

	    return Response::make( $return, 200 );
	}


	public function getUserCategoryFilter()
	{
	    $return = [];

	    $token = Request::header( "X-API-TOKEN" );

	    $session = $this->token->get_session( $token );

	    $user = User::find( $session->token_user_id );

	    $return['categoryFilter'] = $user->getCategoryFilter();

	    return Response::make( $return, 200 );
	}


	private function makeCategoryFilterFromParam( $param, array $allIds = null )
	{
	    if( $param )
	    {
	        $filter = json_decode( $param );
	        if( ! $filter ) $filter = array();
	    }
	    else
	    {
	        $filter = array();
	    }

	    // if there is set all continent ids, collapse them to empty array
	    $isAllIds = true;
	    if( $filter && $allIds )
	    {
	        foreach( $allIds as $id )
	        {
	            if( ! in_array( $id, $filter ) )
	            {
	                $isAllIds = false;
	                break;
	            }
	        }
	    }

	    if( $isAllIds ) $filter = array();
	    else $filter = array_unique( $filter );

	    return $filter;
	}
}
