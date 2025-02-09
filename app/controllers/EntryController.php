<?php

use MobStar\Storage\Entry\EntryRepository as Entry;
use MobStar\Storage\Token\TokenRepository as Token;
use Swagger\Annotations as SWG;
use Aws\S3\S3Client;
use Aws\Common\Credentials\Credentials as Creds;
use MobStar\UserHelper;
use MobStar\EntryHelper;
use MobStar\ResponseHelper;
use MobStar\EntriesResponseHelper;
use MobStar\SnsHelper;
use MobStar\YoutubeHelper;

/**
 * @package
 * @category
 * @subpackage
 *
 * @SWG\Resource(
 *  apiVersion=0.2,
 *  swaggerVersion=1.2,
 *  resourcePath="/entry",
 *  basePath="http://api.mobstar.com"
 * )
 */
class EntryController extends BaseController
{

	public $valid_fields = [ "id", "userId", "category", "type", "name", "description", "created", "modified", "tags", "entryFiles", "upVotes", "downVotes", "rank", "language", 'userName' ];

	public function __construct( Entry $entry, Token $token )
	{
		$this->entry = $entry;
		$this->token = $token;
	}
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */

	/**
	 *
	 * @SWG\Api(
	 *   path="/entry",
	 *   description="Operations about Entries",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="Get all entries",
	 *       notes="Returns all available entries",
	 *       nickname="getAllEntries",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="fields",
	 *           description="Accepted values for the fields parameter are: id, userId, groupName, category, type, name, description, created, modified, tags, entryFiles, upVotes, downVotes, rank, language.",
	 *           paramType="query",
	 *           required=false,
	 *           type="comma seperated list"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="user",
	 *           description="User ID whose entries you want to view.",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="category",
	 *           description="Category ID of entries you want to view.",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="tagId",
	 *           description="Tag ID of entries you want to view.",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="orderBy",
	 *           description="Order to display entries in.",
	 *           paramType="query",
	 *           required=false,
	 *           type="string",
	 *             enum="['latest','popular']"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="language",
	 *           description="Filter categories by language.",
	 *           paramType="query",
	 *           required=false,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="excludeVotes",
	 *           description="Exclude entries the user has already voted on.",
	 *           paramType="query",
	 *           required=false,
	 *           type="string",
	 *           enum="['true', 'false']"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="showFeedback",
	 *           description="Show feedback boolean - 1 for true, 0 for false",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="page",
	 *           description="Page of results you want to view.",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="limit",
	 *           description="Maximum number of representations in response.",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer",
	 *         )
	 *       ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=404,
	 *            message="Entry not found"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */
	public function index()
	{
		$client = getS3Client();

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		if( $session->token_app_version >= 2 )
		{
		    return $this->index_v2();
		}

		$fields = array_values( explode( ',', Input::get( "fields" ) ) );

		if( $fields[ 0 ] == "" )
		{
			unset( $fields );
		}

		$errors = array();
		$valid = false;

		if( !empty( $fields ) )
		{
			//Check if fields are valid
			foreach( $fields as $field )
			{
				if( !in_array( $field, $this->valid_fields ) )
				{
					$errors[] = [ $field . " is not a valid field." ];
				}
				else
				{
					$valid = true;
				}
			}

		}

		//Get limit to calculate pagination
		$limit = ( Input::get( 'limit', '20' ) );

		//If not numeric set it to the default limit
		$limit = ( !is_numeric( $limit ) || $limit < 1 ) ? 20 : $limit;

		//Get page
		$page = ( Input::get( 'page', '1' ) );
		$page = ( !is_numeric( $page ) ) ? 1 : $page;

		//Get page
		$order_by = ( Input::get( 'orderBy', 'id' ) );

		$debug = false;

		switch( $order_by )
		{
			case "popular":
				$order = 'entry_rank';
				$dir = 'asc';
				break;
			case "latest":
				$order = 'entry_created_date';
				$dir = 'desc';
				break;
			default:
				$order = 0;
				$dir = 0;
		}

		//Calculate offset
		$offset = ( $page * $limit ) - $limit;

		//Get user
		$user = ( Input::get( 'user', '0' ) );
		$user = ( !is_numeric( $user ) ) ? 0 : $user;

		//Get Category
		$category = ( Input::get( 'category', '0' ) );
		$category = ( !is_numeric( $category ) ) ? 0 : $category;

		$showFeedback = ( Input::get( 'showFeedback', '0' ) );

		//Get tags
		$tag = ( Input::get( 'tagId', '0' ) );
		$tag = ( !is_numeric( $tag ) ) ? 0 : $tag;

		$exclude = [ ];

		if( Input::get( 'excludeVotes' ) == 'true' )
		{
		  // skip entries, voted down by user
		  $exclude['excludeVotes'] = $session->token_user_id;
		}
		if( $order_by == 'popular' )
		{
		  // skip not popular entries
		  $exclude['notPopular'] = true;
		}
		/* Added for exclude MOBIT category in All entry list */
		if($category != 8 && $category != 7)
		{
		  $exclude['category'] = array(7, 8);
		}
		if( $session->token_app_version < 4 )
		{
		    $exclude['entryType'] = 'video_youtube';
		}
		/* End */
		$entries = $this->entry->allComplexExclude( $user, $category, $tag, $exclude, $order, $dir, $limit, $offset, false, true );
		//dd(DB::getQueryLog());
		$count = $this->entry->allComplexExclude( $user, $category, $tag, $exclude, $order, $dir, $limit, $offset, true );

		$params = array(
		    'userId' => $user,
		    'totalCount' => $count,
		    'url' => 'index.php/entry/?',
		    'limit' => $limit,
		    'page' => $page,
		    'debug' => $debug,
		    'errors' => $errors,
		);
		//check to see if fields were specified and at least one is valid
		if ( ( !empty( $fields ) ) && $valid )
		    $params['fields'] = $fields;

		$data = EntriesResponseHelper::getForIndex(
		    $entries,
		    $session->token_user_id,
		    $showFeedback,
		    $params
		);
		$status_code = $data['code'];
		$return = $data['data'];

		$response = Response::make( $return, $status_code );

		$response->header( 'X-Total-Count', $count );

		return $response;
	}


	/**
	 * returns list of entires using geoLocation and category filter
	 * @return unknown
	 */
	public function index_v2()
	{
	    $client = getS3Client();

	    $token = Request::header( "X-API-TOKEN" );

	    $session = $this->token->get_session( $token );

	    $currentUser = User::findOrFail( $session->token_user_id );

	    $fields = array_values( explode( ',', Input::get( "fields" ) ) );

	    if( $fields[ 0 ] == "" )
	    {
	        unset( $fields );
	    }

	    $errors = array();
	    $valid = false;

	    if( !empty( $fields ) )
	    {
	        //Check if fields are valid
	        foreach( $fields as $field )
	        {
	            if( !in_array( $field, $this->valid_fields ) )
	            {
	                $errors[] = [ $field . " is not a valid field." ];
	            }
	            else
	            {
	                $valid = true;
	            }
	        }

	    }

	    //Get limit to calculate pagination
	    $limit = ( Input::get( 'limit', '20' ) );

	    //If not numeric set it to the default limit
	    $limit = ( !is_numeric( $limit ) || $limit < 1 ) ? 20 : $limit;

	    //Get page
	    $page = ( Input::get( 'page', '1' ) );
	    $page = ( !is_numeric( $page ) ) ? 1 : $page;

	    //Get page
	    $order_by = ( Input::get( 'orderBy', 'id' ) );

	    $debug = false;

	    switch( $order_by )
	    {
	        case "popular":
	            $order = 'entry_rank';
	            $dir = 'asc';
	            break;
	        case "latest":
	            $order = 'entry_created_date';
	            $dir = 'desc';
	            break;
	        default:
	            $order = 0;
	            $dir = 0;
	    }

	    //Calculate offset
	    $offset = ( $page * $limit ) - $limit;

	    //Get user
	    $user = ( Input::get( 'user', '0' ) );
	    $user = ( !is_numeric( $user ) ) ? 0 : $user;

	    //Get Category
	    $category = ( Input::get( 'category', '0' ) );
	    $category = ( !is_numeric( $category ) ) ? 0 : $category;

	    $showFeedback = ( Input::get( 'showFeedback', '0' ) );

	    //Get tags
	    $tag = ( Input::get( 'tagId', '0' ) );
	    $tag = ( !is_numeric( $tag ) ) ? 0 : $tag;

	    $exclude = [ ];

	    if( Input::get( 'excludeVotes' ) == 'true' )
	    {
	        // skip entries, voted down by user
	        $exclude['excludeVotes'] = $session->token_user_id;
	    }
	    if( $order_by == 'popular' )
	    {
	        // skip not popular entries
	        $exclude['notPopular'] = true;
	    }
	    /* Added for exclude MOBIT category in All entry list */
	    if($category != 8 && $category != 7)
	    {
	        $exclude['category'] = array(7, 8);
	    }
	    if( $session->token_app_version < 4 ) // skip youtube entries
	    {
	        $exclude['entryType'] = array( 'video_youtube' );
	    }
	    /* End */
	    if( ! $user ) // use geoLocation filtering (it is query for main feed)
	    {
	        $geoFilter = $currentUser->getContinentFilter();
	        $categoryFilter = $currentUser->getCategoryFilter();
	        if( $category ) // users asks some category (in some way), so ignore user's categoryFilter (replace it)
	        {
	            $categoryFilter = array( $category );
	        }
	        $entries = $this->entry->allWithFilters( $geoFilter, $categoryFilter, $tag, $exclude, $order, $dir, $limit, $offset, false, true );
	        $count = $this->entry->allWithFilters( $geoFilter, $categoryFilter, $tag, $exclude, $order, $dir, $limit, $offset, true );
	    }
	    else // skip geoLocation filtering. User want to see all other users entries
	    {
            $entries = $this->entry->allComplexExclude( $user, $category, $tag, $exclude, $order, $dir, $limit, $offset, false, true );
            //dd(DB::getQueryLog());
            $count = $this->entry->allComplexExclude( $user, $category, $tag, $exclude, $order, $dir, $limit, $offset, true );
	    }

	    $params = array(
	        'userId' => $user,
	        'totalCount' => $count,
	        'url' => 'index.php/entry/?',
	        'limit' => $limit,
	        'page' => $page,
	        'debug' => $debug,
	        'errors' => $errors,
	    );
	    //check to see if fields were specified and at least one is valid
	    if ( ( !empty( $fields ) ) && $valid )
	        $params['fields'] = $fields;

	    $data = EntriesResponseHelper::getForIndex(
	        $entries,
	        $session->token_user_id,
	        $showFeedback,
	        $params
	    );
	    $status_code = $data['code'];
	    $return = $data['data'];

	    $response = Response::make( $return, $status_code );

	    $response->header( 'X-Total-Count', $count );

	    return $response;
	}



