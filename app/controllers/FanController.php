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
class FanController extends BaseController
{

	public function __construct( Token $token )
	{
		$this->token = $token;
	}

	/**
	 *
	 * @SWG\Api(
	 *   path="/fan/feedback",
	 *   description="Operations for Fan Connect",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="GET",
	 *       summary="Get the users entries that have comments",
	 *       notes="Returns entries",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="period",
	 *           description="Current or All time.",
	 *           paramType="query",
	 *           required=false,
	 *           type="string",
	 *             enum="['current','allTime']"
	 *         )
	 *          ),
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

	public function comments()
	{
		$return = [ ];

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );
		$entries = DB::table( 'entries' )
					 ->select( 'entries.*', 'entry_files.*', 'comments.*' )
					 ->join( 'entry_files', 'entries.entry_id', '=', 'entry_files.entry_file_entry_id' )
					 ->join( 'comments', 'entries.entry_id', '=', 'comments.comment_entry_id' )
					 ->where( 'entries.entry_user_id', '=', $session->token_user_id )
					 ->where( 'entries.entry_deleted', '=', '0' )
					 ->where( 'comments.comment_deleted', '=', '0' )
					 ->where( 'entry_files.entry_file_deleted', '=', '0' )
					 ->groupBy('entries.entry_id')
					 ->orderBy( 'comments.comment_added_date', 'desc' )
					 ->get();
		$returning = [ ];

		$client = getS3Client();

		foreach( $entries as $entry )
		{
			if( !isset( $returning[ $entry->entry_id ] ) )
			{
				$current[ 'id' ] = $entry->entry_id;
				$current[ 'entryName' ] = $entry->entry_name;
				$current[ 'entryDescription' ] = $entry->entry_description;				
				if( $entry->entry_type == 'audio' || $entry->entry_type == 'image' )
				{
					if( strtolower( $entry->entry_file_type ) == 'png' || strtolower( $entry->entry_file_type ) == 'jpg' )
					{
						$current[ 'thumbnail' ] = $client->getObjectUrl( 'mobstar-1', $entry->entry_file_name . "." . $entry->entry_file_type, '+10 minutes' );
					}
				}
				else
				{
					$current[ 'thumbnail' ] = ( $entry->entry_file_type == "mp4" ) ?
						$client->getObjectUrl( 'mobstar-1', 'thumbs/' . $entry->entry_file_name . '-thumb.jpg', '+10 minutes' )
						: "";
				}
				$current[ 'lastComment' ] = $entry->comment_added_date;				
				$return[ 'entries' ] [ ] = $current;
				$returning[$entry->entry_id] = 0;
			}
		}
		$response = Response::make( $return, 200 );
		return $response;
	}

}