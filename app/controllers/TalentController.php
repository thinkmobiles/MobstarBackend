<?php

use MobStar\Storage\Token\TokenRepository as Token;
use Swagger\Annotations as SWG;
use Aws\S3\S3Client;

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
class TalentController extends BaseController
{

	public function __construct( Token $token )
	{
		$this->token = $token;
	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/talent",
	 *   description="Operations for My Talent Screen",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="Get the current users top talents",
	 *       notes="Returns entry objects",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="period",
	 *           description="Current or All time.",
	 *           paramType="query",
	 *           required=false,
	 *           type="string",
	 *             enum="['current','allTime']"
	 *         )
	 * 		  ),
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

	public function index()
	{
	    markDeprecated( __METHOD__ );

		$return = [ ];

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$votes = Vote::where( 'vote_user_id', '=', $session->token_user_id )->where('vote_up', '=', 1)->where('vote_deleted', '=', 0)->lists( 'vote_entry_id' );

		if(count($votes) > 0)
		{
			$entries = Entry::whereIn( 'entry_id', $votes )->orderBy( 'entry_rank', 'asc')->get();

			foreach( $entries as $entry )
			{
				$return[ 'entries' ][ ][ 'entry' ] = oneEntry( $entry, $session, true );
			}
		}

		else{
			$return['entries'] = [];
		}

		$response = Response::make( $return, 200 );

		return $response;
	}



	/**
	 *
	 * @SWG\Api(
	 *   path="/talent/top",
	 *   description="Operations for My Talent Screen",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="Get All Talents",
	 *       notes="Operation to retrieve a list of all users in order of their rank",
	 *       nickname="allTalents",
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

	public function top()
	{
		$return = [ ];

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );
		/*
		$entries = Entry::where('entry_rank', '!=', 0)->with( 'user' )->orderBy( 'entry_rank', 'asc' )->get();

		$users = [ ];
		$return[ 'talents' ] = [];

		$rank = 1;

		foreach( $entries as $entry )
		{
			if( !in_array( $entry->entry_user_id, $users ) )
			{
				$user = oneUser( $entry->user, $session );
				$user[ 'rank' ] = $rank;
				$return[ 'talents' ][ ][ 'talent' ] = $user;
				$users[ ] = $entry->entry_user_id;
				$rank++;
			}
		}
		*/
		$entries = DB::table('entries')
		->select('entries.*')
		->join('users', 'entries.entry_user_id', '=', 'users.user_id')
		->where('entries.entry_deleted', '=', '0')
	    ->where(function($query)
            {
                $query->where('entries.entry_rank', '!=', 0);
            })
        ->orderBy( 'entry_rank', 'asc' )
		->get();
		$users = [ ];
		$return[ 'talents' ] = [];

		$rank = 1;

		foreach( $entries as $entry )
		{
			if( !in_array( $entry->entry_user_id, $users ) )
			{
				$User = User::where('user_id' , '=', $entry->entry_user_id)->first();
				$user = oneUser( $User, $session );
				$user[ 'rank' ] = $rank;
				$return[ 'talents' ][ ][ 'talent' ] = $user;
				$users[ ] = $entry->entry_user_id;
				$rank++;
			}
		}
		$response = Response::make( $return, 200 );

		return $response;
	}



	/**
	 *
	 * @SWG\Api(
	 *   path="/talent",
	 *   description="Operations for My Talent Screen",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="DELETE",
	 *       summary="Remove Talent",
	 *       notes="Operation for user to remove another user from their talent pool",
	 *       nickname="deleteTalent",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="user",
	 *           description="User ID who you want to remove from your talent pool.",
	 *           paramType="path",
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

	public function delete($user)
	{
	    markDeprecated( __METHOD__ );

		$return = [ ];

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$entries = Entry::where('entry_user_id', '=', $user)->lists('entry_id');

		DB::transaction(function() use ($entries)
		{
			foreach ($entries as $entry){
				DB::table('votes')
					->where('vote_entry_id', $entry )
					->update(array('vote_deleted' => 0));
			}
		});

		$return['talent'] = "All votes deleted";

		$response = Response::make( $return, 200 );

		return $response;
	}
	/**
	 *
	 * @SWG\Api(
	 *   path="/talent/topnew",
	 *   description="Operations for My Talent Screen",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="Get All Talents",
	 *       notes="Operation to retrieve a list of all users in order of their rank",
	 *       nickname="allTalents",
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

	public function topnew()
	{
		$client = getS3Client();
		$return = [ ];

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );
		$currentUser = User::find( $session->token_user_id );

		if( $currentUser->getContinentFilter() ) {
		    return $this->topnew_v2(); // new vertion. Use geoFilter
		}

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

		$count = DB::table('users')
		->select('users.user_id','users.user_name','users.user_display_name','users.user_facebook_id','users.user_google_id','users.user_twitter_id','users.user_profile_image','users.user_cover_image','users.user_rank')
		->join('entries', 'entries.entry_user_id', '=', 'users.user_id')
		->join('entry_files', 'entry_files.entry_file_entry_id', '=', 'entries.entry_id')
		->where('entries.entry_deleted', '=', '0')
		->where('entry_files.entry_file_deleted', '=', '0')
		->where('users.user_deleted', '=', '0')
	    ->where(function($query)
            {
                $query->where('users.user_rank', '!=', 0);
            })
        ->groupBy( 'users.user_id' )
        ->orderBy( 'user_rank', 'asc' )
		->get();

		$entries = DB::table('users')
		->select('users.user_id','users.user_name','users.user_display_name','users.user_email','users.user_facebook_id','users.user_google_id','users.user_twitter_id','users.user_profile_image','users.user_cover_image','users.user_rank')
		->join('entries', 'entries.entry_user_id', '=', 'users.user_id')
		->join('entry_files', 'entry_files.entry_file_entry_id', '=', 'entries.entry_id')
		->where('entries.entry_deleted', '=', '0')
		->where('entry_files.entry_file_deleted', '=', '0')
		->where('users.user_deleted', '=', '0')
	    ->where(function($query)
            {
                $query->where('users.user_rank', '!=', 0);
            })
        ->groupBy( 'users.user_id' )
        ->orderBy( 'user_rank', 'asc' )
		->take( $limit )->skip( $offset )->get();

		//If the count is greater than the highest number of items displayed show a next link
		if(count($count)==0)
		{
			$return = [ 'error' => 'No Top Talent Found' ];
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

		$users = [ ];
		$return[ 'talents' ] = [];

		foreach( $entries as $entry )
		{
			if( ( $entry->user_display_name == '' ) || ( is_null( $entry->user_name ) ) || ( is_null( $entry->user_email ) ) )
			{
				if( $entry->user_facebook_id != 0 )
				{
					$displayName = DB::table( 'facebook_users' )->where( 'facebook_user_id', '=', $entry->user_facebook_id )->pluck( 'facebook_user_display_name' );
				}
				elseif( $entry->user_twitter_id != 0 )
				{
					$displayName = DB::table( 'twitter_users' )->where( 'twitter_user_id', '=', $entry->user_twitter_id )->pluck( 'twitter_user_display_name' );
				}
				elseif( $entry->user_google_id != 0 )
				{
					$displayName = DB::table( 'google_users' )->where( 'google_user_id', '=', $entry->user_google_id )->pluck( 'google_user_display_name' );
				}
			}
			else
			{
				$displayName = $entry->user_display_name;
			}

			$return[ 'talents' ][ ][ 'talent' ] = [ 'id' => $entry->user_id,
								  'userName'     => @$displayName,
								  'displayName'  => @$displayName,
								  'profileImage' => ( isset( $entry->user_profile_image ) )
									  ? $client->getObjectUrl( Config::get('app.bucket'), $entry->user_profile_image, '+60 minutes' )
									  : '',
								  'profileCover' => ( isset( $entry->user_cover_image ) )
								  ? $client->getObjectUrl( Config::get('app.bucket'), $entry->user_cover_image, '+60 minutes' ) : '',
								  'rank'     => $entry->user_rank
				];

		}
		if( $next )
		{
			$return[ 'next' ] = url( "talent/topnew?". http_build_query( [ "limit" => $limit, "page" => $page + 1 ] ) );
		}

		if( $previous )
		{
			$return[ 'previous' ] = url( "talent/topnew?". http_build_query( [ "limit" => $limit, "page" => $page - 1 ] ) );
		}

		$response = Response::make( $return, 200 );
		$response->header( 'X-Total-Count', count($count) );
		return $response;
	}


	public function topnew_v2()
	{
	    $client = getS3Client();
	    $return = [ ];

	    $token = Request::header( "X-API-TOKEN" );

	    $session = $this->token->get_session( $token );
	    $currentUser = User::find( $session->token_user_id );

	    $geoFilter = $currentUser->getContinentFilter();
	    if( $geoFilter )
	    {
	        if( \Config::get( 'app.force_include_all_world', false ) ) $geoFilter[] = 0;
	    }

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

	    $countQuery = DB::table('users')
	    ->select('users.user_id','users.user_name','users.user_display_name','users.user_facebook_id','users.user_google_id','users.user_twitter_id','users.user_profile_image','users.user_cover_image','users.user_rank')
	    ->join('entries', 'entries.entry_user_id', '=', 'users.user_id')
	    ->join('entry_files', 'entry_files.entry_file_entry_id', '=', 'entries.entry_id')
	    ->where('entries.entry_deleted', '=', '0')
	    ->where('entry_files.entry_file_deleted', '=', '0')
	    ->where('users.user_deleted', '=', '0');
	    if( $geoFilter )
	    {
	        $countQuery = $countQuery->whereIn( 'users.user_continent', $geoFilter );
	    }
	    $countQuery = $countQuery->where(function($query)
	    {
	        $query->where('users.user_rank', '!=', 0);
	    })
	    ->groupBy( 'users.user_id' )
	    ->orderBy( 'user_rank', 'asc' );
	    $count = $countQuery->get();

	    $entriesQuery = DB::table('users')
	    ->select('users.user_id','users.user_name','users.user_display_name','users.user_email','users.user_facebook_id','users.user_google_id','users.user_twitter_id','users.user_profile_image','users.user_cover_image','users.user_rank')
	    ->join('entries', 'entries.entry_user_id', '=', 'users.user_id')
	    ->join('entry_files', 'entry_files.entry_file_entry_id', '=', 'entries.entry_id')
	    ->where('entries.entry_deleted', '=', '0')
	    ->where('entry_files.entry_file_deleted', '=', '0')
	    ->where('users.user_deleted', '=', '0');
	    if( $geoFilter )
	    {
	        $entriesQuery = $entriesQuery->whereIn( 'users.user_continent', $geoFilter );
	    }
	    $entriesQuery = $entriesQuery->where(function($query)
	    {
	        $query->where('users.user_rank', '!=', 0);
	    })
	    ->groupBy( 'users.user_id' )
	    ->orderBy( 'user_rank', 'asc' );
	    $entries = $entriesQuery->take( $limit )->skip( $offset )->get();

	    //If the count is greater than the highest number of items displayed show a next link
	    if(count($count)==0)
	    {
	        $return = [ 'error' => 'No Top Talent Found' ];
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

	    $users = [ ];
	    $return[ 'talents' ] = [];

	    foreach( $entries as $entry )
	    {
	        if( ( $entry->user_display_name == '' ) || ( is_null( $entry->user_name ) ) || ( is_null( $entry->user_email ) ) )
	        {
	            if( $entry->user_facebook_id != 0 )
	            {
	                $displayName = DB::table( 'facebook_users' )->where( 'facebook_user_id', '=', $entry->user_facebook_id )->pluck( 'facebook_user_display_name' );
	            }
	            elseif( $entry->user_twitter_id != 0 )
	            {
	                $displayName = DB::table( 'twitter_users' )->where( 'twitter_user_id', '=', $entry->user_twitter_id )->pluck( 'twitter_user_display_name' );
	            }
	            elseif( $entry->user_google_id != 0 )
	            {
	                $displayName = DB::table( 'google_users' )->where( 'google_user_id', '=', $entry->user_google_id )->pluck( 'google_user_display_name' );
	            }
	        }
	        else
	        {
	            $displayName = $entry->user_display_name;
	        }

	        $return[ 'talents' ][ ][ 'talent' ] = [ 'id' => $entry->user_id,
	            'userName'     => @$displayName,
	            'displayName'  => @$displayName,
	            'profileImage' => ( isset( $entry->user_profile_image ) )
	            ? $client->getObjectUrl( Config::get('app.bucket'), $entry->user_profile_image, '+60 minutes' )
	            : '',
	            'profileCover' => ( isset( $entry->user_cover_image ) )
	            ? $client->getObjectUrl( Config::get('app.bucket'), $entry->user_cover_image, '+60 minutes' ) : '',
	            'rank'     => $entry->user_rank
	        ];

	    }
	    if( $next )
	    {
	        $return[ 'next' ] = url( "talent/topnew?". http_build_query( [ "limit" => $limit, "page" => $page + 1 ] ) );
	    }

	    if( $previous )
	    {
	        $return[ 'previous' ] = url( "talent/topnew?". http_build_query( [ "limit" => $limit, "page" => $page - 1 ] ) );
	    }

	    $response = Response::make( $return, 200 );
	    $response->header( 'X-Total-Count', count($count) );
	    return $response;
	}
}
