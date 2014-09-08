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
 *  resourcePath="/winner",
 *  basePath="http://api.mobstar.com"
 * )
 */
class WinnerController extends BaseController
{

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */

	/**
	 *
	 * @SWG\Api(
	 *   path="/winner/",
	 *   description="Get Winning Entries",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="View all winners",
	 *       notes="Shows all winners.",
	 *       nickname="allWinners",
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
		$count = WinningEntry::count();

		//If the count is greater than the highest number of items displayed show a next link
		if( $count > ( $limit * $page ) )
		{
			$next = true;
		}
		else
		{
			$next = false;
		}

		$winners = WinningEntry::take( $limit )->skip( $offset )->get();

		$return[ 'winners' ] = [ ];

		foreach( $winners as $winner )
		{
			//var_dump($category->mentors()->getResults());

			$current = [ ];

			$current[ 'winner' ][ 'entryId' ] = $winner->winning_entry_entry_id;
			$current[ 'winner' ][ 'strapLine' ] = $winner->winning_entry_strapline;
			$current[ 'winner' ][ 'entry' ][ 'userId' ] = $winner->entry()->getResults()->entry_user_id;
			$current[ 'winner' ][ 'entry' ][ 'category' ] = $winner->entry()->getResults()->category->category_name;
			$current[ 'winner' ][ 'entry' ][ 'type' ] = $winner->entry()->getResults()->entry_type;
			$current[ 'winner' ][ 'entry' ][ 'userName' ] = $winner->entry()->getResults()->user->user_display_name;
			$current[ 'winner' ][ 'entry' ][ 'entryName' ] = $winner->entry()->getResults()->entry_name;
			$current[ 'winner' ][ 'entry' ][ 'entryDescription' ] = $winner->entry()->getResults()->entry_description;
			$current[ 'winner' ][ 'entry' ][ 'created' ] = $winner->entry()->getResults()->entry_created_date;
			$current[ 'winner' ][ 'entry' ][ 'modified' ] = $winner->entry()->getResults()->entry_modified_date;

			$return[ 'winners' ][ ] = $current;
		}

		$status_code = 200;

		//If next is true create next page link
		if( $next )
		{
			$return[ 'next' ] = "http://api.mobstar.com/winner/?" . http_build_query( [ "limit" => $limit, "page" => $page + 1 ] );
		}

		if( $previous )
		{
			$return[ 'previous' ] = "http://api.mobstar.com/winner/?" . http_build_query( [ "limit" => $limit, "page" => $page - 1 ] );
		}

		$response = Response::make( $return, $status_code );

		$response->header( 'X-Total-Count', $count );

		return $response;
	}

}