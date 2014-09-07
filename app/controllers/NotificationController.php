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
 *  resourcePath="/winner",
 *  basePath="http://api.mobstar.com"
 * )
 */
class NotificationController extends BaseController
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
	 *   path="/winner/",
	 *   description="Get Notifications",
	 *   produces="['application/json']",
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

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

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
		$count = Notification::where( 'notification_user_id', '=', $session->token_user_id )->count();

		//If the count is greater than the highest number of items displayed show a next link
		if( $count > ( $limit * $page ) )
		{
			$next = true;
		}
		else
		{
			$next = false;
		}

		$notifications = Notification::where( 'notification_user_id', '=', $session->token_user_id )->take( $limit )->skip( $offset )->get();

		$return[ 'notifications' ] = [ ];

		foreach( $notifications as $notification )
		{

			$current = [ ];

			$subjects = json_decode( $notification->notification_subject_ids, true );

			$subject_count = count( $subjects );

			if( $subject_count > 3 )
			{
				$line = "and " . ( $subject_count - 2 ) . " others " . $notification->notification_details;
			}
			else
			{
				if( $subject_count == 3 )
				{
					$line = "and " . ( $subject_count - 2 ) . " other " . $notification->notification_details;
				}
				else
				{
					$line = $notification->notification_details;
				}
			}

			$name_ids = [ ];

			array_push( $name_ids, array_pop( $subjects ) );

			if( isset( $subjects ) )
			{
				array_push( $name_ids, array_pop( $subjects ) );
			}

			$names = User::whereIn( 'user_id', $name_ids )->get();

			$nameArray = [ ];
			foreach( $names as $name )
			{
				$nameArray[ ] = $name->user_display_name;
			}

			if( $subject_count > 2 )
			{
				$names = implode( ', ', $nameArray );
			}
			else
			{
				$names = implode( ' and ', $nameArray );
			}

			$line = $names . ' ' . $line;

			$current[ 'notificationId' ] = $notification->notification_entry_id;
			$current[ 'notificationContent' ] = $line;
			$current[ 'notificationDate' ] = $notification->notification_updated_date;
			$current[ 'notificationRead' ] = ($notification->notification_read == 1);

			$return[ 'notifications' ][] = $current;
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