	/**
	 *
	 * @SWG\Api(
	 *   path="/entry/{entryIds}",
	 *   description="Operation about Entries",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="Return specified entries",
	 *       notes="Returns entries requested. API-Token is required for this method.",
	 *       nickname="getEntryById",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="entryIds",
	 *           description="Entry ID/IDs you want returned.",
	 *           paramType="path",
	 *           required=true,
	 *           type="comma seperated list"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="fields",
	 *           description="Accepted values for the fields parameter are: id, userId, groupName, category, type, name, description, created, modified, tags, entryFiles, upVotes, downVotes, rank, language.",
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
	 *
	 *         @SWG\Parameter(
	 *           name="showFeedback",
	 *           description="Show feedback boolean - 1 for true, 0 for false",
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
	 *            message="Entry not found"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function show( $id_commas )
	{

		$client = getS3Client();

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$id = array_values( explode( ',', $id_commas ) );

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

		//Get user
		$user = ( Input::get( 'user', '0' ) );
		$user = ( !is_numeric( $user ) ) ? 0 : $user;

		//Get Category
		$category = ( Input::get( 'category', '0' ) );
		$category = ( !is_numeric( $category ) ) ? 0 : $category;

		$showFeedback = ( Input::get( 'showFeedback', '0' ) );

		$entries = $this->entry->whereIn( $id, $user, $category, $limit, $offset, false );

		$count = $this->entry->whereIn( $id, $user, $category, $limit, $offset, true );

		if( $count == 0 )
		{
			$return = [ 'error' => 'No Entries Found' ];
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

		foreach( $entries as $entry )
		{

			$current = array();

			$up_votes = 0;
			$down_votes = 0;
			foreach( $entry->vote as $vote )
			{
				if( $vote->vote_up == 1 && $vote->vote_deleted == 0 )
				{
					$up_votes++;
				}
				elseif( $vote->vote_down == 1 && $vote->vote_deleted == 0 )
				{
					$down_votes++;
				}

			}

			//check to see if fields were specified and at least one is valid
			if( ( !empty( $fields ) ) && $valid )
			{

				if( in_array( "id", $fields ) )
				{
					$current[ 'id' ] = $entry->entry_id;
				}

				if( in_array( "userId", $fields ) )
				{
					$current[ 'userId' ] = $entry->entry_user_id;
				}

				if( in_array( "category", $fields ) )
				{
					$current[ 'category' ] = $entry->category->category_name;
				}

				if( in_array( "user", $fields ) )
				{
					$current[ 'user' ][ 'userId' ] = $entry->entry_user_id;
					$current[ 'user' ][ 'userName' ] = $entry->User->user_name;
					$current[ 'user' ][ 'displayName' ] = $entry->User->user_display_name;
					$current[ 'user' ][ 'email' ] = $entry->User->user_email;
					$current[ 'user' ][ 'profileImage' ] = ( !empty( $entry->User->user_profile_image ) )
						? "http://" . $_ENV[ 'URL' ] . "/" . $entry->User->user_profile_cover : "";
					$current[ 'user' ][ 'profileCover' ] = ( !empty( $entry->User->user_profile_cover ) )
						? "http://" . $_ENV[ 'URL' ] . "/" . $entry->User->user_profile_cover : "";
					$current[ 'user' ][ 'isMyStar' ] = Star::where( 'user_star_user_id', '=', $session->token_user_id )->where( 'user_star_star_id', '=', $entry->entry_user_id )->where( 'user_star_deleted', '=', '0')->count();
					$iAmStarFlag = Star::where( 'user_star_user_id', '=', $entry->entry_user_id )->where( 'user_star_star_id', '=', $session->token_user_id )->where( 'user_star_deleted', '=', '0')->count();
					if($iAmStarFlag > 0)
					{
						$current[ 'user' ][ 'iAmStar' ] = 1;
					}
					else
					{
						$current[ 'user' ][ 'iAmStar' ] = 0;
					}
				}

				if( in_array( "type", $fields ) )
				{
					$current[ 'type' ] = $entry->entry_type;
				}

				if( in_array( "name", $fields ) )
				{
					$current[ 'name' ] = $entry->entry_name;
				}

				if( in_array( "description", $fields ) )
				{
					$current[ 'description' ] = $entry->entry_description;
				}

				if( in_array( "created", $fields ) )
				{
					$current[ 'created' ] = $entry->entry_created_date;
				}

				if( in_array( "modified", $fields ) )
				{
					$current[ 'modified' ] = $entry->entry_modified_date;
				}

				if( in_array( "tags", $fields ) )
				{
					$current[ 'tags' ] = array();
					foreach( $entry->entryTag as $tag )
					{
						$current[ 'tags' ][ ] = Tag::find( $tag->entry_tag_tag_id )->tag_name;
					}
				}

				if( in_array( "entryFiles", $fields ) )
				{
					$current[ 'entryFiles' ] = array();
					if( ! ResponseHelper::isEntryFilesValid( $entry, $entry->file ) )
					{
					    continue;
					}
					foreach( $entry->file as $file )
					{
					    $current['entryFiles'][] = ResponseHelper::entryFile( $file );
					}
					$current['videoThumb'] = ResponseHelper::entryThumb( $entry, $entry->file );
				}

				if( in_array( "upVotes", $fields ) )
				{
					$current[ 'upVotes' ] = $up_votes;
				}

				if( in_array( "upVotes", $fields ) )
				{
					$current[ 'downVotes' ] = $down_votes;
				}

				if( in_array( "rank", $fields ) )
				{
					$current[ 'rank' ] = $entry->entry_rank;
				}

				if( in_array( "language", $fields ) )
				{
					$current[ 'language' ] = $entry->entry_language;
				}

				if( $entry->entry_deleted )
				{
					$current[ 'deleted' ] = true;
				}
				else
				{
					$current[ 'deleted' ] = false;
				}

				$return[ 'entries' ][ ][ 'entry' ] = $current;
			}

			else
			{

				$current[ 'id' ] = $entry->entry_id;
				$current[ 'category' ] = $entry->category->category_name;
				if( isset( $entry->entry_category_id )  && $entry->entry_category_id == 3 )
				{
					$current[ 'subcategory' ] = $entry->entry_subcategory;
					$current[ 'age' ] = $entry->entry_age;
					$current[ 'height' ] = $entry->entry_height;
				}
				$current[ 'type' ] = $entry->entry_type;
				$current[ 'user' ] = oneUser( $entry->User, $session , true);
//
//				$current[ 'user' ][ 'userId' ] = $entry->entry_user_id;
//				$current[ 'user' ][ 'userName' ] = $entry->User->user_name;
//				$current[ 'user' ][ 'displayName' ] = $entry->User->user_display_name;
//				$current[ 'user' ][ 'email' ] = $entry->User->user_email;
//				$current[ 'user' ][ 'profileImage' ] = ( !empty( $entry->User->user_profile_image ) )
//					? "http://" . $_ENV[ 'URL' ] . "/" . $entry->User->user_profile_cover : "";
//				$current[ 'user' ][ 'profileCover' ] = ( !empty( $entry->User->user_profile_cover ) )
//					? "http://" . $_ENV[ 'URL' ] . "/" . $entry->User->user_profile_cover : "";
//				$current[ 'user' ][ 'isMyStar' ] = Star::where( 'user_star_user_id', '=', $session->user_id )->where( 'user_star_star_id', '=', $entry->entry_user_id )->count();

				$current[ 'name' ] = $entry->entry_name;
				$current[ 'description' ] = $entry->entry_description;
				$current[ 'created' ] = $entry->entry_created_date;
				$current[ 'modified' ] = $entry->entry_modified_date;

				$current[ 'tags' ] = array();
				foreach( $entry->entryTag as $tag )
				{
					$current[ 'tags' ][ ] = Tag::find( $tag->entry_tag_tag_id )->tag_name;
				}

				//break;

				$current[ 'entryFiles' ] = array();
				if( ! ResponseHelper::isEntryFilesValid( $entry, $entry->file ) )
				{
				    continue;
				}
				foreach( $entry->file as $file )
				{
				    $current['entryFiles'][] = ResponseHelper::entryFile( $file );
				}
				$current['videoThumb'] = ResponseHelper::entryThumb( $entry, $entry->file );
				if( $showFeedback == 1 )
				{
					$currentFeedback = [ ];

					foreach( $entry->comments as $comment )
					{
						$currentFeedback[ ] = [
							'comment'        => $comment->comment_content,
							'commentDate'    => $comment->comment_added_date,
							'commentDeleted' => (bool)$comment->comment_deleted ];
					}
					$current[ 'feedback' ] = $currentFeedback;
				}

				$current[ 'upVotes' ] = $up_votes;
				$current[ 'downVotes' ] = $down_votes;
				$current[ 'rank' ] = $entry->entry_rank;
				$current[ 'language' ] = $entry->entry_language;

				$current[ 'totalComments' ] = $entry->comments->count();
				$current[ 'totalviews' ] = $entry->viewsTotal();
				if( $entry->entry_deleted )
				{
					$current[ 'deleted' ] = true;
				}
				else
				{
					$current[ 'deleted' ] = false;
				}

				$return[ 'entries' ][ ][ 'entry' ] = $current;
			}
		}

		$status_code = 200;

		//If next is true create next page link
		if( $next )
		{
			$return[ 'next' ] = "http://".$_ENV['URL']."/entry/" . $id_commas . "?" . http_build_query( [ "limit" => $limit, "page" => $page + 1 ] );
		}

		if( $previous )
		{
			$return[ 'previous' ] = "http://".$_ENV['URL']."/entry/" . $id_commas . "?" . http_build_query( [ "limit" => $limit, "page" => $page - 1 ] );
		}

		$response = Response::make( $return, $status_code );

		$response->header( 'X-Total-Count', $count );

		return $response;
	}


	public function info( $entryId )
	{
	    $token = Request::header( "X-API-TOKEN" );

	    $session = $this->token->get_session( $token );

	    $response = EntriesResponseHelper::getEntryInfo( $entryId, $session->token_user_id );

	    return Response::make( $response['data'], $response['code'] );
	}


	/**
	 *
	 * @SWG\Api(
	 *   path="/entry/",
	 *   description="Operation about entries",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Add new entry",
	 *       notes="Submits a new entry.",
	 *       nickname="addEntry",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="category",
	 *           description="Category ID the entry is submitted to.",
	 *           paramType="form",
	 *           required=true,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="name",
	 *           description="Name to be displayed for the entry",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="language",
	 *           description="Language of entry",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="type",
	 *           description="The main file for the entry.",
	 *           paramType="form",
	 *           required=true,
	 *           type="string",
	 *             enum="['audio','video','image']"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="description",
	 *           description="Brief accompanying text for entry.",
	 *           paramType="form",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="tags",
	 *           description="Comma seperated list of tags.",
	 *           paramType="form",
	 *           required=false,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="file1",
	 *           description="Primary file for entry.",
	 *           paramType="form",
	 *           required=true,
	 *           type="file"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="file2",
	 *           description="Secondary file for entry, i.e. accompanying image for audio.",
	 *           paramType="form",
	 *           type="file",
	 *           required=false
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

	public function store()
	{

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$tmp_filenames = array(); // array of temporary created files. we need to delete them before return

		//Validate Input
		$rules = array(
			'category'    => 'required|numeric',
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
		elseif( Input::get( 'type', '' ) == 'video_youtube' )
		{
		    return $this->storeYoutubeEntry();
		}
		else
		{
			$file = Input::file( 'file1' );

			if( !empty( $file ) )
			{

				//Will need to do some work here to upload file to CDN

				//Get input
				$entry_by_default_enable = DB::table( 'settings' )->where( 'vUniqueName', '=', 'ENTRY_DEFAULT' )->pluck( 'vSettingValue' );
				$categoryId = DB::table( 'categories' )->where( 'category_id', '=', Input::get( 'category' ) )->pluck( 'category_id' );
				if(empty($categoryId))
				{
					// @todo currently, if no catagory provided, it sets category to 1, saves entry and returns error and created entry_id
					$response[ 'error' ] = "No category selected";
					$status_code = 400;
				}

				/* Commented for one Demo as on request 07 March 2015 // Removed Comment on 13 March 2015 as on request */
				if( Input::get( 'category' ) == 7 || Input::get( 'category' ) == 8 )
				{
					$entry_deleted = 0;
				}
				else
				{
					if($entry_by_default_enable && $entry_by_default_enable === 'TRUE')
					$entry_deleted = 0;
					else
					$entry_deleted = 1;
				}
				if( Input::get( 'category' ) == 3 )
				{
					$input = [
						'entry_user_id'      => $session->token_user_id,
						'entry_category_id'  => Input::get( 'category' ),
						'entry_type'         => Input::get( 'type' ),
						//'entry_name'         => preg_replace('/\\\\/', '', Input::get( 'name' )),
						//'entry_language'     => preg_replace('/\\\\/', '', Input::get( 'language' )),
						//'entry_description'  => preg_replace('/\\\\/', '', Input::get( 'description' )),
						'entry_name'         => str_replace('"', '', Input::get( 'name' )),
						'entry_language'     => str_replace('"', '', Input::get( 'language' )),
						'entry_description'  => str_replace('"', '', Input::get( 'description' )),
						'entry_created_date' => date( 'Y-m-d H:i:s' ),
						'entry_deleted'      => $entry_deleted,
						'entry_subcategory'  => Input::get( 'subCategory' ),
						'entry_age'          => Input::get( 'age' ),
						'entry_height'       => Input::get( 'height' ),
					];
				}
				else
				{
					$input = [
						'entry_user_id'      => $session->token_user_id,
						'entry_category_id'  => (Input::get( 'category' ) > 0) ? Input::get( 'category' ) : 1,
						'entry_type'         => Input::get( 'type' ),
						'entry_name'         => str_replace('"', '', Input::get( 'name' )),
						'entry_language'     => str_replace('"', '', Input::get( 'language' )),
						'entry_description'  => str_replace('"', '', Input::get( 'description' )),
						'entry_created_date' => date( 'Y-m-d H:i:s' ),
						'entry_deleted'      => $entry_deleted,
						'entry_subcategory'  => '',
						'entry_age'      	 => '',
						'entry_height'       => '',
					];
				}
				if( empty( $input['entry_subcategory'] ) ) unset( $input['entry_subcategory'] );
				if( empty( $input['entry_age'] ) ) unset( $input['entry_age'] );
				if( Input::get( 'splitVideoId', false ) ) $input['entry_splitVideoId'] = (int)Input::get( 'splitVideoId' );
				// set entry continent based on user continent
				$user = \User::findOrFail( $session->token_user_id );
				$input['entry_continent'] = $user->user_continent;
				unset( $user );
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
						$this->entry->addTag( str_replace('"', '', $tag), $response[ 'entry_id' ], $session->token_user_id );
						//$this->entry->addTag( trim(preg_replace('/\\\\/', '', $tag)), $response[ 'entry_id' ], $session->token_user_id );
						//$this->entry->addTag( trim( $tag ), $response[ 'entry_id' ], $session->token_user_id );
					}
				}

				$dest = 'uploads';

				// @todo check that name not exists
				$filename = str_random( 12 );

				$extension = $file->getClientOriginalExtension();
				$originalextension = $file->getClientOriginalExtension();

