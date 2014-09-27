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
 *  resourcePath="/mentor",
 *  basePath="http://api.mobstar.com"
 * )
 */
class MentorController extends BaseController
{

	public $valid_fields = [ "id", "displayName", "firstName", "surname", "profilePicture", "video", "info", "categories" ];

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */

	/**
	 *
	 * @SWG\Api(
	 *   path="/mentor/",
	 *   description="Operation about mentors",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="View all mentors",
	 *       notes="Shows all mentors available.",
	 *       nickname="showMentor",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="fields",
	 *           description="Accepted values for the fields parameter are: id, displayName, firstName, surname, profilePicture, video, info, categories.",
	 *           paramType="query",
	 *           required=false,
	 *           type="comma seperated list"
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
	 *            message="Mentor not found"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function index()
	{

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
		$limit = ( Input::get( 'limit', '5' ) );

		//If not numeric set it to the default limit
		$limit = ( !is_numeric( $limit ) || $limit < 1 ) ? 5 : $limit;

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
		$count = Mentor::count();

		if( $count == 0 )
		{
			$return = [ 'error' => 'No Mentors Found' ];
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

		$mentors = Mentor::take( $limit )->skip( $offset )->get();

		foreach( $mentors as $mentor )
		{

			$current = array();

			//check to see if fields were specified and at least one is valid
			if( ( !empty( $fields ) ) && $valid )
			{

				if( in_array( "id", $fields ) )
				{
					$current[ 'id' ] = $mentor->mentor_id;
				}

				if( in_array( "displayName", $fields ) )
				{
					$current[ 'displayName' ] = $mentor->mentor_display_name;
				}

				if( in_array( "firstName", $fields ) )
				{
					$current[ 'firstName' ] = $mentor->mentor_first_name;
				}

				if( in_array( "surname", $fields ) )
				{
					$current[ 'surname' ] = $mentor->mentor_surname;
				}

				if( in_array( "profilePicture", $fields ) )
				{
					$current[ 'profilePicture' ] = "http://" . $_ENV[ 'URL' ] . '/' . $mentor->mentor_profile_picture;
				}

				if( in_array( "video", $fields ) )
				{
					$current[ 'video' ] = $mentor->mentor_video;
				}

				if( in_array( "info", $fields ) )
				{
					$current[ 'info' ] = $mentor->mentor_bio;
				}

				if( in_array( "mentors", $fields ) )
				{

					$categories = $mentor->categories()->getResults();

					foreach( $categories as $category )
					{
						$current[ 'category' ] = [
							'categoryId'   => $category->category_id,
							'categoryName' => $category->category_name,
						];
					}
				}

				$return[ 'mentors' ][ ][ 'mentor' ] = $current;
			}

			else
			{

				$current[ 'id' ] = $mentor->mentor_id;
				$current[ 'displayName' ] = $mentor->mentor_display_name;
				$current[ 'firstName' ] = $mentor->mentor_first_name;
				$current[ 'surname' ] = $mentor->mentor_surname;
				$current[ 'profilePicture' ] = "http://" . $_ENV[ 'URL' ] . '/' . $mentor->mentor_profile_picture;
				$current[ 'video' ] = $mentor->mentor_video;
				$current[ 'info' ] = $mentor->mentor_bio;

				//var_dump($category->mentors()->getResults());

				$categories = $mentor->categories()->getResults();

				foreach( $categories as $category )
				{
					$current[ 'category' ]= $category->category_name;

				}

				$return[ 'mentors' ][ ][ 'mentor' ] = $current;
			}
		}

		$status_code = 200;

		//If next is true create next page link
		if( $next )
		{
			$return[ 'next' ] = "http://api.mobstar.com/mentor/?" . http_build_query( [ "limit" => $limit, "page" => $page + 1 ] );
		}

		if( $previous )
		{
			$return[ 'previous' ] = "http://api.mobstar.com/mentor/?" . http_build_query( [ "limit" => $limit, "page" => $page - 1 ] );
		}

		$response = Response::make( $return, $status_code );

		$response->header( 'X-Total-Count', $count );

		return $response;
	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/mentor/{mentorIds}",
	 *   description="Operation about mentors",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="View mentor(s)",
	 *       notes="Shows all mentor available.",
	 *       nickname="showSpecificMentor",
	 *       @SWG\Parameters(
	 *			@SWG\Parameter(
	 *           name="mentorIds",
	 *           description="Mentor ID/IDs you want returned.",
	 *           paramType="path",
	 *           required=true,
	 *           type="comma seperated list"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="fields",
	 *           description="Accepted values for the fields parameter are: id, displayName, firstName, surname, profilePicture, video, info, categories.",
	 *           paramType="query",
	 *           required=false,
	 *           type="comma seperated list"
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
	 *            message="Mentor not found"
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function show( $id_commas )
	{

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
		$limit = ( Input::get( 'limit', '5' ) );

		//If not numeric set it to the default limit
		$limit = ( !is_numeric( $limit ) || $limit < 1 ) ? 5 : $limit;

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
		$count = Mentor::whereIn( 'mentor_id', $id )->count();

		if( $count == 0 )
		{
			$return = [ 'error' => 'No Categories Found' ];
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

		$mentors = Mentor::whereIn( 'mentor_id', $id )->take( $limit )->skip( $offset )->get();

		foreach( $mentors as $mentor )
		{

			$current = array();

			//check to see if fields were specified and at least one is valid
			if( ( !empty( $fields ) ) && $valid )
			{

				if( in_array( "id", $fields ) )
				{
					$current[ 'id' ] = $mentor->mentor_id;
				}

				if( in_array( "displayName", $fields ) )
				{
					$current[ 'displayName' ] = $mentor->mentor_display_name;
				}

				if( in_array( "firstName", $fields ) )
				{
					$current[ 'firstName' ] = $mentor->mentor_first_name;
				}

				if( in_array( "surname", $fields ) )
				{
					$current[ 'surname' ] = $mentor->mentor_surname;
				}

				if( in_array( "profilePicture", $fields ) )
				{
					$current[ 'profilePicture' ] = "http://" . $_ENV[ 'URL' ] . '/' . $mentor->mentor_profile_picture;
				}

				if( in_array( "video", $fields ) )
				{
					$current[ 'video' ] = $mentor->mentor_video;
				}

				if( in_array( "info", $fields ) )
				{
					$current[ 'info' ] = $mentor->mentor_bio;
				}

				if( in_array( "mentors", $fields ) )
				{

					$categories = $mentor->categories()->getResults();

					foreach( $categories as $category )
					{
						$current[ 'category' ] = [
							'categoryId'   => $category->category_id,
							'categoryName' => $category->category_name,
						];
					}
				}

				$return[ 'mentors' ][ ][ 'mentor' ] = $current;
			}

			else
			{

				$current[ 'id' ] = $mentor->mentor_id;
				$current[ 'displayName' ] = $mentor->mentor_display_name;
				$current[ 'firstName' ] = $mentor->mentor_first_name;
				$current[ 'surname' ] = $mentor->mentor_surname;
				$current[ 'profilePicture' ] = "http://" . $_ENV[ 'URL' ] . '/' . $mentor->mentor_profile_picture;
				$current[ 'video' ] = $mentor->mentor_video;
				$current[ 'info' ] = $mentor->mentor_bio;

				//var_dump($category->mentors()->getResults());

				$categories = $mentor->categories()->getResults();

				foreach( $categories as $category )
				{
					$current[ 'category' ]= $category->category_name;
				}

				$return[ 'mentors' ][ ][ 'mentor' ] = $current;
			}
		}

		$status_code = 200;

		//If next is true create next page link
		if( $next )
		{
			$return[ 'next' ] = "http://api.mobstar.com/mentor/" . $id_commas . "?" . http_build_query( [ "limit" => $limit, "page" => $page + 1 ] );
		}

		if( $previous )
		{
			$return[ 'previous' ] = "http://api.mobstar.com/mentor/" . $id_commas . "?" . http_build_query( [ "limit" => $limit, "page" => $page - 1 ] );
		}

		$response = Response::make( $return, $status_code );

		$response->header( 'X-Total-Count', $count );

		return $response;
	}
}