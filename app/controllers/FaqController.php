<?php

use Swagger\Annotations as SWG;
use MobStar\Storage\Token\TokenRepository as Token;

/**
 * @package
 * @category
 * @subpackage
 *
 * @SWG\Resource(
 *  apiVersion=0.2,
 *  swaggerVersion=1.2,
 *  resourcePath="/faq",
 *  basePath="http://api.mobstar.com"
 * )
 */
class FaqController extends BaseController
{

	public function __construct( Token $token )
	{
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
	 *   path="/faq/",
	 *   description="Operation about FAQ's",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="Show all FAQ",
	 *       notes="Show all notifications for logged in user.",
	 *       nickname="allNotifications",
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
		$count = Faq::all()->count();

		//If the count is greater than the highest number of items displayed show a next link
		if( $count > ( $limit * $page ) )
		{
			$next = true;
		}
		else
		{
			$next = false;
		}

		$faqs = Faq::take( $limit )->skip( $offset )->get();

		$return[ 'faqs' ] = [ ];

		foreach( $faqs as $faq )
		{

			$current = [ ];

			$current[ 'faqId' ] = $faq->faq_id;
			$current[ 'faqQuestion' ] = $faq->faq_question;
			$current[ 'faqAnswer' ] = $faq->faq_answer;

			$return[ 'faqs' ][] = $current;
		}

		$status_code = 200;

		//If next is true create next page link
		if( $next )
		{
			$return[ 'next' ] = $_ENV['URL'] . "/faq/?" . http_build_query( [ "limit" => $limit, "page" => $page + 1 ] );
		}

		if( $previous )
		{
			$return[ 'previous' ] = $_ENV['URL'] . "/faq/?" . http_build_query( [ "limit" => $limit, "page" => $page - 1 ] );
		}

		$response = Response::make( $return, $status_code );

		$response->header( 'X-Total-Count', $count );

		return $response;
	}

}