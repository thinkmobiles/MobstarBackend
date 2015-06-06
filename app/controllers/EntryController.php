<?php

use MobStar\Storage\Entry\EntryRepository as Entry;
use MobStar\Storage\Token\TokenRepository as Token;
use Swagger\Annotations as SWG;
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
			$votes = Vote::where( 'vote_user_id', '=', $session->token_user_id )->get();
			foreach( $votes as $vote )
			{
				$exclude[ ] = $vote->vote_entry_id;
			}
		}
		if( $order_by == 'popular' )
		{
			$entry_rank = DB::table( 'entries' )->where( 'entry_rank', '=', '0' )->get();
			foreach( $entry_rank as $rank )
			{
				$exclude[ ] = $rank->entry_id;
			}
		}
		/* Added for exclude MOBIT category in All entry list */
		$excludeCategory = array();
		if($category != 8 && $category != 7)
		{
			$excludeCategory = [7,8];			
			$entry_category = DB::table('entries')->whereIn( 'entry_category_id', $excludeCategory )->get();
			foreach( $entry_category as $c )
			{
				$exclude[ ] = $c->entry_id;
			}
		}
		if($category == 8)
		{
			$excludeCategory = [8];
			$entry_category = DB::table('entries')->whereNotIn( 'entry_category_id', $excludeCategory )->get();
			foreach( $entry_category as $c )
			{
				$exclude[ ] = $c->entry_id;
			}	
		}
		if($category == 7)
		{
			$excludeCategory = [7];
			$entry_category = DB::table('entries')->whereNotIn( 'entry_category_id', $excludeCategory )->get();
			foreach( $entry_category as $c )
			{
				$exclude[ ] = $c->entry_id;
			}	
		}
		/* End */
		$entries = $this->entry->all( $user, $category, $tag, $exclude, $order, $dir, $limit, $offset, false, true );
		//dd(DB::getQueryLog());
		$count = $this->entry->all( $user, $category, $tag, $exclude, $order, $dir, $limit, $offset, true );

		if( $count == 0 )
		{
			if( $user != 0 )
			{
				$user = User::find( $user );
				$current[ 'id' ] = null;
				$current[ 'user' ] = oneUser( $user, $session );
				$current[ 'user' ][ 'isMyStar' ] = Star::where( 'user_star_user_id', '=', $session->user_id )->where( 'user_star_star_id', '=', $user->user_id )->count();
				$current[ 'category' ] = null;
				$current[ 'type' ] = null;
				$current[ 'name' ] = null;
				$current[ 'description' ] = null;
				$current[ 'created' ] = null;
				$current[ 'modified' ] = null;

				$return[ 'entries' ][ ][ 'entry' ] = $current;
				/* Added By AJ for getting followrs */
				$starredBy = [ ];
				foreach( $user->StarredBy as $starred )
				{
					if( $starred->user_star_deleted == 0 )
					{
						$starNames = [];
						$starNames = userDetails($starred->User);

						$starredBy[ ] = [ 'starId'       => $starred->User->user_id,
										  'starName'     => @$starNames['displayName'],
										  'starredDate'  => $starred->user_star_created_date,
										  'profileImage' => ( isset( $starred->User->user_profile_image ) )
											  ? $client->getObjectUrl( 'mobstar-1', $starred->User->user_profile_image, '+720 minutes' )
											  : '',
										  'profileCover' => ( isset( $starred->User->user_cover_image ) )
										  ? $client->getObjectUrl( 'mobstar-1', $starred->User->user_cover_image, '+720 minutes' ) : '',	  
						];
					}
				}
				$return[ 'starredBy' ] = $starredBy;
				$return['fans'] = count($starredBy);
				/* End */
				$status_code = 200;

			}
			else
			{
				$return = [ 'error' => 'No Entries Found' ];
				$status_code = 404;
			}

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

			$current = array();
			//check to see if fields were specified and at least one is valid
			if( ( !empty( $fields ) ) && $valid )
			{

				if( in_array( "id", $fields ) )
				{
					$current[ 'id' ] = $entry->entry_id;
				}

				if( in_array( "user", $fields ) )
				{
					$current[ 'user' ][ 'userId' ] = $entry->entry_user_id;
					$current[ 'user' ][ 'userName' ] = $entry->User->user_name;
				}

				if( in_array( "userName", $fields ) )
				{

					$current[ 'user' ] = oneUser( $entry->User, $session );

				}

				if( in_array( "category", $fields ) )
				{
					$current[ 'category' ] = $entry->category->category_name;
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
					if(count($entry->file) <= 0)
					continue;
					foreach( $entry->file as $file )
					{

						$url = $client->getObjectUrl( 'mobstar-1', $file->entry_file_name . "." . $file->entry_file_type, '+720 minutes' );
						$current[ 'entryFiles' ][ ] = [
							'fileType' => $file->entry_file_type,
							'filePath' => $url ];

						$current[ 'videoThumb' ] = ( $file->entry_file_type == "mp4" ) ?
							$client->getObjectUrl( 'mobstar-1', 'thumbs/' . $file->entry_file_name . '-thumb.jpg', '+720 minutes' )
							: "";
					}
					if( ( count( $current[ 'entryFiles' ] ) < 2 ) &&  $entry->entry_type === 'audio' )
						continue;
					if( ( count( $current[ 'entryFiles' ] ) < 1 ) &&  $entry->entry_type === 'video' )
						continue;	
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
				$current[ 'user' ] = oneUser( $entry->User, $session );

//				$current[ 'user' ][ 'userId' ] = $entry->entry_user_id;
//				$current[ 'user' ][ 'userName' ] = $entry->User->user_name;
//				$current[ 'user' ][ 'displayName' ] = $entry->User->user_display_name;
//				$current[ 'user' ][ 'email' ] = $entry->User->user_email;
//				$current[ 'user' ][ 'profileImage' ] = ( !empty( $entry->user->user_profile_image ) )
//					? "http://" . $_ENV[ 'URL' ] . "/" . $entry->user->user_profile_image : "";
//				$current[ 'user' ][ 'profileCover' ] = ( !empty( $entry->User->user_profile_cover ) )
//					? "http://" . $_ENV[ 'URL' ] . "/" . $entry->User->user_profile_cover : "";
//				$current[ 'user' ][ 'isMyStar' ] = Star::where( 'user_star_user_id', '=', $session->user_id )->where( 'user_star_star_id', '=', $entry->entry_user_id )->count();
				if( isset( $entry->entry_category_id )  && $entry->entry_category_id == 3 )
				{
					$current[ 'subcategory' ] = $entry->entry_subcategory;
					$current[ 'age' ] = $entry->entry_age;
					$current[ 'height' ] = $entry->entry_height;
				}
				$current[ 'category' ] = $entry->category->category_name;
				$current[ 'type' ] = $entry->entry_type;
				$current[ 'name' ] = $entry->entry_name;
				$current[ 'description' ] = $entry->entry_description;
				$current[ 'totalComments' ] = $entry->comments->count();
				$current[ 'totalviews' ] = $entry->entryViews->count();
				$current[ 'created' ] = $entry->entry_created_date;
				$current[ 'modified' ] = $entry->entry_modified_date;

				$current[ 'tags' ] = array();
				foreach( $entry->entryTag as $entry_tag )
				{
					//TODO: Fix tags so that we do not need to find this
					$current[ 'tags' ][ ] = $entry_tag->tag->tag_name;
				}
				if(count($entry->file) <= 0)
				continue;
				foreach( $entry->file as $file )
				{

					$signedUrl = $client->getObjectUrl( 'mobstar-1', $file->entry_file_name . "." . $file->entry_file_type, '+720 minutes' );

					$current[ 'entryFiles' ][ ] = [
						'fileType' => $file->entry_file_type,
						'filePath' => $signedUrl ];

					$current[ 'videoThumb' ] = ( $file->entry_file_type == "mp4" ) ?
						$client->getObjectUrl( 'mobstar-1', 'thumbs/' . $file->entry_file_name . '-thumb.jpg', '+720 minutes' )
						: "";
				}
				if( ( count( $current[ 'entryFiles' ] ) < 2 ) &&  $entry->entry_type === 'audio' )
				{
					continue;
				}
				if( ( count( $current[ 'entryFiles' ] ) < 1 ) &&  $entry->entry_type === 'video' )
				{
					continue;
				}

				$current[ 'upVotes' ] = $up_votes;
				$current[ 'downVotes' ] = $down_votes;
				$current[ 'rank' ] = $entry->entry_rank;
				$current[ 'language' ] = $entry->entry_language;

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

		/* Added By AJ for getting followers */
		if( $user != 0 )
		{
			$aj = User::find( $user );
			$starredBy = [ ];
			foreach( $aj->StarredBy as $starred )
			{
				if( $starred->user_star_deleted == 0 )
				{
					$starNames = [];
					$starNames = userDetails($starred->User);

					$starredBy[ ] = [ 'starId'       => $starred->User->user_id,
									  'starName'     => @$starNames['displayName'],
									  'starredDate'  => $starred->user_star_created_date,
									  'profileImage' => ( isset( $starred->User->user_profile_image ) )
										  ? $client->getObjectUrl( 'mobstar-1', $starred->User->user_profile_image, '+720 minutes' )
										  : '',
									  'profileCover' => ( isset( $starred->User->user_cover_image ) )
									  ? $client->getObjectUrl( 'mobstar-1', $starred->User->user_cover_image, '+720 minutes' ) : '',	  
					];
				}
			}
			$return[ 'starredBy' ] = $starredBy;
			$return['fans'] = count($starredBy);
		}
		/* End */
		$status_code = 200;

		if( $debug !== false )
		{
			$return[ 'debug' ] = $debug;
		}
		//If next is true create next page link
		if( $next )
		{
			$return[ 'next' ] = url( "index.php/entry/?" . http_build_query( [ "limit" => $limit, "page" => $page + 1 ] ) );
		}

		if( $previous )
		{
			$return[ 'previous' ] = url( "index.php/entry/?" . http_build_query( [ "limit" => $limit, "page" => $page - 1 ] ) );
		}

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
					$current[ 'user' ][ 'isMyStar' ] = Star::where( 'user_star_user_id', '=', $session->user_id )->where( 'user_star_star_id', '=', $entry->entry_user_id )->count();

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
					if(count($entry->file) <= 0)
					continue;
					foreach( $entry->file as $file )
					{
						$url = $client->getObjectUrl( 'mobstar-1', $file->entry_file_name . "." . $file->entry_file_type, '+720 minutes' );
						$current[ 'entryFiles' ][ ] = [
							'fileType' => $file->entry_file_type,
							'filePath' => $url ];

						$current[ 'videoThumb' ] = ( $file->entry_file_type == "mp4" ) ?
							$client->getObjectUrl( 'mobstar-1', 'thumbs/' . $file->entry_file_name . '-thumb.jpg', '+720 minutes' )
							: "";
					}
					if( ( count( $current[ 'entryFiles' ] ) < 2 ) &&  $entry->entry_type === 'audio' )
						continue;
					if( ( count( $current[ 'entryFiles' ] ) < 1 ) &&  $entry->entry_type === 'video' )
						continue;
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
				if(count($entry->file) <= 0)
					continue;
				foreach( $entry->file as $file )
				{
					$url = $client->getObjectUrl( 'mobstar-1', $file->entry_file_name . "." . $file->entry_file_type, '+720 minutes' );
					$current[ 'entryFiles' ][ ] = [
						'fileType' => $file->entry_file_type,
						'filePath' => $url ];

					$current[ 'videoThumb' ] = ( $file->entry_file_type == "mp4" ) ?
						$client->getObjectUrl( 'mobstar-1', 'thumbs/' . $file->entry_file_name . '-thumb.jpg', '+720 minutes' )
						: "";
				}
				if( ( count( $current[ 'entryFiles' ] ) < 2 ) &&  $entry->entry_type === 'audio' )
					continue;
				if( ( count( $current[ 'entryFiles' ] ) < 1 ) &&  $entry->entry_type === 'video' )
					continue;
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
				$current[ 'totalviews' ] = $entry->entryViews->count();
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
			$return[ 'next' ] = "http://api.mobstar.com/entry/" . $id_commas . "?" . http_build_query( [ "limit" => $limit, "page" => $page + 1 ] );
		}

		if( $previous )
		{
			$return[ 'previous' ] = "http://api.mobstar.com/entry/" . $id_commas . "?" . http_build_query( [ "limit" => $limit, "page" => $page - 1 ] );
		}

		$response = Response::make( $return, $status_code );

		$response->header( 'X-Total-Count', $count );

		return $response;
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
						'entry_name'         => preg_replace('/\\\\/', '', Input::get( 'name' )),
						'entry_language'     => preg_replace('/\\\\/', '', Input::get( 'language' )),
						'entry_description'  => preg_replace('/\\\\/', '', Input::get( 'description' )),
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
						'entry_name'         => Input::get( 'name' ),
						'entry_language'     => Input::get( 'language' ),
						'entry_description'  => Input::get( 'description' ),
						'entry_created_date' => date( 'Y-m-d H:i:s' ),
						'entry_deleted'      => $entry_deleted,
						'entry_subcategory'  => '',
						'entry_age'      	 => '',
						'entry_height'       => '',
					];
				}
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
						$this->entry->addTag( trim(preg_replace('/\\\\/', '', $tag)), $response[ 'entry_id' ], $session->token_user_id );
						//$this->entry->addTag( trim( $tag ), $response[ 'entry_id' ], $session->token_user_id );
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
						//shell_exec( '/usr/bin/ffmpeg -i ' . $file_in . ' -vsync 2 -vf scale=306:306 -strict -2 ' . $file_out . ' 2>' . $_ENV[ 'PATH' ] . 'public/uploads/' . $filename . '-log.txt' );

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

		}

		return Response::make( $response, $status_code );
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

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$view = [
			'entry_view_entry_id' => $id,
			'entry_view_user_id'  => $session->token_user_id,
			'entry_view_date'     => date( 'Y-m-d H:i:s' ),
		];

		EntryView::create( $view );

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
		$entries = $this->entry->rerankall( $user, $category, 0, $exclude, 'entry_rank', 'asc', 10000, 0, false, true )->toArray();
		print_r($entries);
		die('here');
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
				//$entry->save();
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
			$url = $client->getObjectUrl( 'mobstar-1', $file->entry_file_name . "." . $file->entry_file_type, '+720 minutes' );
			$current[ 'entryFiles' ][ ] = [
				'fileType' => $file->entry_file_type,
				'filePath' => $url ];

			$current[ 'videoThumb' ] = ( $file->entry_file_type == "mp4" ) ?
				$client->getObjectUrl( 'mobstar-1', 'thumbs/' . $file->entry_file_name . '-thumb.jpg', '+720 minutes' )
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

		$this->entry->undelete( $id );

		return Response::make( [ 'status' => 'entry undeleted' ], 200 );
	}

	public function mysearch()
	{
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
		$totalviews = EntryView::where( 'entry_view_entry_id', '=', $entry->entry_id )->count();
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
			$url = $client->getObjectUrl( 'mobstar-1', $file->entry_file_name . "." . $file->entry_file_type, '+720 minutes' );
			$current[ 'entryFiles' ][ ] = [
				'fileType' => $file->entry_file_type,
				'filePath' => $url ];

			$current[ 'videoThumb' ] = ( $file->entry_file_type == "mp4" ) ?
				$client->getObjectUrl( 'mobstar-1', 'thumbs/' . $file->entry_file_name . '-thumb.jpg', '+720 minutes' )
				: "";
		}*/
		if(count($EntryFile) <= 0)
			return false;		
		foreach( $EntryFile as $file )
		{
			$url = $client->getObjectUrl( 'mobstar-1', $file->entry_file_name . "." . $file->entry_file_type, '+720 minutes' );
			$current[ 'entryFiles' ][ ] = [
				'fileType' => $file->entry_file_type,
				'filePath' => $url ];

			$current[ 'videoThumb' ] = ( $file->entry_file_type == "mp4" ) ?
				$client->getObjectUrl( 'mobstar-1', 'thumbs/' . $file->entry_file_name . '-thumb.jpg', '+720 minutes' )
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
		else
		{
			$viewcount = new EntryView;
			$viewcount->entry_view_entry_id = Input::get( 'entryId' );
			$viewcount->entry_view_user_id = Input::get( 'userId' );
			$viewcount->entry_view_date = date( 'Y-m-d H:i:s' );

			if( $viewcount->save() )
			{
				$return = [ 'notice' => 'success' ];
				$status_code = 200;
			}
			else
			{
				$return = [ 'error' => 'No Entries Found' ];
				$status_code = 404;
			}
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
			$votes = Vote::where( 'vote_user_id', '=', $session->token_user_id )->get();
			foreach( $votes as $vote )
			{
				$exclude[ ] = $vote->vote_entry_id;
			}
		}
		if( $order_by == 'popular' )
		{
			$entry_rank = DB::table('entries')->where( 'entry_rank', '=', '0')->get();
			foreach( $entry_rank as $rank )
			{
				$exclude[ ] = $rank->entry_id;
			}
		}
		$entries = $this->entry->all( $user, $category, $tag, $exclude, $order, $dir, $limit, $offset, false, true );
		//dd(DB::getQueryLog());
		$count = $this->entry->all( $user, $category, $tag, $exclude, $order, $dir, $limit, $offset, true );

		if( $count == 0 )
		{
			if( $user != 0 )
			{
				$user = User::find( $user );
				$current[ 'id' ] = null;
				$current[ 'user' ] = oneUser( $user, $session );
				$current[ 'user' ][ 'isMyStar' ] = Star::where( 'user_star_user_id', '=', $session->user_id )->where( 'user_star_star_id', '=', $user->user_id )->count();
				$current[ 'category' ] = null;
				$current[ 'type' ] = null;
				$current[ 'name' ] = null;
				$current[ 'description' ] = null;
				$current[ 'created' ] = null;
				$current[ 'modified' ] = null;

				$return[ 'entries' ][ ][ 'entry' ] = $current;
				/* Added By AJ for getting followrs */
				$starredBy = [ ];
				foreach( $user->StarredBy as $starred )
				{
					if( $starred->user_star_deleted == 0 )
					{
						$starNames = [];
						$starNames = userDetails($starred->User);

						$starredBy[ ] = [ 'starId'       => $starred->User->user_id,
										  'starName'     => @$starNames['displayName'],
										  'starredDate'  => $starred->user_star_created_date,
										  'profileImage' => ( isset( $starred->User->user_profile_image ) )
											  ? $client->getObjectUrl( 'mobstar-1', $starred->User->user_profile_image, '+720 minutes' )
											  : '',
										  'profileCover' => ( isset( $starred->User->user_cover_image ) )
										  ? $client->getObjectUrl( 'mobstar-1', $starred->User->user_cover_image, '+720 minutes' ) : '',	  
						];
					}
				}
				$return[ 'starredBy' ] = $starredBy;
				$return['fans'] = count($starredBy);
				/* End */
				$status_code = 200;	
			}
			else
			{
				$return = [ 'error' => 'No Entries Found' ];
				$status_code = 404;
			}

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

			$current = array();
			if($entry->entry_category_id == 7)
			{
				$votes = DB::table('votes')->where('vote_user_id', '=', $session->token_user_id)->where('vote_entry_id', '=', $entry->entry_id)->where('vote_deleted', '=', '0')->orderBy( 'vote_id','desc')->get();
				$isVotedByYou= 0;
				foreach( $votes as $v )
				{
					if($v->vote_up == 0)
					{
						$isVotedByYou = 0;
					}
					else
					{
						$isVotedByYou = 1;
					}					
				}
				$current[ 'isVotedByYou' ] = $isVotedByYou;	
			}
			//check to see if fields were specified and at least one is valid
			if( ( !empty( $fields ) ) && $valid )
			{

				if( in_array( "id", $fields ) )
				{
					$current[ 'id' ] = $entry->entry_id;
				}

				if( in_array( "user", $fields ) )
				{
					$current[ 'user' ][ 'userId' ] = $entry->entry_user_id;
					$current[ 'user' ][ 'userName' ] = $entry->User->user_name;
				}

				if( in_array( "userName", $fields ) )
				{

					$current[ 'user' ] = oneUser( $entry->User, $session );

				}

				if( in_array( "category", $fields ) )
				{
					$current[ 'category' ] = $entry->category->category_name;
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
					if(count($entry->file) <= 0)
					continue;
					foreach( $entry->file as $file )
					{

						$url = $client->getObjectUrl( 'mobstar-1', $file->entry_file_name . "." . $file->entry_file_type, '+720 minutes' );
						$current[ 'entryFiles' ][ ] = [
							'fileType' => $file->entry_file_type,
							'filePath' => $url ];

						$current[ 'videoThumb' ] = ( $file->entry_file_type == "mp4" ) ?
							$client->getObjectUrl( 'mobstar-1', 'thumbs/' . $file->entry_file_name . '-thumb.jpg', '+720 minutes' )
							: "";
					}
					if( ( count( $current[ 'entryFiles' ] ) < 2 ) &&  $entry->entry_type === 'audio' )
						continue;
					if( ( count( $current[ 'entryFiles' ] ) < 1 ) &&  $entry->entry_type === 'video' )
						continue;
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
				$current[ 'user' ] = oneUser( $entry->User, $session );
				if( isset( $entry->entry_category_id )  && $entry->entry_category_id == 3 )
				{
					$current[ 'subcategory' ] = $entry->entry_subcategory;
					$current[ 'age' ] = $entry->entry_age;
					$current[ 'height' ] = $entry->entry_height;
				}
				$current[ 'category' ] = $entry->category->category_name;
				$current[ 'type' ] = $entry->entry_type;
				$current[ 'name' ] = $entry->entry_name;
				$current[ 'description' ] = $entry->entry_description;
				$current[ 'totalComments' ] = $entry->comments->count();
				$current[ 'totalviews' ] = $entry->entryViews->count();
				$current[ 'created' ] = $entry->entry_created_date;
				$current[ 'modified' ] = $entry->entry_modified_date;

				$current[ 'tags' ] = array();
				foreach( $entry->entryTag as $entry_tag )
				{
					//TODO: Fix tags so that we do not need to find this
					$current[ 'tags' ][ ] = $entry_tag->tag->tag_name;
				}
				if(count($entry->file) <= 0)
					continue;
				foreach( $entry->file as $file )
				{

					$signedUrl = $client->getObjectUrl( 'mobstar-1', $file->entry_file_name . "." . $file->entry_file_type, '+720 minutes' );

					$current[ 'entryFiles' ][ ] = [
						'fileType' => $file->entry_file_type,
						'filePath' => $signedUrl ];

					$current[ 'videoThumb' ] = ( $file->entry_file_type == "mp4" ) ?
						$client->getObjectUrl( 'mobstar-1', 'thumbs/' . $file->entry_file_name . '-thumb.jpg', '+720 minutes' )
						: "";
				}
				if( ( count( $current[ 'entryFiles' ] ) < 2 ) &&  $entry->entry_type === 'audio' )
					continue;
				if( ( count( $current[ 'entryFiles' ] ) < 1 ) &&  $entry->entry_type === 'video' )
					continue;
				$current[ 'upVotes' ] = $up_votes;
				$current[ 'downVotes' ] = $down_votes;
				$current[ 'rank' ] = $entry->entry_rank;
				$current[ 'language' ] = $entry->entry_language;

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
		/* Added By AJ for getting followrs */
		if( $user != 0 )
		{
			$aj = User::find( $user );
			$starredBy = [ ];
			/*foreach( $aj->StarredBy as $starred )
			{
				if( $starred->user_star_deleted == 0 )
				{
					$starNames = [];
					$starNames = userDetails($starred->User);

					$starredBy[ ] = [ 'starId'       => $starred->User->user_id,
									  'starName'     => @$starNames['displayName'],
									  'starredDate'  => $starred->user_star_created_date,
									  'profileImage' => ( isset( $starred->User->user_profile_image ) )
										  ? $client->getObjectUrl( 'mobstar-1', $starred->User->user_profile_image, '+720 minutes' )
										  : '',
									  'profileCover' => ( isset( $starred->User->user_cover_image ) )
									  ? $client->getObjectUrl( 'mobstar-1', $starred->User->user_cover_image, '+720 minutes' ) : '',	  
					];
				}
			}
			$return[ 'starredBy' ] = $starredBy;
			$return['fans'] = count($starredBy);
			*/
			$return[ 'starredBy' ] = $starredBy;
			$return['fans'] = count($aj->StarredBy);
		}
		/* End */
		$status_code = 200;

		if($debug !== false)
			$return['debug'] = $debug;
		//If next is true create next page link
		if( $next )
		{
			$return[ 'next' ] = url( "index.php/entry/?" . http_build_query( [ "limit" => $limit, "page" => $page + 1 ] ) );
		}

		if( $previous )
		{
			$return[ 'previous' ] = url( "index.php/entry/?" . http_build_query( [ "limit" => $limit, "page" => $page - 1 ] ) );
		}

		$response = Response::make( $return, $status_code );

		$response->header( 'X-Total-Count', $count );

		return $response;
	}
	public function search3()
	{

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
		$file = Input::file( 'video' );

		if( !empty( $file ) )
		{
			$dest = 'uploads';

			$filename = 'Spaceo_'.time();

			$extension = $file->getClientOriginalExtension();
						
			$file_in = $file->getRealPath();
			
			$file_out = $_ENV[ 'PATH' ] . 'public/uploads/' . $filename . '.mp4';

			// Transcode Video
			shell_exec( '/usr/bin/ffmpeg -i ' . $file_in . ' -vf scale=306:306 -strict -2 ' . $file_out . ' 2>' . $_ENV[ 'PATH' ] . 'public/uploads/' . $filename . '-log.txt' );
			$file->move( $_ENV[ 'PATH' ] . 'public/uploads/', $filename . '-uploaded.' . $extension );

			$extension = 'mp4';

			$handle = fopen( $file_out, "r" );

			//Flysystem::connection( 'awss3' )->put( $filename . "." . $extension, fread( $handle, filesize( $file_out ) ) );

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
					 //->get();
					 ->take( $limit )->skip( $offset )->get();
		$status_code = 200;
		
		$results_user = DB::table( 'users' )
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
				 //->get();
				 ->take( $limit )->skip( $offset )->get();
		if(count($results_user) > 0)
		{
			for( $i = 0; $i < count( $results_user ); $i++ )
			{
				$User = User::where( 'user_id', '=', $results_user[$i]->user_id )->first();
				$current[ 'category' ] = 'onlyprofile';
				$current[ 'user' ] = oneUser( $User, $session );		 
				$return[ 'entries' ][ ][ 'entry' ] = $current;
			}				
		}			
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
		return Response::make( $return, $status_code );
	}

	
}