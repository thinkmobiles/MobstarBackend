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
 *  resourcePath="/tag",
 *  basePath="http://api.mobstar.com"
 * )
 */
class TagController extends BaseController
{

	public $valid_fields = [ "id", "categoryName", "categoryDescription", "categoryActive", "mentors", "subCategories" ];

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */

	/**
	 *
	 * @SWG\Api(
	 *   path="/tag/",
	 *   description="Operation about Tags",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="View all tags",
	 *       notes="Shows all tags available.",
	 *       nickname="allTags",
	 *       @SWG\Parameters(
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
	 *          )
	 *       )
	 *     )
	 *   )
	 * )
	 */

	public function index()
	{
	    markDeprecated( __METHOD__ );

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

		//Find total number to put in header
		$count = Tag::count();

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

		$tags = Tag::take( $limit )->skip( $offset )->get();

		foreach( $tags as $tag )
		{

			//var_dump($category->mentors()->getResults());
			$current[ 'id' ] = $tag->tag_id;
			$current[ 'tagName' ] = $tag->tag_name;

			$return[ 'tags' ][ ][ 'tag' ] = $current;
		}

		$status_code = 200;

		//If next is true create next page link
		if( $next )
		{
			$return[ 'next' ] = "http://".$_ENV['URL']."/tag/?" . http_build_query( [ "limit" => $limit, "page" => $page + 1 ] );
		}

		if( $previous )
		{
			$return[ 'previous' ] = "http://".$_ENV['URL']."/tag/?" . http_build_query( [ "limit" => $limit, "page" => $page - 1 ] );
		}

		$response = Response::make( $return, $status_code );

		$response->header( 'X-Total-Count', $count );

		return $response;
	}

}