//old method was just to move the file, and not encode it
//				$file->move($dest, $filename . '.' . $extension);

				if( $input[ 'entry_type' ] == 'audio' )
				{
					$file_in = $file->getRealPath();
					$file_out = Config::get('app.home') . 'public/uploads/' . $filename . '.mp3';

					// Transcode Audio
					shell_exec( Config::get( 'app.bin_ffmpeg' ).' -i ' . $file_in . ' -strict -2 ' . $file_out );

					$tmp_filenames['result'] = $file_out;

					// Get media duration
					$duration = getMediaDurationInSec( $file_out );
					// and save it
					$entryToUpdate = \Entry::findOrFail( $response['entry_id'] );
					$entryToUpdate->entry_duration = $duration ? $duration : -1;
					$entryToUpdate->save();
					unset( $entryToUpdate );

					$extension = 'mp3';

					Flysystem::connection( 'awss3' )->put( $filename . "." . $extension, file_get_contents( $file_out ) );
				}
				else
				{
					if( $input[ 'entry_type' ] == 'video' )
					{

						$file_in = $file->getRealPath();
						$file_out = Config::get('app.home') . 'public/uploads/' . $filename . '.mp4';
						$file_out_scale = Config::get('app.home') . 'public/uploads/' . $filename . '-scale.mp4';

						// Transcode Video
						if($session->token_user_id == 307 || $session->token_user_id == 302)
						{
							$log_filename = Config::get('app.home') . 'public/uploads/' . $filename . '-log.txt';
							shell_exec( Config::get( 'app.bin_ffmpeg' ).' -i ' . $file_in . ' -strict -2 ' . $file_out . ' 2>' . $log_filename );

							$tmp_filenames['result'] = $file_out;
							$tmp_filenames['log'] = $log_filename;

							// Get media duration
							$duration = getMediaDurationInSec( $file_out );
							// and save it
							$entryToUpdate = \Entry::findOrFail( $response['entry_id'] );
							$entryToUpdate->entry_duration = $duration ? $duration : -1;
							$entryToUpdate->save();
							unset( $entryToUpdate );

							$fileMoved = $file->move( Config::get('app.home') . 'public/uploads/', $filename . '-uploaded.' . $extension );

							$tmp_filenames['uploaded'] = $fileMoved->getPathname();

							$extension = 'mp4';
							Flysystem::connection( 'awss3' )->put( $filename . "." . $extension, file_get_contents( $file_out ) );

							$thumb = Config::get('app.home') . 'public/uploads/' . $filename . '-thumb.jpg';

							$videoInfo = getMediaInfo( $file_out );
							makeVideoThumbnail( $file_out, $thumb, $videoInfo );
							$tmp_filenames['thumb'] = $thumb;

							$rotation_angel = empty( $videoInfo['rotate'] ) ? '' : $videoInfo['rotate'];

							Flysystem::connection( 'awss3' )->put( "thumbs/" . $filename . "-thumb.jpg", file_get_contents( $thumb ) );
							/* Added By AJ on 09-Jul-2015 for youtube and water mark */
							if( Input::get( 'category' ) != 7 && Input::get( 'category' ) != 8 )
							{
								$pathfile = Config::get( 'app.home' ).'/public/uploads/'. $filename . '-uploaded.' . $originalextension;
								$serviceDetails = array();
								$serviceDetails["pathfile"] = $pathfile;
								$serviceDetails["entry_id"] = $response[ 'entry_id' ];
								$serviceDetails["rotation_angel"] = $rotation_angel;
								$serviceDetails["name"] = Input::get( 'name' );
								$serviceDetails["description"] = Input::get( 'description' );
								$serviceDetails["category"] = Input::get( 'category' );

								$this->backgroundPost('http://'.$_ENV['URL'].'/entry/youtubeUpload?jsonData='.urlencode(json_encode($serviceDetails)));
							}
							/* End */
						}
						else
						{
							$log_filename = Config::get('app.home') . 'public/uploads/' . $filename . '-log.txt';
							shell_exec( Config::get( 'app.bin_ffmpeg' ).' -i ' . $file_in . ' -vf scale=306:306 -strict -2 ' . $file_out . ' 2>' . $log_filename );

							$tmp_filenames['result'] = $file_out;
							$tmp_filenames['log'] = $log_filename;

							// Get media duration
							$duration = getMediaDurationInSec( $file_out );
							// and save it
							$entryToUpdate = \Entry::findOrFail( $response['entry_id'] );
							$entryToUpdate->entry_duration = $duration ? $duration : -1;
							$entryToUpdate->save();
							unset( $entryToUpdate );

							$fileMoved = $file->move( Config::get('app.home') . 'public/uploads/', $filename . '-uploaded.' . $extension );

							$tmp_filenames['uploaded'] = $fileMoved->getPathname();

							$extension = 'mp4';
							Flysystem::connection( 'awss3' )->put( $filename . "." . $extension, file_get_contents( $file_out ) );

							$thumb = Config::get('app.home') . 'public/uploads/' . $filename . '-thumb.jpg';

							$videoInfo = getMediaInfo( $file_out );
							makeVideoThumbnail( $file_out, $thumb, $videoInfo );
							$tmp_filenames['thumb'] = $thumb;

							$rotation_angel = empty( $videoInfo['rotate'] ) ? '' : $videoInfo['rotate'];

							Flysystem::connection( 'awss3' )->put( "thumbs/" . $filename . "-thumb.jpg", file_get_contents( $thumb ) );
							/* Added By AJ on 09-Jul-2015 for youtube and water mark */
							if( Input::get( 'category' ) != 7 && Input::get( 'category' ) != 8 )
							{
								$pathfile = Config::get( 'app.home' ).'/public/uploads/'. $filename . '-uploaded.' . $originalextension;
								$serviceDetails = array();
								$serviceDetails["pathfile"] = $pathfile;
								$serviceDetails["entry_id"] = $response[ 'entry_id' ];
								$serviceDetails["rotation_angel"] = $rotation_angel;
								$serviceDetails["name"] = Input::get( 'name' );
								$serviceDetails["description"] = Input::get( 'description' );
								$serviceDetails["category"] = Input::get( 'category' );

								$this->backgroundPost('http://'.$_ENV['URL'].'/entry/youtubeUpload?jsonData='.urlencode(json_encode($serviceDetails)));
							}
							/* End */
						}
					}
					else
					{
						//File is an image

						$file_in = $file->getRealPath();

						$file_out = Config::get('app.home') . "public/uploads/" . $filename . '.' . $extension;

						$image = Image::make( $file_in );

						$image->widen( 350 );

						$image->save( $file_out );

						$tmp_filenames['result'] = $file_out;

						Flysystem::connection( 'awss3' )->put( $filename . "." . $extension, file_get_contents( $file_out ) );
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
									   'entry_file_size' => filesize( $file_out ),
								   ] );

				Eloquent::reguard();

				$file = Input::file( 'file2' );

				if( !empty( $file ) && $file->isValid() )
				{
					$dest = 'uploads';

					$file_in = $file->getRealPath();

					$extension = ".jpg";

					$file_out = Config::get('app.home') . "public/uploads/" . $filename . '.' . $extension;

					$image = Image::make( $file_in );

					$image->widen( 350 );

					$image->save( $file_out, 80 );

					$tmp_filenames['file2'] = $file_out;

					Flysystem::connection( 'awss3' )->put( $filename . "." . $extension,  file_get_contents( $file_out ) );



					Eloquent::unguard();

					EntryFile::create( [
										   'entry_file_name'         => $filename,
										   'entry_file_entry_id'     => $response[ 'entry_id' ],
										   'entry_file_location'     => $dest,
										   'entry_file_type'         => $extension,
										   'entry_file_created_date' => date( 'Y-m-d H:i:s' ),
										   'entry_file_updated_date' => date( 'Y-m-d H:i:s' ),
										   'entry_file_size' => filesize( $file_out ),
									   ] );

					Eloquent::reguard();
				}

				// is entry is split video, send notifications
				if( Input::get( 'splitVideoId', false ) )
				{
				  $this->processSplitVideoNotifications(
				    $session->token_user_id,
				    \Entry::findOrFail( $response['entry_id'] ),
				    Input::get( 'splitVideoId' )
				  );
				}

				// send notification about new unloaded entry
				$newEntry = \Entry::findOrFail( $response['entry_id'] );
				if( ($newEntry->entry_category_id != 7) AND ($newEntry->entry_category_id != 8) ) { // no notification on profile entries
				    $messageData = array(
				        'pushType' => 'newEntry',
				        'entries' =>  array(
				            array(
				                'id' => $newEntry->entry_id,
				                'userId' => $newEntry->entry_user_id,
				                'timeUpload' => \DateTime::createFromFormat( 'Y-m-d H:i:s', $newEntry->entry_created_date )->getTimestamp() * 1000,
				                'category' => $newEntry->entry_category_id,
				                'continent' => $newEntry->entry_continent,
				            )
				        )
				    );
				    SnsHelper::sendBroadcast( 'New entry uploaded', $messageData );
				}
			}
			else
			{

				$response[ 'error' ] = "No file included";
				$status_code = 400;
			}

		}

		// delete uploaded files
		if( ! Config::get( 'app.keep_uploaded_entry_files') )
		{
		  foreach( $tmp_filenames as $tmp_filename )
		  {
		    if( file_exists( $tmp_filename ) ) unlink( $tmp_filename );
		  }
		}

		return Response::make( $response, $status_code );
	}


	private function storeYoutubeEntry()
	{
        do
        {
            $token = Request::header( "X-API-TOKEN" );
            $session = $this->token->get_session( $token );

            $response = array();
            $statusCode = 200;

            $entry_by_default_enable = DB::table( 'settings' )->where( 'vUniqueName', '=', 'ENTRY_DEFAULT' )->pluck( 'vSettingValue' );
            $categoryId = DB::table( 'categories' )->where( 'category_id', '=', Input::get( 'category' ) )->pluck( 'category_id' );
            if(empty($categoryId))
            {
                // @todo currently, if no catagory provided, it sets category to 1, saves entry and returns error and created entry_id
                $response[ 'error' ] = "No category selected";
                $statusCode = 400;
            }

            // create EntryFile with video
            $youtubeUrl = Input::get( 'video_url', '' );
            if( empty( $youtubeUrl ) )
            {
                $response[ 'error' ] = "No video_url provided";
                $statusCode = 400;
                break;
            }
            $youtubeInfo = YoutubeHelper::getInfoFromUrl( $youtubeUrl );

            if( $youtubeInfo['error'] )
            {
                $response[ 'error' ] = $youtubeInfo['error'];
                $statusCode = 400;
                break;
            }

            if( $youtubeInfo['access'] != 'public' )
            {
                $response['error'] = 'Youtube video is not public';
                $statusCode = 400;
                break;
            }

            $videoEntryFile = array(
                'entry_file_name'         => $youtubeInfo['id'],
                'entry_file_entry_id'     => 0, // we will set it later
                'entry_file_location_type'=> 'url',
                'entry_file_location'     => $youtubeUrl,
                'entry_file_type'         => 'video_youtube',
                'entry_file_size' => 0,
            );

            // create EntryFile with thumbnail
            $thumbnailUrl = Input::get( 'thumbnail_url', '' );
            if( ! $thumbnailUrl )
            {
                $response[ 'error' ] = "No thumbnail_url provided";
                $statusCode = 400;
                break;
            }
            $urlParts = parse_url( $thumbnailUrl );
            if( empty( $urlParts ) || empty( $urlParts['path'] ) )
            {
                $response[ 'error' ] = "Invalid thumbnail_url provided";
                $statusCode = 400;
                break;
            }
            $pathParts = pathinfo( $urlParts['path'] );
            $thumbExtention = empty( $pathParts['extension'] ) ? 'jpg' : $pathParts['extension'];

            $thumbEntryFile = array(
                'entry_file_name'         => 'thumbnail',
                'entry_file_entry_id'     => 0, // we will set it later
                'entry_file_location'     => $thumbnailUrl,
                'entry_file_location_type'=> 'url',
                'entry_file_type'         => $thumbExtention,
                'entry_file_size' => 0,
            );

            if( $categoryId == 7 || $categoryId == 8 )
            {
                $entry_deleted = 0;
            }
            else
            {
                $entry_deleted = $entry_by_default_enable === 'TRUE' ? 0 : 1;
            }

            $input = [
                'entry_user_id'      => $session->token_user_id,
                'entry_category_id'  => $categoryId ? $categoryId : 1,
                'entry_type'         => Input::get( 'type' ),
                'entry_name'         => str_replace('"', '', Input::get( 'name' )),
                'entry_language'     => str_replace('"', '', Input::get( 'language' )),
                'entry_description'  => str_replace('"', '', Input::get( 'description' )),
                'entry_created_date' => date( 'Y-m-d H:i:s' ),
                'entry_deleted'      => $entry_deleted,
                'entry_subcategory'  => '',
                'entry_age'          => '',
                'entry_height'       => '',
            ];

            if( $categoryId == 3 ) // adjust modeling entry
            {
                $input['entry_subcategory'] = Input::get( 'subCategory' );
                $input['entry_age'] = Input::get( 'age' );
                $input['entry_height'] = Input::get( 'height' );
            }

            if( empty( $input['entry_subcategory'] ) ) unset( $input['entry_subcategory'] );
            if( empty( $input['entry_age'] ) ) unset( $input['entry_age'] );
            if( Input::get( 'splitVideoId', false ) ) $input['entry_splitVideoId'] = (int)Input::get( 'splitVideoId' );
            $input['entry_duration'] = isset( $youtubeInfo['duration'] ) ? $youtubeInfo['duration'] : -1;

            // set entry continent based on user continent
            $user = \User::findOrFail( $session->token_user_id );
            $input['entry_continent'] = $user->user_continent;
            unset( $user );

            // create new entry
            Eloquent::unguard();
            $newEntry = $this->entry->create( $input );
            $response[ 'entry_id' ] = $newEntry->entry_id;
            $status_code = 201;
            Eloquent::reguard();

            // add tags
            $tags = Input::get( 'tags' );
            if( isset( $tags ) )
            {
                $tags = array_values( explode( ',', $tags ) );

                foreach( $tags as $tag )
                {
                    $this->entry->addTag( str_replace('"', '', $tag), $newEntry->entry_id, $session->token_user_id );
                }
            }

            // add EntryFiles
            Eloquent::unguard();

            $videoEntryFile['entry_file_entry_id'] = $newEntry->entry_id;
            $videoEntryFile['entry_file_created_date'] = date( 'Y-m-d H:i:s' );
            $videoEntryFile['entry_file_updated_date'] = date( 'Y-m-d H:i:s' );

            EntryFile::create( $videoEntryFile );

            $thumbEntryFile['entry_file_entry_id'] = $newEntry->entry_id;
            $thumbEntryFile['entry_file_created_date'] = date( 'Y-m-d H:i:s' );
            $thumbEntryFile['entry_file_updated_date'] = date( 'Y-m-d H:i:s' );

            EntryFile::create( $thumbEntryFile );

            Eloquent::reguard();

            // if entry is split screen, send notification
            if( Input::get( 'splitVideoId', false ) )
            {
                $this->processSplitVideoNotifications(
                    $session->token_user_id,
                    $newEntry,
                    Input::get( 'splitVideoId' )
                );
            }

            // send notification about new unloaded entry
            if( ($newEntry->entry_category_id != 7) AND ($newEntry->entry_category_id != 8) ) { // no notification on profile entries
                $messageData = array(
                    'pushType' => 'newEntry',
                    'entries' =>  array(
                        array(
                            'id' => $newEntry->entry_id,
                            'userId' => $newEntry->entry_user_id,
                            'timeUpload' => \DateTime::createFromFormat( 'Y-m-d H:i:s', $newEntry->entry_created_date )->getTimestamp() * 1000,
                            'category' => $newEntry->entry_category_id,
                            'continent' => $newEntry->entry_continent,
                        )
                    )
                );
                SnsHelper::sendBroadcast( 'New entry uploaded', $messageData );
            }

        } while( false );

        return Response::make( $response, $statusCode );
    }


	/**
	 *
	 * @SWG\Api(
	 *   path="/entry/{entryId}",
	 *   description="Operation about Entries",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="PUT",
	 *       summary="Update entry",
	 *       notes="Updates an entry.",
	 *       nickname="updateEntry",
	 *       @SWG\Parameters(
	 *			@SWG\Parameter(
	 *           name="entryId",
	 *           description="Entry ID to be updated.",
	 *           paramType="path",
	 *           required=false,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="category",
	 *           description="Category ID the entry is submitted to.",
	 *           paramType="form",
	 *           required=false,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="name",
	 *           description="Name to be displayed for the entry",
	 *           paramType="form",
	 *           required=false,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="language",
	 *           description="Language of the entry",
	 *           paramType="form",
	 *           required=false,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="type",
	 *           description="The main file for the entry.",
	 *           paramType="form",
	 *           required=false,
	 *           type="string",
	 *             enum="['audio','video','image']"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="description",
	 *           description="Brief accompanying text for entry.",
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
	 *            code=404,
	 *            message="Entry not found"
	 *          ),
	 *
	 *          @SWG\ResponseMessage(
	 *            code=400,
	 *            message="Input validation failed"
	 *          )
	 *       )
	 *       )
	 *     )
	 *   )
	 */

	public function update( $id )
	{
	    markDeprecated( __METHOD__ );

		$client = getS3Client();

		//Validate Input
		$rules = array(
			'category' => 'numeric',
		);

		$validator = Validator::make( Input::get(), $rules );

		if( $validator->fails() )
		{
			$response[ 'errors' ] = $validator->messages();
			$status_code = 400;
		}
		else
		{

			$input = Input::get();

			if( isset( $input[ 'category' ] ) )
			{
				$update[ 'entry_category_id' ] = $input[ 'category' ];
			}
			if( isset( $input[ 'type' ] ) )
			{
				$update[ 'entry_type' ] = $input[ 'type' ];
			}
			if( isset( $input[ 'name' ] ) )
			{
				$update[ 'entry_name' ] = $input[ 'name' ];
			}
			if( isset( $input[ 'description' ] ) )
			{
				$update[ 'entry_description' ] = $input[ 'description' ];
			}
			if( isset( $input[ 'language' ] ) )
			{
				$update[ 'entry_language' ] = $input[ 'language' ];
			}

			$token = Request::header( "X-API-TOKEN" );

			$session = $this->token->get_session( $token );

			if( isset( $input[ 'tags' ] ) )
			{
				$tags = array_values( explode( ',', $input[ 'tags' ] ) );

				foreach( $tags as $tag )
				{
					$this->entry->addTag( $tag, $id, $session->token_user_id );
				}
			}

			if( isset( $update ) )
			{
				if( $this->entry->update( $update, $id, $session->token_user_id ) )
				{
					$response[ 'entry_id' ] = $id;
					$response[ 'notice' ] = "Entry updated successfully.";
					$status_code = 200;
				}
				else
				{
					$response[ 'error' ] = "Update failed - entry not found.";
					$status_code = 404;
				}
			}
			else
			{

				$response[ 'entry_id' ] = $id;
				$response[ 'notice' ] = "Entry updated successfully.";
				$status_code = 200;
			}
		}

		return Response::make( $response, $status_code );
	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/entry/tag/{entryIds}",
	 *   description="Operation about Entries",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Tag an Entry",
	 *       notes="Tags an entry. API-Token is required for this method.",
	 *       nickname="tagEntry",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="entryIds",
	 *           description="Entry ID you want to tag.",
	 *           paramType="path",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="tag",
	 *           description="Tag you wish to add to entry.",
	 *           paramType="form",
	 *           required=true,
	 *           type="true"
	 *         )
	 *       ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=404,
	 *            message="Entry not found"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function tagEntry( $id )
	{
	    markDeprecated( __METHOD__ );

		$tag = Input::get( 'tag' );

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		if( $this->entry->addTag( $tag, $id, $session->token_user_id ) )
		{
			$response[ 'entry_id' ] = $id;
			$response[ 'notice' ] = "Entry tagged successfully.";
			$status_code = 200;
		}
		else
		{
			$response[ 'error' ] = 'Failed to add tag';
			$status_code = 400;
		}

		return Response::make( $response, $status_code );
	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/entry/report/{entryIds}",
	 *   description="Operation about Entries",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Report an Entry",
	 *       notes="Reports an entry. API-Token is required for this method.",
	 *       nickname="reportEntry",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="entryIds",
	 *           description="Entry ID you want to report.",
	 *           paramType="path",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="reason",
	 *           description="Reason for reporting entry.",
	 *           paramType="form",
	 *           required=true,
	 *           type="true"
	 *         )
	 *       ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=404,
	 *            message="Entry not found"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function report( $id )
	{
		//Validate Input
		$rules = array(
			'reason' => 'required',
		);

		$validator = Validator::make( Input::get(), $rules );

		if( $validator->fails() )
		{
			$response[ 'errors' ] = $validator->messages();
			$status_code = 400;
		}
		else
		{

			$token = Request::header( "X-API-TOKEN" );

			$session = $this->token->get_session( $token );

			$report = [
				'entry_report_entry_id'      => $id,
				'entry_report_report_reason' => Input::get( 'reason' ),
				'entry_report_user_id'       => $session->token_user_id,
				'entry_report_created_date'  => date( 'Y-m-d H:i:s' ),
			];

			EntryReport::create( $report );

			$response[ 'entry_id' ] = $id;
			$response[ 'notice' ] = "Entry reported.";
			$status_code = 200;

			return Response::make( $response, $status_code );

		}
	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/entry/view/{entryId}",
	 *   description="Operation about Entries",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Register an Entry View",
	 *       notes="Registers that a user has viewed an entry. API-Token is required for this method.",
	 *       nickname="reportEntry",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="entryId",
	 *           description="Entry ID you want to report.",
	 *           paramType="path",
	 *           required=true,
	 *           type="string"
	 *         )
	 *       ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=404,
	 *            message="Entry not found"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function view( $id )
	{
	    markDead( __METHOD__, false );

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		\Entry::addView( $id, $session->token_user_id );

		$response[ 'notice' ] = "View recorded.";
		$status_code = 200;

		return Response::make( $response, $status_code );
	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/entryfeedback/{entry}",
	 *   description="Operation about Entries",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Report an Entry",
	 *       notes="Reports an entry. API-Token is required for this method.",
	 *       nickname="reportEntry",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="entry",
	 *           description="Entry ID you want to provide feedback for.",
	 *           paramType="path",
	 *           required=true,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="feedback",
	 *           description="Feedback.",
	 *           paramType="form",
	 *           required=true,
	 *           type="true"
	 *         )
	 *       ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=404,
	 *            message="Entry not found"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function storeFeedback( $id )
	{
	    markDead( __METHOD__, false );

		//Validate Input
		$rules = array(
			'feedback' => 'required',
		);

		$validator = Validator::make( Input::get(), $rules );

		if( $validator->fails() )
		{
			$response[ 'errors' ] = $validator->messages();
			$status_code = 400;
		}
		else
		{

			$token = Request::header( "X-API-TOKEN" );

			$session = $this->token->get_session( $token );

			$feedback = [
				'entry_feedback_entry_id'     => $id,
				'entry_feedback_content'      => Input::get( 'feedback' ),
				'entry_feedback_user_id'      => $session->token_user_id,
				'entry_feedback_created_date' => date( 'Y-m-d H:i:s' ),
			];

			EntryFeedback::create( $feedback );

			$response[ 'entry_id' ] = $id;
			$response[ 'notice' ] = "Feedback submitted.";
			$status_code = 201;

			return Response::make( $response, $status_code );

		}
	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/entryfeedback",
	 *   description="Operations about Entries",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="Get entry feedback",
	 *       notes="Returns available entry feedback",
	 *       nickname="getAllEntries",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="user",
	 *           description="User ID whose feedback you want to view.",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="entry",
	 *           description="Entry ID of feedback you want to view.",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="orderBy",
	 *           description="Order to display feedback in.",
	 *           paramType="query",
	 *           required=false,
	 *           type="string",
	 *             enum="['latest','entry']"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="page",
	 *           description="Page of results you want to view.",
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
	 *            message="Entry not found"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */
	public function getFeedback()
	{
	    markDead( __METHOD__, false );

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$return = [ ];

		//Get limit to calculate pagination
		$limit = ( Input::get( 'limit', '50' ) );

		//If not numeric set it to the default limit
		$limit = ( !is_numeric( $limit ) || $limit < 1 ) ? 50 : $limit;

		//Get page
		$page = ( Input::get( 'page', '1' ) );
		$page = ( !is_numeric( $page ) ) ? 1 : $page;

		//Get page
		$order_by = ( Input::get( 'orderBy', '0' ) );

		if( $order_by )
		{
			if( $order_by == 'entry' )
			{
				$order = 'entry_feedback_entry_id';
				$dir = 'asc';
			}
			elseif( $order_by == 'latest' )
			{
				$order = 'entry_feedback_created_date';
				$dir = 'desc';
			}
			else
			{
				$order = 0;
				$dir = 0;
			}
		}
		else
		{
			$order = 0;
			$dir = 0;
		}

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

		//Get user
		$user = ( Input::get( 'user', '0' ) );
		$user = ( !is_numeric( $user ) ) ? 0 : $user;

		//Get Category
		$entry = ( Input::get( 'entry', '0' ) );
		$entry = ( !is_numeric( $entry ) ) ? 0 : $entry;

		//Get tags
		$tag = ( Input::get( 'tagId', '0' ) );
		$tag = ( !is_numeric( $tag ) ) ? 0 : $tag;

		$feedbacks = $this->entry->feedback( $user, $entry, $order, $dir, $limit, $offset, false );
		$count = $this->entry->feedback( $user, $entry, $order, $dir, $limit, $offset, true );

		if( $count == 0 )
		{
			$response = [ 'feedback' => [ ] ];
		}

		else
		{
			foreach( $feedbacks as $feedback )
			{

				if( !isset( $current[ $feedback->entry_feedback_entry_id ] ) )
				{
					$current[ $feedback->entry_feedback_entry_id ] = [
						'entry'    => $this->oneEntry( $feedback->entry, $session, true ),
						'feedback' => [
							[
								'feedbackUser' => oneUser( $feedback->user, $session, false ),
								'feedback'     => $feedback->entry_feedback_content
							]
						]
					];
				}
				else
				{
					array_push( $current[ $feedback->entry_feedback_entry_id ][ 'feedback' ],
								[ 'feedbackUser' => oneUser( $feedback->user, $session, false ),
								  'feedback'     => $feedback->entry_feedback_content ] );
				}
			}

			foreach( $current as $i => $record )
			{
				$response[ ] = [
					'feedback'      => $record[ 'feedback' ],
					'feedbackEntry' => $record[ 'entry' ]
				];
			}
		}

		$status_code = 200;

		return Response::make( $response, $status_code );

	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/entry/search",
	 *   description="Operations about entries",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="Serch for an entry",
	 *       notes="Use the term parameter for your search term, if an email address is submitted the API will search for any users with this email address, anything other than an email address will result in a search of user names and display names. API-Token is required for this method.",
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
	    markDead( __METHOD__ );

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$term = Input::get( "term" );

		$results = $this->entry->search( $term );

		$status_code = 200;

		if( count( $results ) == 0 )
		{
			$return = json_encode( [ 'error' => 'No Entries Found' ] );
			$status_code = 404;
		}

		else
		{
			$return = [ ];
			foreach( $results as $entry )
			{
				$return[ 'entries' ][ ] = oneEntry( $entry, $session, true );
			}
		}

		return Response::make( $return, $status_code );
	}

	public function search2()
	{
	    markDead( __METHOD__ );

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$term = Input::get( "term" );
		/*
		$results = $this->entry->search( $term );
		$status_code = 200;

		if( count( $results ) == 0 )
		{
			$return = json_encode( [ 'error' => 'No Entries Found' ] );
			$status_code = 404;
		}

		else
		{
			$return = [ ];
			foreach( $results as $entry )
			{
				$return[ 'entries' ][ ][ 'entry' ] = oneEntry( $entry, $session, true );
			}
		}

		return Response::make( $return, $status_code );*/
		/*$results = DB::table('entries')
		->select('entries.*')
		->join('users', 'entries.entry_user_id', '=', 'users.user_id')
        ->where('entries.entry_name', 'LIKE', '%'.$term.'%')
        ->orWhere(function($query) use ($term)
            {
                $query->orWhere('entries.entry_description', 'LIKE', '%'.$term.'%')
						->orWhere('users.user_name', 'LIKE', '%'.$term.'%')
						->orWhere('users.user_full_name', 'LIKE', '%'.$term.'%');
            })
		->where('entries.entry_deleted', '=', '0')
        ->get();*/
		$excludeCategory = [7,8];
		$results = DB::table( 'entries' )
					 ->select( 'entries.*' )
					 ->join( 'users', 'entries.entry_user_id', '=', 'users.user_id' )
					 ->whereNotIn( 'entries.entry_category_id', $excludeCategory )
					 ->where( 'entries.entry_deleted', '=', '0' )
					 ->where( function ( $query ) use ( $term )
					 {
						 $query->orWhere( 'entries.entry_name', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'entries.entry_description', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'users.user_name', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'users.user_full_name', 'LIKE', '%' . $term . '%' );
					 } )
					 ->get();
		$status_code = 200;
		if( count( $results ) == 0 )
		{
			$return = json_encode( [ 'error' => 'No Entries Found' ] );
			$status_code = 404;
		}
		else
		{
			$return = [ ];
			for( $i = 0; $i < count( $results ); $i++ )
			{
				if( $results[ $i ]->entry_deleted === 1 )
				{
					continue;
				}
				else
				{
					if(!$this->oneEntryNew( $results[ $i ], $session, true ))
						continue;
					$return[ 'entries' ][ ][ 'entry' ] = $this->oneEntryNew( $results[ $i ], $session, true );
				}
			}
		}

		return Response::make( $return, $status_code );
	}

	//////////////////////      Rerank function for Cron Job        \\\\\\\\\\\\\\\\\\\\\\\\\\\\\

	public function rerank()
	{
		$user = 0;
		$category = 0;
		$tag = 0;
		$excludeCategory = array();
		$excludeCategory = [7,8];
		$entry_category = DB::table('entries')->whereIn( 'entry_category_id', $excludeCategory )->get();
		foreach( $entry_category as $c )
		{
			$exclude[ ] = $c->entry_id;
		}
		//$entries = $this->entry->all( $user, $category, 0, 0, 'entry_rank', 'asc', 10000, 0, false, true )->toArray();
		$entries = $this->entry->all( $user, $category, 0, $exclude, 'entry_rank', 'asc', 10000, 0, false, true )->toArray();

		$sortArray = array();
		$i = 0;

		foreach( $entries as $entry )
		{

			$up_votes = 0;
			$down_votes = 0;

			foreach( $entry[ 'vote' ] as $vote )
			{
				if( $vote[ 'vote_up' ] && !$vote[ 'vote_deleted' ] )
				{
					$up_votes++;
				}
				elseif( $vote[ 'vote_down' ] && !$vote[ 'vote_deleted' ] )
				{
					$down_votes++;
				}

			}

			$entries[ $i ][ 'entry_up_votes' ] = $up_votes;
			$entry[ 'entry_up_votes' ] = $up_votes;
			$entries[ $i ][ 'entry_down_votes' ] = $down_votes;
			$entry[ 'entry_down_votes' ] = $down_votes;

			foreach( $entry as $key => $value )
			{
				if( !isset( $sortArray[ $key ] ) )
				{
					$sortArray[ $key ] = array();
				}
				$sortArray[ $key ][ ] = $value;
			}
		}

		$orderby = "entry_up_votes"; //change this to whatever key you want from the array

		array_multisort( $sortArray[ $orderby ], SORT_DESC, $entries );

		$r = 1;
		$id = array();
		$rank = array();

		foreach( $entries as $entry )
		{
			if( $entry[ 'entry_rank' ] != $r )
			{
				$rank[ $entry[ 'entry_id' ] ] = $r;
				$id[ ] = $entry[ 'entry_id' ];
			}
			$r++;
		}

		if( count( $rank ) )
		{
			$entries2 = $this->entry->whereIn( $id, $user, $category, 10000, 0, false );

			foreach( $entries2 as $entry )
			{
				$entry->entry_rank = $rank[ $entry->entry_id ];
				$entry->save();
			}
		}
	}

	public function oneEntry( $entry, $session, $includeUser = false )
	{

		$client = getS3Client();

		$current = array();

		$up_votes = 0;
		$down_votes = 0;
		foreach( $entry->vote as $vote )
		{
			if( $vote->vote_up == 1 && $vote->vote_deleted == 0 )
			{
				$up_votes++;
			}
			elseif( $vote->vote_down == 1 && $vote->vote_deleted == 0 )
			{
				$down_votes++;
			}

		}

		$current[ 'id' ] = $entry->entry_id;
		if( $entry->entry_splitVideoId ) $current['splitVideoId'] = $entry->entry_splitVideoId;
		$current[ 'category' ] = $entry->category->category_name;
		if( isset( $entry->entry_category_id )  && $entry->entry_category_id == 3 )
		{
			$current[ 'subcategory' ] = $entry->entry_subcategory;
			$current[ 'age' ] = $entry->entry_age;
			$current[ 'height' ] = $entry->entry_height;
		}
		$current[ 'type' ] = $entry->entry_type;

		if( $includeUser )
		{
			$current[ 'user' ] = oneUser( $entry->User, $session );

//			$current[ 'user' ][ 'userId' ] = $entry->entry_user_id;
//			$current[ 'user' ][ 'userName' ] = $entry->User->user_name;
//			$current[ 'user' ][ 'displayName' ] = $entry->User->user_display_name;
//			$current[ 'user' ][ 'email' ] = $entry->User->user_email;
//			$current[ 'user' ][ 'profileImage' ] = ( !empty( $entry->User->user_profile_image ) )
//				? "http://" . $_ENV[ 'URL' ] . "/" . $entry->User->user_profile_cover : "";
//			$current[ 'user' ][ 'profileCover' ] = ( !empty( $entry->User->user_profile_cover ) )
//				? "http://" . $_ENV[ 'URL' ] . "/" . $entry->User->user_profile_cover : "";
//			xdebug_break();
//			$current[ 'user' ][ 'isMyStar' ] = Star::where( 'user_star_user_id', '=', $session->user_id )->where( 'user_star_star_id', '=', $entry->entry_user_id )->count();
		}

		$current[ 'name' ] = $entry->entry_name;
		$current[ 'description' ] = $entry->entry_description;
		$current[ 'created' ] = $entry->entry_created_date;
		$current[ 'modified' ] = $entry->entry_modified_date;

		$current[ 'tags' ] = array();
		foreach( $entry->entryTag as $tag )
		{
			$current[ 'tags' ][ ] = Tag::find( $tag->entry_tag_tag_id )->tag_name;
		}

		//break;

		$current[ 'entryFiles' ] = array();
		foreach( $entry->file as $file )
		{
			$url = $client->getObjectUrl( Config::get('app.bucket'), $file->entry_file_name . "." . $file->entry_file_type, '+720 minutes' );
			$current[ 'entryFiles' ][ ] = [
				'fileType' => $file->entry_file_type,
				'filePath' => $url ];

			$current[ 'videoThumb' ] = ( $file->entry_file_type == "mp4" ) ?
				$client->getObjectUrl( Config::get('app.bucket'), 'thumbs/' . $file->entry_file_name . '-thumb.jpg', '+720 minutes' )
				: "";
		}

		$current[ 'upVotes' ] = $up_votes;
		$current[ 'downVotes' ] = $down_votes;
		$current[ 'rank' ] = $entry->entry_rank;
		$current[ 'language' ] = $entry->entry_language;

		if( $entry->entry_deleted )
		{
			$current[ 'deleted' ] = true;
		}
		else
		{
			$current[ 'deleted' ] = false;
		}

		return $current;
	}

	public function delete( $id )
	{

		$this->entry->delete( $id );

		return Response::make( [ 'status' => 'entry deleted' ], 200 );
	}

	public function undelete( $id )
	{
	    markDeprecated( __METHOD__ );

		$this->entry->undelete( $id );

		return Response::make( [ 'status' => 'entry undeleted' ], 200 );
	}

	public function mysearch()
	{
	    markDead( __METHOD__, false );

		$token = Request::header( "X-API-TOKEN" );
		$session = $this->token->get_session( $token );
		$term = Input::get( "term" );

		/*$results = $this->entry->search( $term );
		//dd(DB::getQueryLog());
		$status_code = 200;

		if( count( $results ) == 0 )
		{
			$return = json_encode( [ 'error' => 'No Entries Found' ] );
			$status_code = 404;
		}

		else
		{
			$return = [ ];
			foreach( $results as $entry )
			{
				$return[ 'entries' ][ ][ 'entry' ] = oneEntry( $entry, $session, true );
			}
		}

		return Response::make( $return, $status_code );*/
		$results = DB::table( 'entries' )
					 ->select( 'entries.*' )
					 ->join( 'users', 'entries.entry_user_id', '=', 'users.user_id' )
					 ->where( 'entries.entry_deleted', '=', '0' )
					 ->where( function ( $query ) use ( $term )
					 {
						 $query->orWhere( 'entries.entry_name', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'entries.entry_description', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'users.user_name', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'users.user_full_name', 'LIKE', '%' . $term . '%' );
					 } )
			//->where('entries.entry_deleted', '=', '0')
					 ->get();
		//dd(DB::getQueryLog());
		$status_code = 200;
		if( count( $results ) == 0 )
		{
			$return = json_encode( [ 'error' => 'No Entries Found' ] );
			$status_code = 404;
		}
		else
		{
			$return = [ ];
			for( $i = 0; $i < count( $results ); $i++ )
			{
				if( $results[ $i ]->entry_deleted === 1 )
				{
					continue;
				}
				else
				{
					if(!$this->oneEntryNew( $results[ $i ], $session, true ))
						continue;
					$return[ 'entries' ][ ][ 'entry' ] = $this->oneEntryNew( $results[ $i ], $session, true );
				}
			}
		}

		return Response::make( $return, $status_code );
	}

	public function oneEntryNew( $entry, $session, $includeUser = false )
	{

		$client = getS3Client();

		$current = array();

		$up_votes = 0;
		$down_votes = 0;
		$votes = Vote::where( 'vote_entry_id', '=', $entry->entry_id )->get();
		foreach( $votes as $vote )
		{
			if( $vote->vote_up == 1 && $vote->vote_deleted == 0 )
			{
				$up_votes++;
			}
			elseif( $vote->vote_down == 1 && $vote->vote_deleted == 0 )
			{
				$down_votes++;
			}

		}
		$current[ 'id' ] = $entry->entry_id;
		if( $entry->entry_splitVideoId ) $current['splitVideoId'] = $entry->entry_splitVideoId;
		if( isset( $entry->entry_category_id )  && $entry->entry_category_id == 3 )
		{
			$current[ 'subcategory' ] = $entry->entry_subcategory;
			$current[ 'age' ] = $entry->entry_age;
			$current[ 'height' ] = $entry->entry_height;
		}
		$column = 'category_id';
		$category_name = DB::table( 'categories' )->where( 'category_id', '=', $entry->entry_category_id )->pluck( 'category_name' );
		$current[ 'category' ] = $category_name;
		$current[ 'type' ] = $entry->entry_type;

		if( $includeUser )
		{
			$User = User::where( 'user_id', '=', $entry->entry_user_id )->first();
			$current[ 'user' ] = oneUser( $User, $session );
		}

		$current[ 'name' ] = $entry->entry_name;
		$current[ 'description' ] = $entry->entry_description;
		$totalComments = Comment::where( 'comment_entry_id', '=', $entry->entry_id )->where( 'comment_deleted', '=', '0' )->count();
		if ( $entry instanceof \Entry ) {
		  $totalviews = $entry->viewsTotal();
		} else {
		    $totalviews = $entry->entry_views + $entry->entry_views_added; // @todo must be in model
		}
		$current[ 'totalComments' ] = $totalComments;
		$current[ 'totalviews' ] = $totalviews;
		$current[ 'created' ] = $entry->entry_created_date;
		$current[ 'modified' ] = $entry->entry_modified_date;

		$current[ 'tags' ] = array();
		$entryTag = EntryTag::where( 'entry_tag_entry_id', '=', $entry->entry_id )->get();
		foreach( $entryTag as $tag )
		{
			$current[ 'tags' ][ ] = Tag::find( $tag->entry_tag_tag_id )->tag_name;
		}

		$current[ 'entryFiles' ] = array();
		$EntryFile = EntryFile::where( 'entry_file_entry_id', '=', $entry->entry_id )->get();
		/*foreach( $EntryFile as $file )
		{
			$url = $client->getObjectUrl( Config::get('app.bucket'), $file->entry_file_name . "." . $file->entry_file_type, '+720 minutes' );
			$current[ 'entryFiles' ][ ] = [
				'fileType' => $file->entry_file_type,
				'filePath' => $url ];

			$current[ 'videoThumb' ] = ( $file->entry_file_type == "mp4" ) ?
				$client->getObjectUrl( Config::get('app.bucket'), 'thumbs/' . $file->entry_file_name . '-thumb.jpg', '+720 minutes' )
				: "";
		}*/
		if(count($EntryFile) <= 0)
			return false;
		foreach( $EntryFile as $file )
		{
			$url = $client->getObjectUrl( Config::get('app.bucket'), $file->entry_file_name . "." . $file->entry_file_type, '+720 minutes' );
			$current[ 'entryFiles' ][ ] = [
				'fileType' => $file->entry_file_type,
				'filePath' => $url ];

			$current[ 'videoThumb' ] = ( $file->entry_file_type == "mp4" ) ?
				$client->getObjectUrl( Config::get('app.bucket'), 'thumbs/' . $file->entry_file_name . '-thumb.jpg', '+720 minutes' )
				: "";
		}
		if( ( count( $current[ 'entryFiles' ] ) < 2 ) &&  $entry->entry_type === 'audio' )
		{
			return false;
		}
		if( ( count( $current[ 'entryFiles' ] ) < 1 ) &&  $entry->entry_type === 'video' )
		{
			return false;
		}

		$current[ 'upVotes' ] = $up_votes;
		$current[ 'downVotes' ] = $down_votes;
		$current[ 'rank' ] = $entry->entry_rank;
		$current[ 'language' ] = $entry->entry_language;

		if( $entry->entry_deleted )
		{
			$current[ 'deleted' ] = true;
		}
		else
		{
			$current[ 'deleted' ] = false;
		}

		return $current;
	}

	public function dummytest()
	{
	    markDead( __METHOD__ );

		/*$exclude = [ ];
		$entry_rank = DB::table('entries')->where( 'entry_rank', '=', '0')->get();
		foreach( $entry_rank as $rank )
		{
			$exclude[ ] = $rank->entry_id;
		}
		$team = DB::table('users')
		->select('user_id')
		->where( 'user_user_group', 4 );
		$order = 'entry_rank';
		$dir = 'asc';
		$query = DB::table('entries')
		->select('entries.entry_user_id as user_id')
		->where( 'entry_id', '>', '0' );
		$query = $query->where( 'entry_rank', '>', 0 );
		$query = $query->where( 'entry_deleted', '=', 0 );
		$query = $query->whereNotIn( 'entries.entry_id',$exclude );
		$query = $query->orderBy( $order, $dir );
		$query = $query->take( 10 );
		$entries = $query;
		$combined = $team->union($entries)->get();
		$ids= [];
		foreach( $combined as $users )
		{
			$ids[] = $users->user_id;
		}
		//$mentors = Mentor::orderByRaw(DB::raw("FIELD(mentor_id, $names)"))->take( $limit )->skip( $offset )->get();
		$newOrderBy = implode(",",$ids);
		$users = User::whereIn( 'user_id', $ids )->orderByRaw(DB::raw("FIELD(user_id, $newOrderBy)"))->get();
		*/
		//Get users greater than the cursor from
		//$users = User::where( 'user_user_group', 4 )->get();
		/*$include = [ 4, 5 ];
		$exclude = [ ];
		$entry_rank = DB::table('entries')->where( 'entry_rank', '=', '0')->get();
		foreach( $entry_rank as $rank )
		{
			$exclude[ ] = $rank->entry_id;
		}
		$team = DB::table('users')
		->select('user_id')
		//->where( 'user_user_group', 4 );
		->whereIn( 'user_user_group',$include );
		//->orderBy( 'user_user_group','asc');
		$order = 'entry_rank';
		$dir = 'asc';
		$query = DB::table('entries')
		->select('entries.entry_user_id as user_id')
		->where( 'entry_id', '>', '0' );
		$query = $query->where( 'entry_rank', '>', 0 );
		$query = $query->where( 'entry_deleted', '=', 0 );
		$query = $query->whereNotIn( 'entries.entry_id',$exclude );
		$query = $query->orderBy( $order, $dir );
		$query = $query->take( 10 );
		$entries = $query;
		$combined = $team->union($entries)->get();
		$ids= [];
		foreach( $combined as $teamusers )
		{
			$ids[] = $teamusers->user_id;
		}
		$newOrderBy = implode(",",$ids);
		$users = User::whereIn( 'user_id', $ids )->orderByRaw(DB::raw("FIELD(user_id, $newOrderBy)"))->get();
		//Find total number to put in header
		//$count = User::where( 'user_user_group', 4 )->count();
		$count = User::whereIn( 'user_id', $ids )->orderByRaw(DB::raw("FIELD(user_id, $newOrderBy)"))->count();


		//print_r($users);
		foreach( $users as $user )
		{
			$idstmp[] = $user->user_id;
		}
		echo "<pre>";
		print_r($idstmp);
		dd(DB::getQueryLog());*/
		$return = [ ];

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		//$entries = Entry::where('entry_rank', '!=', 0)->with( 'user' )->orderBy( 'entry_rank', 'asc' )->get();
		//$entries = DB::table('entries')whereRaw('entry_rank != 0 and entry_deleted = 0')->with( 'user' )->orderBy( 'entry_rank', 'asc' )->get();

		/*$entries = DB::table('entries')
		->select('entries.*')
		->join('users', 'entries.entry_user_id', '=', 'users.user_id')
		->where('entries.entry_deleted', '=', '0')
	    ->where(function($query)
            {
                $query->where('entries.entry_rank', '!=', 0);
            })
       ->orderBy( 'entry_rank', 'asc' )->get();*/

		$entries = DB::table( 'entries' )
					 ->select( 'entries.*' )
					 ->join( 'users', 'entries.entry_user_id', '=', 'users.user_id' )
					 ->where( 'entries.entry_deleted', '=', '0' )
					 ->where( function ( $query )
					 {
						 $query->where( 'entries.entry_rank', '!=', 0 );
					 } )
					 ->orderBy( 'entry_rank', 'asc' )
					 ->get();
		$users = [ ];
		$return[ 'talents' ] = [ ];

		$rank = 1;

		foreach( $entries as $entry )
		{
			if( !in_array( $entry->entry_user_id, $users ) )
			{
				$User = User::where( 'user_id', '=', $entry->entry_user_id )->first();
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
	 *   path="/entry/updateViewCount",
	 *   description="Increase one count everytime when the video plays.",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="entryId",
	 *           description="Entry id of the video or audio file.",
	 *           paramType="form",
	 *           required=true,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="userId",
	 *           description="user id for who played this video or audio.",
	 *           paramType="form",
	 *           required=true,
	 *           type="integer"
	 *         ),
	 *       ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="missing fields"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=404,
	 *            message="No entry found"
	 *          )
	 *        )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function updateViewCount()
	{
		$rules = array(
			'entryId' => 'required',
			'userId'  => 'required',
		);

		$validator = Validator::make( Input::all(), $rules );

		if( $validator->fails() )
		{
			$return = $validator->messages();
			$status_code = 401;
		}
		elseif( \Entry::addView( Input::get( 'entryId' ), Input::get( 'userId' ) ))
		{
		    $return = [ 'notice' => 'success' ];
		    $status_code = 200;

		    // return entry in response to allow client to update entry info
		    $entryId = Input::get('entryId');
		    $userId = Input::get( 'userId' );
		    $entryInfo = ResponseHelper::oneEntryInfo( $entryId, $userId );
		    $return['entries'][]['entry'] = $entryInfo;
		}
		else
		{
		    $return = [ 'error' => 'No Entries Found' ];
		    $status_code = 404;
		}

		$response = Response::make( $return, $status_code );

		return $response;
	}

	// For delete entries
	public function deleteEntryFiles()
	{

		if( isset( $_POST ) )
		{
			$post = $_POST;

			$filename = $post[ 'filename' ];
			$filetype = $post[ 'filetype' ];

			$originalfile = 'uploads/' . $filename . '-uploaded.' . $filetype;
			$mp4file = 'uploads/' . $filename . '.' . $filetype;
			$logfile = 'uploads/' . $filename . '-log.txt';
			$thumbfile = 'uploads/' . $filename . '-thumb.jpg';

			if( File::exists( $originalfile ) )
			{
				File::delete( $originalfile );
			}
			if( File::exists( $mp4file ) )
			{
				File::delete( $mp4file );
			}
			if( File::exists( $logfile ) )
			{
				File::delete( $logfile );
			}
			if( File::exists( $thumbfile ) )
			{
				File::delete( $thumbfile );
			}
			$return = [ 'notice' => 'success' ];
			$status_code = 200;
		}
		else
		{
			$return = [ 'error' => 'No files deleted' ];
			$status_code = 404;
		}

		$response = Response::make( $return, $status_code );

		return $response;
	}
	// End
	/**
	 *
	 * @SWG\Api(
	 *   path="/entry/mix",
	 *   description="Operations about Entries",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="Get all entries",
	 *       notes="Returns all available entries",
	 *       nickname="getAllEntries",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="fields",
	 *           description="Accepted values for the fields parameter are: id, userId, groupName, category, type, name, description, created, modified, tags, entryFiles, upVotes, downVotes, rank, language.",
	 *           paramType="query",
	 *           required=false,
	 *           type="comma seperated list"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="user",
	 *           description="User ID whose entries you want to view.",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="category",
	 *           description="Category ID of entries you want to view.",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="tagId",
	 *           description="Tag ID of entries you want to view.",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="orderBy",
	 *           description="Order to display entries in.",
	 *           paramType="query",
	 *           required=false,
	 *           type="string",
	 *             enum="['latest','popular']"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="language",
	 *           description="Filter categories by language.",
	 *           paramType="query",
	 *           required=false,
	 *           type="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="excludeVotes",
	 *           description="Exclude entries the user has already voted on.",
	 *           paramType="query",
	 *           required=false,
	 *           type="string",
	 *           enum="['true', 'false']"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="showFeedback",
	 *           description="Show feedback boolean - 1 for true, 0 for false",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="page",
	 *           description="Page of results you want to view.",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="limit",
	 *           description="Maximum number of representations in response.",
	 *           paramType="query",
	 *           required=false,
	 *           type="integer",
	 *         )
	 *       ),
	 *       @SWG\ResponseMessages(
	 *          @SWG\ResponseMessage(
	 *            code=401,
	 *            message="Authorization failed"
	 *          ),
	 *          @SWG\ResponseMessage(
	 *            code=404,
	 *            message="Entry not found"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */
	public function mix()
	{
		$client = getS3Client();

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$fields = array_values( explode( ',', Input::get( "fields" ) ) );

		if( $fields[ 0 ] == "" )
		{
			unset( $fields );
		}

		$return = [ ];
		$errors = array();
		$valid = false;

		if( !empty( $fields ) )
		{
			//Check if fields are valid
			foreach( $fields as $field )
			{
				if( !in_array( $field, $this->valid_fields ) )
				{
				    $errors[] = [ $field . " is not a valid field." ];
					//$return[ 'errors' ][ ] = [ $field . " is not a valid field." ];
				}
				else
				{
					$valid = true;
				}
			}

		}

		//Get limit to calculate pagination
		$limit = ( Input::get( 'limit', '20' ) );

		//If not numeric set it to the default limit
		$limit = ( !is_numeric( $limit ) || $limit < 1 ) ? 20 : $limit;

		//Get page
		$page = ( Input::get( 'page', '1' ) );
		$page = ( !is_numeric( $page ) ) ? 1 : $page;

		//Get page
		$order_by = ( Input::get( 'orderBy', 'id' ) );

		$debug = false;

		switch($order_by)
		{
			case "popular":
				$order = 'entry_rank';
				$dir = 'asc';
				break;
			case "latest":
				$order = 'entry_created_date';
				$dir = 'desc';
				break;
			default:
				$order = 'entry_created_date';
				$dir = 'desc';
		}

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

		//Get user
		$user = ( Input::get( 'user', '0' ) );
		$user = ( !is_numeric( $user ) ) ? 0 : $user;

		//Get Category
		$category = ( Input::get( 'category', '0' ) );
		$category = ( !is_numeric( $category ) ) ? 0 : $category;

		$showFeedback = ( Input::get( 'showFeedback', '0' ) );

		//Get tags
		$tag = ( Input::get( 'tagId', '0' ) );
		$tag = ( !is_numeric( $tag ) ) ? 0 : $tag;

		$exclude = [ ];

		if( Input::get( 'excludeVotes' ) == 'true' )
		{
		    // skip entries, voted down by user
		    $exclude['excludeVotes'] = $session->token_user_id;
		}
		if( $order_by == 'popular' )
		{
		    // skip not popular entries
		    $exclude['notPopular'] = true;
		}

		if( $session->token_app_version < 4 ) // skip youtube entries
		{
		    $exclude['entryType'] = 'video_youtube';
		}

		$entries = $this->entry->allComplexExclude( $user, $category, $tag, $exclude, $order, $dir, $limit, $offset, false, true );

		// for some strange reasons, count all user entries, independent of 'exclude' and 'tag'
		$count = $this->entry->allComplexExclude( $user, $category, 0, array(), $order, $dir, $limit, $offset, true );

		$params = array(
		    'userId' => $user,
		    'totalCount' => $count,
		    'url' => 'index.php/entry/?',
		    'limit' => $limit,
		    'page' => $page,
		    'debug' => $debug,
		    'errors' => $errors,
		);
		//check to see if fields were specified and at least one is valid
		if ( ( !empty( $fields ) ) && $valid )
		    $params['fields'] = $fields;

		$data = EntriesResponseHelper::getForMix(
		    $entries,
		    $session->token_user_id,
		    $showFeedback,
		    $params
		);
		$status_code = $data['code'];
		$return = $data['data'];

		$response = Response::make( $return, $status_code );

		$response->header( 'X-Total-Count', $count );

		return $response;
	}



	public function search3()
	{
	    markDead( __METHOD__ );

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$term = Input::get( "term" );
		//$excludeCategory = [7,8];
		$results = DB::table( 'entries' )
					 ->select( 'entries.*' )
					 ->leftJoin( 'users', 'entries.entry_user_id', '=', 'users.user_id' )
					 ->leftJoin('comments', 'comments.comment_entry_id', '=', 'entries.entry_id')
					 ->leftJoin('facebook_users', 'users.user_facebook_id', '=', 'facebook_users.facebook_user_id')
					 ->leftJoin('google_users', 'users.user_google_id', '=', 'google_users.google_user_id')
					// ->whereNotIn( 'entries.entry_category_id', $excludeCategory )
					 ->where( 'entries.entry_deleted', '=', '0' )
					->where( 'users.user_deleted', '=', '0' )
					->where( function ( $query ) use ( $term )
					 {
						 $query->orWhere( 'entries.entry_name', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'entries.entry_description', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'users.user_name', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'users.user_full_name', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'facebook_users.facebook_user_display_name', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'facebook_users.facebook_user_user_name', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'google_users.google_user_display_name', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'google_users.google_user_user_name', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'comments.comment_content', 'LIKE','%' . $term . '%' );
					 } )
					 ->groupBy('entry_id')
					 ->get();
		$status_code = 200;
		if( count( $results ) == 0 )
		{
			$results = DB::table( 'users' )
					 ->select( 'users.*' )
					 ->leftJoin('facebook_users', 'users.user_facebook_id', '=', 'facebook_users.facebook_user_id')
					 ->leftJoin('google_users', 'users.user_google_id', '=', 'google_users.google_user_id')
					 ->where( 'users.user_deleted', '=', '0' )
					 ->where( function ( $query ) use ( $term )
					 {
						 $query->orWhere( 'users.user_name', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'users.user_full_name', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'facebook_users.facebook_user_display_name', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'facebook_users.facebook_user_user_name', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'google_users.google_user_display_name', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'google_users.google_user_user_name', 'LIKE', '%' . $term . '%' );
					 } )
					 ->get();
			if(count($results) > 0)
			{
				$User = User::where( 'user_id', '=', $results[0]->user_id )->first();
				$current[ 'category' ] = 'onlyprofile';
				$current[ 'user' ] = oneUser( $User, $session );
				$return[ 'entries' ][ ][ 'entry' ] = $current;
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
			$return = [ ];
			for( $i = 0; $i < count( $results ); $i++ )
			{
				if( $results[ $i ]->entry_deleted === 1 )
				{
					continue;
				}
				else
				{
					if(!$this->oneEntryNew( $results[ $i ], $session, true ))
						continue;
					$return[ 'entries' ][ ][ 'entry' ] = $this->oneEntryNew( $results[ $i ], $session, true );
				}
			}
		}

		return Response::make( $return, $status_code );
	}
	public function videoupload()
	{
	    markDead( __METHOD__ );

		$file = Input::file( 'video' );

		if( !empty( $file ) )
		{
			$dest = 'uploads';

			$filename = 'Spaceo_'.time();

			$extension = $file->getClientOriginalExtension();

			$file_in = $file->getRealPath();

			$file_out = Config::get('app.home') . 'public/uploads/' . $filename . '.mp4';

			// Transcode Video
			shell_exec( Config::get( 'app.bin_ffmpeg' ).' -i ' . $file_in . ' -vf scale=306:306 -strict -2 ' . $file_out . ' 2>' . Config::get('app.home') . 'public/uploads/' . $filename . '-log.txt' );
			$file->move( Config::get('app.home') . 'public/uploads/', $filename . '-uploaded.' . $extension );

			$extension = 'mp4';

			$handle = fopen( $file_out, "r" );

			//Flysystem::connection( 'awss3' )->put( $filename . "." . $extension, fread( $handle, filesize( $file_out ) ) );

			$thumb = Config::get('app.home') . 'public/uploads/' . $filename . '-thumb.jpg';

			exec( Config::get( 'app.bin_ffprobe' ).' 2>&1 ' . $file_out . ' | grep "rotate          :"', $rotation );

			if( isset( $rotation[ 0 ] ) )
			{
				$rotation = substr( $rotation[ 0 ], 17 );
			}

			$contents = file_get_contents( Config::get('app.home') . 'public/uploads/' . $filename . '-log.txt' );
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

			shell_exec( Config::get( 'app.bin_ffmpeg' ).' -i ' . $file_out . $transpose . ' -vframes 1 -an -s 300x300 -ss 00:00:00.10 ' . $thumb );

			$handle = fopen( $thumb, "r" );

			//Flysystem::connection( 'awss3' )->put( "thumbs/" . $filename . "-thumb.jpg", fread( $handle, filesize( $thumb ) ) );

			//						unlink($file_out);
			//						unlink($thumb);
			$response[ 'notice' ] = "success";
			$status_code = 200;
		}
		else
		{

			$response[ 'error' ] = "error";
			$status_code = 400;
		}
		return Response::make( $response, $status_code );
	}

	public function search4()
	{
		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		if( $session->token_app_version >= 2 )
		{
		    return $this->search4_v2();
		}

		$term = Input::get( "term" );

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

		$return = [ ];
		//$excludeCategory = [7,8];
		$results = DB::table( 'entries' )
					 ->select( 'entries.entry_id', 'entries.entry_user_id', 'entries.entry_deleted' )
					 ->leftJoin( 'users', 'entries.entry_user_id', '=', 'users.user_id' )
					 ->leftJoin('comments', 'comments.comment_entry_id', '=', 'entries.entry_id')
					 ->leftJoin('facebook_users', 'users.user_facebook_id', '=', 'facebook_users.facebook_user_id')
					 ->leftJoin('google_users', 'users.user_google_id', '=', 'google_users.google_user_id')
					// ->whereNotIn( 'entries.entry_category_id', $excludeCategory )
					 ->where( 'entries.entry_deleted', '=', '0' )
					 ->where( 'entries.entry_type', '<>', 'video_youtube' )
					 ->where( 'users.user_deleted', '=', '0' )
					 ->where( function ( $query ) use ( $term )
					 {
						 $query->orWhere( 'entries.entry_name', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'entries.entry_description', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'users.user_name', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'users.user_full_name', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'facebook_users.facebook_user_display_name', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'facebook_users.facebook_user_user_name', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'google_users.google_user_display_name', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'google_users.google_user_user_name', 'LIKE', '%' . $term . '%' )
							   ->orWhere( 'comments.comment_content', 'LIKE','%' . $term . '%' );
					 } )
					 ->groupBy('entry_id')
					 //->get();
					 ->take( $limit )->skip( $offset )->get();
		$status_code = 200;

		$results_user = DB::table( 'users' )
				 ->select( 'users.user_id' )
				 ->leftJoin('facebook_users', 'users.user_facebook_id', '=', 'facebook_users.facebook_user_id')
				 ->leftJoin('google_users', 'users.user_google_id', '=', 'google_users.google_user_id')
				 ->where( 'users.user_deleted', '=', '0' )
				 ->where( function ( $query ) use ( $term )
				 {
					 $query->orWhere( 'users.user_name', 'LIKE', '%' . $term . '%' )
						   ->orWhere( 'users.user_full_name', 'LIKE', '%' . $term . '%' )
						   ->orWhere( 'facebook_users.facebook_user_display_name', 'LIKE', '%' . $term . '%' )
						   ->orWhere( 'facebook_users.facebook_user_user_name', 'LIKE', '%' . $term . '%' )
						   ->orWhere( 'google_users.google_user_display_name', 'LIKE', '%' . $term . '%' )
						   ->orWhere( 'google_users.google_user_user_name', 'LIKE', '%' . $term . '%' );
				 } )
				 //->get();
				 ->take( $limit )->skip( $offset )->get();

		if(count($results_user) > 0)
		{
		    // get user ids for prepare
		    $userIds = array();
		    for( $i = 0; $i < count( $results_user ); $i++ )
		    {
		        $userIds[] = $results_user[$i]->user_id;
		    }
		    UserHelper::prepareUsers( $userIds, array( 'votes', 'stars' ) );
			for( $i = 0; $i < count( $results_user ); $i++ )
			{
				$current = array();
				$current[ 'category' ] = 'onlyprofile';
				$current[ 'user' ] = ResponseHelper::oneUser( $results_user[$i]->user_id, $session->token_user_id );
				$return[ 'entries' ][ ][ 'entry' ] = $current;
			}
		}
		// get entry ids and user ids for prepare
		$userIds = $entryIds = array();
		for( $i = 0; $i < count( $results ); $i++ )
		{
		    if( $results[$i]->entry_deleted == 1 )
		    {
		        continue;
		    }
		    $entryIds[] = $results[$i]->entry_id;
		    $userIds[] = $results[$i]->entry_user_id;
		}
		UserHelper::prepareUsers( $userIds, array( 'votes', 'stars') );
		EntryHelper::prepareEntries( $entryIds, array('commentCounts', 'filesInfo', 'tagNames', 'totalVotes', 'votedByUser') );

		$sessionUserId = $session->token_user_id;
		for( $i = 0; $i < count( $results ); $i++ )
		{
			if( $results[ $i ]->entry_deleted === 1 )
			{
				continue;
			}
			else
			{
			    $entryId = $results[$i]->entry_id;
			    $entry = ResponseHelper::oneEntryNewById( $entryId, $sessionUserId, true );
			    if( ! $entry )
			        continue;

			    $return[ 'entries' ][ ][ 'entry' ] = $entry;
			}
		}
		return Response::make( $return, $status_code );
	}


	public function search4_v2()
	{
	    $token = Request::header( "X-API-TOKEN" );

	    $session = $this->token->get_session( $token );

	    $currentUser = User::find( $session->token_user_id );

	    $geoFilter = $currentUser->getContinentFilter();
	    if( $geoFilter )
	    {
	       if( \Config::get( 'app.force_include_all_world', false ) ) $geoFilter[] = 0;
	    }

	    $categoryFilter = $currentUser->getCategoryFilter();

	    $term = Input::get( "term" );

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

	    $return = [ ];
	    //$excludeCategory = [7,8];
	    $results =
	    $entriesQuery = DB::table( 'entries' )
	    ->select( 'entries.*' )
	    ->leftJoin( 'users', 'entries.entry_user_id', '=', 'users.user_id' )
	    ->leftJoin('comments', 'comments.comment_entry_id', '=', 'entries.entry_id')
	    ->leftJoin('facebook_users', 'users.user_facebook_id', '=', 'facebook_users.facebook_user_id')
	    ->leftJoin('google_users', 'users.user_google_id', '=', 'google_users.google_user_id')
	    // ->whereNotIn( 'entries.entry_category_id', $excludeCategory )
	    ->where( 'entries.entry_deleted', '=', '0' )
	    ->where( 'users.user_deleted', '=', '0' );
	    if( $session->token_app_version < 4 )
	    {
	        $entriesQuery = $entriesQuery->where( 'entries.entry_type', '<>', 'video_youtube' );
	    }
	    if( ! empty( $geoFilter ) )
	    {
	        $entriesQuery = $entriesQuery->whereIn( 'entries.entry_continent', $geoFilter );
	    }
	    if( ! empty( $categoryFilter ) )
	    {
	        $entriesQuery = $entriesQuery->whereIn( 'entries.entry_category_id', $categoryFilter );
	    }
	    $entriesQuery = $entriesQuery->where( function ( $query ) use ( $term )
	    {
	        $query->orWhere( 'entries.entry_name', 'LIKE', '%' . $term . '%' )
	        ->orWhere( 'entries.entry_description', 'LIKE', '%' . $term . '%' )
	        ->orWhere( 'users.user_name', 'LIKE', '%' . $term . '%' )
	        ->orWhere( 'users.user_full_name', 'LIKE', '%' . $term . '%' )
	        ->orWhere( 'facebook_users.facebook_user_display_name', 'LIKE', '%' . $term . '%' )
	        ->orWhere( 'facebook_users.facebook_user_user_name', 'LIKE', '%' . $term . '%' )
	        ->orWhere( 'google_users.google_user_display_name', 'LIKE', '%' . $term . '%' )
	        ->orWhere( 'google_users.google_user_user_name', 'LIKE', '%' . $term . '%' )
	        ->orWhere( 'comments.comment_content', 'LIKE','%' . $term . '%' );
	    } );
	    $results = $entriesQuery->groupBy('entry_id')
	    //->get();
	    ->take( $limit )->skip( $offset )->get();
	    $status_code = 200;


	    $usersQuery = DB::table( 'users' )
	    ->select( 'users.*' )
	    ->leftJoin('facebook_users', 'users.user_facebook_id', '=', 'facebook_users.facebook_user_id')
	    ->leftJoin('google_users', 'users.user_google_id', '=', 'google_users.google_user_id')
	    ->where( 'users.user_deleted', '=', '0' );
	    if( ! empty( $geoFilter ) )
	    {
	       $usersQuery = $usersQuery->whereIn( 'users.user_continent', $geoFilter );
	    }
	    $usersQuery = $usersQuery->where( function ( $query ) use ( $term )
	    {
	        $query->orWhere( 'users.user_name', 'LIKE', '%' . $term . '%' )
	        ->orWhere( 'users.user_full_name', 'LIKE', '%' . $term . '%' )
	        ->orWhere( 'facebook_users.facebook_user_display_name', 'LIKE', '%' . $term . '%' )
	        ->orWhere( 'facebook_users.facebook_user_user_name', 'LIKE', '%' . $term . '%' )
	        ->orWhere( 'google_users.google_user_display_name', 'LIKE', '%' . $term . '%' )
	        ->orWhere( 'google_users.google_user_user_name', 'LIKE', '%' . $term . '%' );
	    } );
	    //->get();
	    $results_user = $usersQuery->take( $limit )->skip( $offset )->get();
	    if(count($results_user) > 0)
	    {
	        // get user ids for prepare
	        $userIds = array();
	        for( $i = 0; $i < count( $results_user ); $i++ )
	        {
	            $userIds[] = $results_user[$i]->user_id;
	        }
	        UserHelper::prepareUsers( $userIds, array('votes', 'stars') );

	        for( $i = 0; $i < count( $results_user ); $i++ )
	        {
	            $current = array();
	            $current[ 'category' ] = 'onlyprofile';
	            $current[ 'user' ] = ResponseHelper::oneUser( $results_user[$i]->user_id, $session->token_user_id );
	            $return[ 'entries' ][ ][ 'entry' ] = $current;
	        }
	    }
	    // get entry ids and user ids for prepare
	    $userIds = $entryIds = array();
	    for( $i = 0; $i < count( $results ); $i++ )
	    {
	        if( $results[$i]->entry_deleted == 1 )
	        {
	            continue;
	        }
	        $entryIds[] = $results[$i]->entry_id;
	        $userIds[] = $results[$i]->entry_user_id;
	    }
	    UserHelper::prepareUsers( $userIds, array( 'votes', 'stars') );
	    EntryHelper::prepareEntries( $entryIds, array('commentCounts', 'filesInfo', 'tagNames', 'totalVotes', 'votedByUser') );

	    $sessionUserId = $session->token_user_id;

	    for( $i = 0; $i < count( $results ); $i++ )
	    {
	        if( $results[ $i ]->entry_deleted === 1 )
	        {
	            continue;
	        }
	        else
	        {
	            $entryId = $results[$i]->entry_id;
	            $entry = ResponseHelper::oneEntryNewById( $entryId, $sessionUserId, true );
	            if( ! $entry )
	                continue;

	            $return[ 'entries' ][ ][ 'entry' ] = $entry;
	        }
	    }
	    return Response::make( $return, $status_code );
	}


	/* Added by Anil for testing youtube upload and watermark symbol add in video */
	public function store2()
	{
	    markDead( __METHOD__ );

		$token = Request::header( "X-API-TOKEN" );
		$response = array();
		$session = $this->token->get_session( $token );

		//Validate Input
		$rules = array(
			'category'    => 'required|numeric',
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
				$entry_by_default_enable = DB::table( 'settings' )->where( 'vUniqueName', '=', 'ENTRY_DEFAULT' )->pluck( 'vSettingValue' );
				$categoryId = DB::table( 'categories' )->where( 'category_id', '=', Input::get( 'category' ) )->pluck( 'category_id' );
				if(empty($categoryId))
				{
					$response[ 'error' ] = "No category selected";
					$status_code = 400;
				}

				/* Commented for one Demo as on request 07 March 2015 // Removed Comment on 13 March 2015 as on request */
				if( Input::get( 'category' ) == 7 || Input::get( 'category' ) == 8 )
				{
					$entry_deleted = 0;
				}
				else
				{
					if($entry_by_default_enable && $entry_by_default_enable === 'TRUE')
					$entry_deleted = 0;
					else
					$entry_deleted = 1;
				}
				if( Input::get( 'category' ) == 3 )
				{
					$input = [
						'entry_user_id'      => $session->token_user_id,
						'entry_category_id'  => Input::get( 'category' ),
						'entry_type'         => Input::get( 'type' ),
						//'entry_name'         => preg_replace('/\\\\/', '', Input::get( 'name' )),
						//'entry_language'     => preg_replace('/\\\\/', '', Input::get( 'language' )),
						//'entry_description'  => preg_replace('/\\\\/', '', Input::get( 'description' )),
						'entry_name'         => str_replace('"', '', Input::get( 'name' )),
						'entry_language'     => str_replace('"', '', Input::get( 'language' )),
						'entry_description'  => str_replace('"', '', Input::get( 'description' )),
						'entry_created_date' => date( 'Y-m-d H:i:s' ),
						'entry_deleted'      => $entry_deleted,
						'entry_subcategory'  => Input::get( 'subCategory' ),
						'entry_age'          => Input::get( 'age' ),
						'entry_height'       => Input::get( 'height' ),
					];
				}
				else
				{
					$input = [
						'entry_user_id'      => $session->token_user_id,
						'entry_category_id'  => (Input::get( 'category' ) > 0) ? Input::get( 'category' ) : 1,
						'entry_type'         => Input::get( 'type' ),
						'entry_name'         => str_replace('"', '', Input::get( 'name' )),
						'entry_language'     => str_replace('"', '', Input::get( 'language' )),
						'entry_description'  => str_replace('"', '', Input::get( 'description' )),
						'entry_created_date' => date( 'Y-m-d H:i:s' ),
						'entry_deleted'      => $entry_deleted,
						'entry_subcategory'  => '',
						'entry_age'      	 => '',
						'entry_height'       => '',
					];
				}
				//Eloquent::unguard();
				//$response[ 'entry_id' ] = $this->entry->create( $input )->entry_id;
				$status_code = 201;
				//Eloquent::reguard();

				$tags = Input::get( 'tags' );

				/*if( isset( $tags ) )
				{
					$tags = array_values( explode( ',', $tags ) );

					foreach( $tags as $tag )
					{
						$this->entry->addTag( str_replace('"', '', $tag), $response[ 'entry_id' ], $session->token_user_id );
						//$this->entry->addTag( trim(preg_replace('/\\\\/', '', $tag)), $response[ 'entry_id' ], $session->token_user_id );
						//$this->entry->addTag( trim( $tag ), $response[ 'entry_id' ], $session->token_user_id );
					}
				}*/

				$dest = 'uploads';

				$filename = str_random( 12 );

				$extension = $file->getClientOriginalExtension();

//old method was just to move the file, and not encode it
//				$file->move($dest, $filename . '.' . $extension);

				if( $input[ 'entry_type' ] == 'audio' )
				{
					$file_in = $file->getRealPath();
					$file_out = Config::get('app.home') . 'public/uploads/' . $filename . '.mp3';

					// Transcode Audio
					shell_exec( Config::get( 'app.bin_ffmpeg' ).' -i ' . $file_in . ' -strict -2 ' . $file_out );

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

						$file_out = Config::get('app.home') . 'public/uploads/' . $filename . '.mp4';

						// Transcode Video
						shell_exec( Config::get( 'app.bin_ffmpeg' ).' -i ' . $file_in . ' -vf scale=306:306 -strict -2 ' . $file_out . ' 2>' . Config::get('app.home') . 'public/uploads/' . $filename . '-log.txt' );
						//shell_exec( Config::get( 'app.bin_ffmpeg' ).' -i ' . $file_in . ' -vsync 2 -vf scale=306:306 -strict -2 ' . $file_out . ' 2>' . Config::get('app.home') . 'public/uploads/' . $filename . '-log.txt' );

						$file->move( Config::get('app.home') . 'public/uploads/', $filename . '-uploaded.' . $extension );

						$extension = 'mp4';

						$handle = fopen( $file_out, "r" );

						Flysystem::connection( 'awss3' )->put( $filename . "." . $extension, fread( $handle, filesize( $file_out ) ) );

						$thumb = Config::get('app.home') . 'public/uploads/' . $filename . '-thumb.jpg';

						exec( Config::get( 'app.bin_ffprobe' ).' 2>&1 ' . $file_out . ' | grep "rotate          :"', $rotation );

						if( isset( $rotation[ 0 ] ) )
						{
							$rotation = substr( $rotation[ 0 ], 17 );
						}

						$contents = file_get_contents( Config::get('app.home') . 'public/uploads/' . $filename . '-log.txt' );
						preg_match( "#rotate.*?([0-9]{1,3})#im", $contents, $rotationMatches );

						$transpose = '';

						if( count( $rotationMatches ) > 0 )
						{
							switch( $rotationMatches[ 1 ] )
							{
								case '90':
									$transpose = ' -vf transpose=1';
									$rotation_angel = '90';
									break;
								case '180':
									$transpose = ' -vf vflip,hflip';
									$rotation_angel = '180';
									break;
								case '270':
									$transpose = ' -vf transpose=2';
									$rotation_angel = '270';
									break;
							}
						}

						shell_exec( Config::get( 'app.bin_ffmpeg' ).' -i ' . $file_out . $transpose . ' -vframes 1 -an -s 300x300 -ss 00:00:00.10 ' . $thumb );

						$handle = fopen( $thumb, "r" );

						Flysystem::connection( 'awss3' )->put( "thumbs/" . $filename . "-thumb.jpg", fread( $handle, filesize( $thumb ) ) );

						$pathfile = Config::get( 'app.home' ).'/public/uploads/'. $filename . '-uploaded.' . $extension;
						$serviceDetails = array();
						$serviceDetails["pathfile"] = $pathfile;
						$serviceDetails["rotation_angel"] = $rotation_angel;
						$serviceDetails["name"] = Input::get( 'name' );
						$serviceDetails["description"] = Input::get( 'description' );
						$serviceDetails["category"] = Input::get( 'category' );

						$this->backgroundPost('http://'.$_ENV['URL'].'/entry/youtubeUpload?jsonData='.urlencode(json_encode($serviceDetails)));
//						unlink($file_out);
//						unlink($thumb);
					}
					else
					{
						//File is an image

						$file_in = $file->getRealPath();

						$file_out = Config::get('app.home') . "public/uploads/" . $filename . '.' . $extension;

						$image = Image::make( $file_in );

						$image->widen( 350 );

						$image->save( $file_out );

						$handle = fopen( $file_out, "r" );

						Flysystem::connection( 'awss3' )->put( $filename . "." . $extension,
															   fread( $handle,
																	  filesize( $file_out ) ) );
					}
				}

				/*Eloquent::unguard();

				EntryFile::create( [
									   'entry_file_name'         => $filename,
									   'entry_file_entry_id'     => $response[ 'entry_id' ],
									   'entry_file_location'     => $dest,
									   'entry_file_type'         => $extension,
									   'entry_file_created_date' => date( 'Y-m-d H:i:s' ),
									   'entry_file_updated_date' => date( 'Y-m-d H:i:s' ),
								   ] );

				Eloquent::reguard();*/

				$file = Input::file( 'file2' );

				if( !empty( $file ) && $file->isValid() )
				{
					$dest = 'uploads';

					$file_in = $file->getRealPath();

					$extension = ".jpg";

					$file_out = Config::get('app.home') . "public/uploads/" . $filename . '.' . $extension;

					$image = Image::make( $file_in );

					$image->widen( 350 );

					$image->save( $file_out, 80 );

					$handle = fopen( $file_out, "r" );

					Flysystem::connection( 'awss3' )->put( $filename . "." . $extension,
														   fread( $handle,
																  filesize( $file_out ) ) );

					unlink( $file_out );

					/*Eloquent::unguard();

					EntryFile::create( [
										   'entry_file_name'         => $filename,
										   'entry_file_entry_id'     => $response[ 'entry_id' ],
										   'entry_file_location'     => $dest,
										   'entry_file_type'         => $extension,
										   'entry_file_created_date' => date( 'Y-m-d H:i:s' ),
										   'entry_file_updated_date' => date( 'Y-m-d H:i:s' ),
									   ] );

					Eloquent::reguard();*/
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
	// youtube upload
	public function youtubeUpload()
 	{
		if( Config::get('app.disable_youtube_upload') ) return;

		$serviceDetails = json_decode($_REQUEST['jsonData'], true);
		//mail('anil@spaceotechnologies.com','i am in_'.time(),print_r($serviceDetails,true));
		require_once Config::get( 'app.home' ).'/vendor/google-api-php-client-master/src/Google/autoload.php';
		// session_start();
		// Development
		//$OAUTH2_CLIENT_ID = '750620540831-68mufugc9vnh04qnm1f74qv98h696ljb.apps.googleusercontent.com';
		//$OAUTH2_CLIENT_SECRET = 'jXOGIdgad98FzkZ6pIhgxJmy';

		$OAUTH2_CLIENT_ID = '173877326502-4n4u9loil1dfrmppnik51elrrgn3m2t4.apps.googleusercontent.com';
		$OAUTH2_CLIENT_SECRET = 'V2BIjYMMFvy1vca_MjotO-jq';

		$client = new Google_Client();
		$client->setClientId($OAUTH2_CLIENT_ID);
		$client->setClientSecret($OAUTH2_CLIENT_SECRET);
		$client->setAccessType('offline');
		$client->setApprovalPrompt('force');
		$scope = array('https://www.googleapis.com/auth/youtube.upload', 'https://www.googleapis.com/auth/youtube', 'https://www.googleapis.com/auth/youtubepartner');
		$client->setScopes($scope);
		$redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
		    FILTER_SANITIZE_URL);
		$client->setRedirectUri($redirect);

		$youtube = new Google_Service_YouTube($client);

		$filename = 'youtubeToken.txt';
		$readData = '';

		if (isset($_GET['code']))
		{
		    $client->authenticate($_GET['code']);
		    $tokenData = $client->getAccessToken();
		    if(file_exists($filename))
		    {
		        $handler = fopen($filename, 'w+');
		        fwrite($handler, $tokenData);
		        fclose($handler);
		    }
		    else
		    {
		        touch($filename);
		        chmod($filename, 0777);
		        $newfile = fopen($filename, 'w+');
		        fwrite($newfile, $tokenData);
		        fclose($newfile);
		    }

		  header('Location: ' . $redirect);

		}
		if(file_exists($filename) && filesize($filename) > 0)
		{
		    $fp = fopen($filename, 'r');
		    $ftokenRead = json_decode(fread($fp,filesize($filename)),true);
		    fclose($fp);
		}

		if (!empty($ftokenRead) && is_array($ftokenRead)) {
		  $client->setAccessToken(json_encode($ftokenRead));
		}
		// Check to ensure that the access token was successfully acquired.
		if (!empty($ftokenRead))
		{
		  	if($client->isAccessTokenExpired())
		  	{
			    $newRefreshTokenData = $client->refreshToken($ftokenRead['refresh_token']);
			    $newTokenData = $client->getAccessToken();
			    $fpnew = fopen($filename, 'w+');
			    fwrite($fpnew, $newTokenData);
			    fclose($fpnew);
			    $client->setAccessToken($newTokenData);
		  	}

		  	try
		  	{
			    $videoPath_original = $serviceDetails["pathfile"];
				$file_out = Config::get('app.home') . 'public/uploads/'.time().'.mp4';
				if(isset($serviceDetails["rotation_angel"]) && $serviceDetails["rotation_angel"] != '')
				{
					if($serviceDetails["rotation_angel"] == '90')
					{
						$img_path = Config::get('app.home') . 'public/images/mob_img.png';
						shell_exec( Config::get( 'app.bin_ffmpeg' ).' -i '.$videoPath_original.' -vf "movie="'.$img_path.'" [watermark]; [in][watermark] overlay=10:10 [out]" '.$file_out);
						sleep(10);
						$videoPath = $file_out;
					}
					if($serviceDetails["rotation_angel"] == '180')
					{
						$img_path = Config::get('app.home') . 'public/images/mob_img180.png';
						shell_exec( Config::get( 'app.bin_ffmpeg' ).' -i '.$videoPath_original.' -vf "movie="'.$img_path.'" [watermark]; [in][watermark] overlay=10:main_h-overlay_h-10 [out]" '.$file_out);
						sleep(10);
						$videoPath = $file_out;
					}
					if($serviceDetails["rotation_angel"] == '270')
					{
						$img_path = Config::get('app.home') . 'public/images/mob_img_270.png';
						shell_exec( Config::get( 'app.bin_ffmpeg' ).' -i '.$videoPath_original.' -vf "movie="'.$img_path.'" [watermark]; [in][watermark] overlay=main_w-overlay_w-10:main_h-overlay_h-10 [out]" '.$file_out);
						sleep(10);
						$videoPath = $file_out;
					}
				}
				else
				{
					$img_path = Config::get('app.home') . 'public/images/watermark1.png';
					shell_exec( Config::get( 'app.bin_ffmpeg' ).' -i '.$videoPath_original.' -vf "movie="'.$img_path.'" [watermark]; [in][watermark] overlay=main_w-overlay_w-10:10 [out]" '.$file_out);
					sleep(10);
					$videoPath = $file_out;
				}
				$snippet = new Google_Service_YouTube_VideoSnippet();
			    $serviceDetails["description"] = preg_replace('/\\\u[0-9A-F]{4}/i', '',$serviceDetails["description"]);
				$snippet->setTitle($serviceDetails["name"]. ' - ' . $serviceDetails["description"]);
			    $snippet->setDescription($serviceDetails["description"]);
			    //$snippet->setTags(array("Yes", "No"));
				$snippet->setCategoryId("24");
				if($serviceDetails["category"] == 1)
				{
					$snippet->setCategoryId("10");
				}
				if($serviceDetails["category"] == 5)
				{
					$snippet->setCategoryId("17");
				}
				if($serviceDetails["category"] == 9)
				{
					$snippet->setCategoryId("23");
				}
			    $status = new Google_Service_YouTube_VideoStatus();
			    $status->privacyStatus = "private";

			    $video = new Google_Service_YouTube_Video();
			    $video->setSnippet($snippet);
			    $video->setStatus($status);

			    // Specify the size of each chunk of data, in bytes. Set a higher value for
			    // reliable connection as fewer chunks lead to faster uploads. Set a lower
			    // value for better recovery on less reliable connections.
			    $chunkSizeBytes = 1 * 1024 * 1024;
			    //$chunkSizeBytes = -1;

			    // Setting the defer flag to true tells the client to return a request which can be called
			    // with ->execute(); instead of making the API call immediately.
			    $client->setDefer(true);

		    	// Create a request for the API's videos.insert method to create and upload the video.

			    $insertRequest = $youtube->videos->insert("status,snippet",$video,
			      array("data"=>file_get_contents($videoPath),
			       "uploadType"=>"media",  // This was needed in my case
			       "mimeType" => "application/octet-stream",
			   ));

		    	// Create a MediaFileUpload object for resumable uploads.
			    $media = new Google_Http_MediaFileUpload(
			        $client,
			        $insertRequest,
			        'application/octet-stream',
			        null,
			        true,
			        $chunkSizeBytes
			    );
		    	$media->setFileSize(filesize($videoPath));
		    // Read the media file and upload it chunk by chunk.
			    $status = false;
			    $handle = fopen($videoPath, "rb");

			    while (!$status && !feof($handle)) {
			      $chunk = fread($handle, $chunkSizeBytes);
			      $status = $media->nextChunk($chunk);
			  	}

		    	fclose($handle);
		    // If you want to make other calls after the file upload, set setDefer back to false
		    $client->setDefer(false);
				mail('anil@spaceotechnologies.com', 'Success_'.time(),print_r($status['id'],true));
				//unlink($file_out);
			// REPLACE this value with the video ID of the video being updated.
		    	$videoId = $status['id'];

		    // Call the API's videos.list method to retrieve the video resource.
		   		$listResponse = $youtube->videos->listVideos("snippet,status",
		        array('id' => $videoId));



		    	//If $listResponse is empty, the specified video was not found.
			    if (empty($listResponse)) {
			      mail('anil@spaceotechnologies.com', 'video not found_'.time(),print_r($videoId,true));
				  //$htmlBody .= sprintf('<h3>Can\'t find a video with video id: %s</h3>', $videoId);
			    }
			    else
			    {
			      /* Added By AJ for update database entry with flag and video id */
					$entryId = $serviceDetails["entry_id"];
					if(isset($videoId) && !empty( $videoId ) && !empty($entryId))
					{
						$updateData = DB::table('entries')
								->where('entry_id','=',$entryId)
								->update(array('entry_uploaded_on_youtube' => 1,
									'entry_youtube_id' => $videoId));
					}
					/* End */

				  // Since the request specified a video ID, the response only
			      // contains one video resource.
			      $video = $listResponse[0];
			      $videoSnippet = $video['snippet'];
			      $videoStatus = $video['status'];

			      $tags = $videoSnippet['tags'];
			      $title = $videoSnippet['title'];
			      $description = $videoSnippet['description'];
			      $status = $videoStatus['privacyStatus'];

			      if (is_null($title) || $title == "unknown")
			      {
			        $serviceDetails["description"] = preg_replace('/\\\u[0-9A-F]{4}/i', '',$serviceDetails["description"]);
					$title = $serviceDetails["name"].' - '.$serviceDetails["description"];
			      }
			      if(is_null($description) || empty($description))
			      {
			        $description = $serviceDetails["description"];
			      }
			      if($status == 'public')
			      {
			        $status = "public";
			      }

				  ////// call for short url
				  $originalUrl = 'http://www.share.mobstar.com/info.php?id='.$entryId;
				  $shortedUrl = $this->get_tiny_url($originalUrl);
				  /////

			      // Set the tags array for the video snippet
			      $videoSnippet['tags'] = $tags;
			      $videoSnippet['title'] = $title;
			      $videoSnippet['description'] = $description.' '.$shortedUrl;
			      $videoStatus['privacyStatus'] = $status;

			      // Update the video resource by calling the videos.update() method.
			      $updateResponse = $youtube->videos->update("snippet,status", $video);
			    }

	  		} catch (Google_Service_Exception $e) {
	    		mail('anil@spaceotechnologies.com', 'Fail1_'.time(),print_r($e->getMessage(),true));
				//$htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
	        	//	htmlspecialchars($e->getMessage()));
		  	} catch (Google_Exception $e) {
		    	mail('anil@spaceotechnologies.com', 'Fail2_'.time(),print_r($e->getMessage(),true));
				//$htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
		        //	htmlspecialchars($e->getMessage()));
		  	}
		}
		else
		{
  			// If the user hasn't authorized the app, initiate the OAuth flow
			$authUrl = $client->createAuthUrl();
			mail('anil@spaceotechnologies.com', 'Authorization Required_'.time(),'You need to authorize access before proceeding.');
		}
		//return Response::make( $response, $status_code );
 	}

	/////// for Short URL

	public function get_tiny_url($url)
	{
	  $ch = curl_init();
	  $timeout = 5;
	  curl_setopt($ch,CURLOPT_URL,'http://tinyurl.com/api-create.php?url='.$url);
	  curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	  curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
	  $data = curl_exec($ch);
	  curl_close($ch);
	  return $data;
	}

	///////

	/**
	 * This function useful to create background process call
	 */
	public function backgroundPost($url){

		$parts = parse_url($url);
		//echo "<pre>"; print_r($parts); //exit;
		//mail('anil@spaceotechnologies.com','Backgroundcall_Called',print_r($parts,true));
		$fp = fsockopen($parts['host'],
			  isset($parts['port'])?$parts['port']:80,
			  $errno, $errstr, 30);
		if (!$fp) {
			return false;
		} else {
			$out = "POST ".$parts['path']." HTTP/1.1\r\n";
			$out.= "Host: ".$parts['host']."\r\n";
			$out.= "Content-Type: application/x-www-form-urlencoded\r\n";
			$out.= "Content-Length: ".strlen($parts['query'])."\r\n";
			$out.= "Connection: Close\r\n\r\n";

			if (isset($parts['query'])) $out.= $parts['query'];
			//mail('anil@spaceotechnologies.com','out_check',print_r($out,true));
			$rs = fwrite($fp, $out);
			//mail('anil@spaceotechnologies.com','fwrite_check',print_r($rs,true));
			fclose($fp);
			return true;
		}

	}
	/* End */

	/* Youtube delete */
	public function youtubeDelete()
 	{
		$serviceDetails = json_decode($_REQUEST['jsonData'], true);
		//mail('anil@spaceotechnologies.com','i am in_'.time(),print_r($serviceDetails,true));
		require_once Config::get( 'app.home' ).'/vendor/google-api-php-client-master/src/Google/autoload.php';
		// session_start();
		// Development
		//$OAUTH2_CLIENT_ID = '750620540831-68mufugc9vnh04qnm1f74qv98h696ljb.apps.googleusercontent.com';
		//$OAUTH2_CLIENT_SECRET = 'jXOGIdgad98FzkZ6pIhgxJmy';

		$OAUTH2_CLIENT_ID = '173877326502-4n4u9loil1dfrmppnik51elrrgn3m2t4.apps.googleusercontent.com';
		$OAUTH2_CLIENT_SECRET = 'V2BIjYMMFvy1vca_MjotO-jq';

		$client = new Google_Client();
		$client->setClientId($OAUTH2_CLIENT_ID);
		$client->setClientSecret($OAUTH2_CLIENT_SECRET);
		$client->setAccessType('offline');
		$client->setApprovalPrompt('force');
		$scope = array('https://www.googleapis.com/auth/youtube.upload', 'https://www.googleapis.com/auth/youtube', 'https://www.googleapis.com/auth/youtubepartner');
		$client->setScopes($scope);
		$redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
		    FILTER_SANITIZE_URL);
		$client->setRedirectUri($redirect);

		$youtube = new Google_Service_YouTube($client);

		$filename = 'youtubeToken.txt';
		$readData = '';

		if (isset($_GET['code']))
		{
		    $client->authenticate($_GET['code']);
		    $tokenData = $client->getAccessToken();
		    if(file_exists($filename))
		    {
		        $handler = fopen($filename, 'w+');
		        fwrite($handler, $tokenData);
		        fclose($handler);
		    }
		    else
		    {
		        touch($filename);
		        chmod($filename, 0777);
		        $newfile = fopen($filename, 'w+');
		        fwrite($newfile, $tokenData);
		        fclose($newfile);
		    }

		  header('Location: ' . $redirect);

		}
		if(file_exists($filename) && filesize($filename) > 0)
		{
		    $fp = fopen($filename, 'r');
		    $ftokenRead = json_decode(fread($fp,filesize($filename)),true);
		    fclose($fp);
		}

		if (!empty($ftokenRead) && is_array($ftokenRead)) {
		  $client->setAccessToken(json_encode($ftokenRead));
		}
		// Check to ensure that the access token was successfully acquired.
		if (!empty($ftokenRead))
		{
		  	if($client->isAccessTokenExpired())
		  	{
			    $newRefreshTokenData = $client->refreshToken($ftokenRead['refresh_token']);
			    $newTokenData = $client->getAccessToken();
			    $fpnew = fopen($filename, 'w+');
			    fwrite($fpnew, $newTokenData);
			    fclose($fpnew);
			    $client->setAccessToken($newTokenData);
		    }
		  	try
		  	{
				$videoid = $serviceDetails["videoid"];
				$entryId = $serviceDetails["entry_id"];
				$youtube->videos->delete($videoid);
				/* Added By AJ for update database entry with flag and video id */
				if(isset($videoid) && !empty( $videoid ) && !empty($entryId))
				{
					$updateData = DB::table('entries')
							->where('entry_id','=',$entryId)
							->update(array('entry_uploaded_on_youtube' => 0,
								'entry_youtube_id' => NULL));
				}
				mail('anil@spaceotechnologies.com', 'Delete Successfully_'.time(),print_r($videoid,true));
				/* End */
	  		} catch (Google_Service_Exception $e) {
	    		mail('anil@spaceotechnologies.com', 'Fail1_'.time(),print_r($e->getMessage(),true));
				//$htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
	        	//	htmlspecialchars($e->getMessage()));
		  	} catch (Google_Exception $e) {
		    	mail('anil@spaceotechnologies.com', 'Fail2_'.time(),print_r($e->getMessage(),true));
				//$htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
		        //	htmlspecialchars($e->getMessage()));
		  	}
		}
		else
		{
  			// If the user hasn't authorized the app, initiate the OAuth flow
			$authUrl = $client->createAuthUrl();
			mail('anil@spaceotechnologies.com', 'Authorization Required_'.time(),'You need to authorize access before proceeding.');
		}
		//return Response::make( $response, $status_code );
 	}


    public function processSplitVideoNotifications(
      $creatorUserId,
      $createdEntry,
      $usedEntryId
    )
    {
        //@todo do not send notification to yourself !!
        //@todo check that provided correct base video id
      $usedEntry = \Entry::find( $usedEntryId );
      $usedUserId = $usedEntry->entry_user_id;
      $createdEntryId = $createdEntry->entry_id;
      $creatorName = getusernamebyid( $creatorUserId );
      $notifType = 'splitScreen';
      $notifIcon = 'splitScreen.png';
      $msg = sprintf(
        'Your entry %s has been collaborated on by %s. Check it out...',
        $usedEntry->entry_description,
        $creatorName
      );

      $messageData = array(
        "creatorId" => $creatorUserId,
        "creatorName" => $creatorName,
        "createdEntryId" => $createdEntryId,
        "createdEntryName" => $createdEntry->entry_description,
        "usedEntryId" => $usedEntryId,
        "usedEntryName" => $usedEntry->entry_description,
        "Type" => $notifType,
      );

      Notification::create( [
        'notification_user_id'      => $usedUserId,
        'notification_subject_ids'  => json_encode( [ $messageData ] ),
        'notification_details'      => $msg,
        'notification_icon'			=> $notifIcon,
        'notification_read'         => 0,
        'notification_entry_id'     => $usedEntryId,
        'notification_type'         => $notifType,
        'notification_created_date' => date( 'Y-m-d H:i:s' ),
        'notification_updated_date' => date( 'Y-m-d H:i:s' ) ]
      );

      // update notification count
      // @todo need to rewrite, very complex and unclean
      $notificationcount = NotificationCount::firstOrNew( array('user_id' => $usedUserId ) );
      $notificationsCount = isset( $notificationcount->id ) ? $notificationcount->notification_count : 0;
      $notificationsCount++;
      $notificationcount->notification_count = $notificationsCount;
      $notificationcount->save();

      //recreate message data for Push notification
      $pushData = array(
        'Type' => $messageData['Type'],
        'badge' => intval( $notificationsCount ), // set count of messages to user
        'usedEntryName' => $messageData['usedEntryName'],
        'creatorName' => $messageData['creatorName'],
        'createdEntryId' => $messageData['createdEntryId'],
      );

      \MobStar\SnsHelper::sendNotification( $usedUserId, $msg, $pushData );
    }


    public static function sendPushNotification( $toUserId, $message, $data )
    {
      // get user device data
      $userDevices = DB::select( DB::raw("SELECT t1.* FROM
        (select device_registration_id,device_registration_device_type,device_registration_device_token,device_registration_date_created,device_registration_user_id
        from device_registrations where device_registration_device_token  != '' AND device_registration_device_token != 'mobstar'
        order by device_registration_date_created desc
      ) t1 left join users u on t1.device_registration_user_id = u.user_id
        where u.user_deleted = 0
        AND u.user_id = $toUserId
        order by t1.device_registration_date_created desc"
      ));

      if( ! empty( $userDevices ) )
      {
        for( $k=0; $k < count($userDevices); $k++ )
        {
          self::registerSNSEndpoint(
            $userDevices[$k],
            $message,
            $data
          );
        }
      }
    }

	/* End */
}
