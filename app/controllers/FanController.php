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

		$entries = Entry::where( 'entry_user_id', '=', $session->token_user_id )->has( 'comments' )->with( 'comments' )->get();

		$returning = [ ];

		$client = getS3Client();
		$sortArray = array();
		foreach( $entries as $entry )
		{
			if( !isset( $returning[ $entry->entry_id ] ) && ( count( $entry->comments ) > 0 ) )
			{
				$current[ 'id' ] = $entry->entry_id;
				$current[ 'entryName' ] = $entry->entry_name;

				foreach( $entry->file as $file )
				{

					if( $entry->entry_type == 'audio' || $entry->entry_type == 'image' )
					{
						if( strtolower( $file->entry_file_type ) == 'png' || strtolower( $file->entry_file_type ) == 'jpg' )
						{
							$current[ 'thumbnail' ] = $client->getObjectUrl( 'mobstar-1', $file->entry_file_name . "." . $file->entry_file_type, '+10 minutes' );
						}
					}
					else
					{
						$current[ 'thumbnail' ] = ( $file->entry_file_type == "mp4" ) ?
							$client->getObjectUrl( 'mobstar-1', 'thumbs/' . $file->entry_file_name . '-thumb.jpg', '+10 minutes' )
							: "";
					}
				}
				
				foreach( $entry->comments as $comment )
				{
					if( !isset( $current[ 'lastComment' ] ) )
					{
						$current[ 'lastComment' ] = $comment->comment_added_date;
					}
					elseif( $comment->comment_added_date > $current[ 'lastComment' ] )
					{
						$current[ 'lastComment' ] = $comment->comment_added_date;
					}

				}
				foreach( $entry->comments as $key => $value )
				{
					if( !isset( $sortArray[ $key ] ) )
					{
						$sortArray[ $key ] = array();
					}
					$sortArray[ $key ][ ] = $value;
				}
				$return[ 'entries' ] [ ] = $current;

				$returning[$entry->entry_id] = 0;
			}

		}
		$orderby = "lastComment"; //change this to whatever key you want from the array

		array_multisort( $sortArray[ $orderby ], SORT_DESC, $return[ 'entries' ] );
		
		$response = Response::make( $return, 200 );

		return $response;
	}

}