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
class StarController extends BaseController
{

	public function __construct( Token $token )
	{
		$this->token = $token;
	}

	/**
	 *
	 * @SWG\Api(
	 *    path="/star",
	 *   description="Operations about stars",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="POST",
	 *       summary="Add star",
	 *       notes="Operation for user to add a Star to their 'Stars' list.",
	 *       nickname="addStar",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="star",
	 *           description="The stars User ID.",
	 *           paramType="form",
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

	public function store()
	{

		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		//Validate Input
		$rules = array(
			'star' => 'required|numeric|exists:users,user_id',
		);

		$validator = Validator::make( Input::get(), $rules );

		if( $validator->fails() )
		{
			//var_dump($validator->messages());
			$response[ 'errors' ] = $validator->messages()->all();
			$status_code = 400;
		}
		else
		{

			//Get input
			$starsCheck = Star::where( 'user_star_star_id', '=', Input::get( 'star' ) )->where( 'user_star_user_id', '=', $session->token_user_id )->get();
			$count = Star::where( 'user_star_star_id', '=', Input::get( 'star' ) )->where( 'user_star_user_id', '=', $session->token_user_id )->count();
			if($count > 0)
			{
				foreach( $starsCheck as $starCheck )
				{
					if( isset( $starCheck->user_star_deleted ) && $starCheck->user_star_deleted == 1 )
					{
						$starCheck->user_star_deleted = 0;
						$starCheck->save();
						$userid = $session->token_user_id;
						$name = getusernamebyid($userid);
						$to = $starCheck->user_star_star_id;
						// For Notification Table entry
						$prev_not = Notification::where( 'notification_user_id', '=', Input::get( 'star' ), 'and' )
										->where( 'notification_entry_id', '=', $session->token_user_id, 'and' )
										->where( 'notification_details', '=', ' is now following you.', 'and' )
										->orderBy( 'notification_updated_date', 'desc' )
										->first();

						if( !count( $prev_not ) )
						{
							Notification::create( [ 'notification_user_id'      => Input::get( 'star' ),
													'notification_subject_ids'  => json_encode( [ $session->token_user_id ] ),
													'notification_details'      => ' is now following you.',
													'notification_icon'			=> 'follow.png',
													'notification_read'         => 0,
													'notification_entry_id'     => $session->token_user_id,
													'notification_type'         => 'Follow',
													'notification_created_date' => date( 'Y-m-d H:i:s' ),
													'notification_updated_date' => date( 'Y-m-d H:i:s' )
												  ] );
						}
						else
						{

							$subjects = json_decode( $prev_not->notification_subject_ids );

							if( !in_array( $session->token_user_id, $subjects ) )
							{
								array_push( $subjects, $session->token_user_id );

								$prev_not->notification_subject_ids = json_encode( $subjects );
								$prev_not->notification_read = 0;
								$prev_not->notification_updated_date = date( 'Y-m-d H:i:s' );

								$prev_not->save();
							}

						}
						// End For Notification Table entry
						// Added for make entry for push badge count
						$notification_count = 0;
						$input = array(
									'user_id' => $starCheck->user_star_star_id,
								);

						$notificationcount = NotificationCount::firstOrNew( $input );
						if( isset( $notificationcount->id ) )
						{
							$notification_count = DB::table('notification_counts')
								->where('user_id','=',$starCheck->user_star_star_id)
								->pluck( 'notification_count' );
							$notification_count = $notification_count + 1;
							$notificationcount->notification_count = $notification_count;
							$notificationcount->save();
						}
						else
						{
							$notificationcount->notification_count = 1;
							$notificationcount->save();
						}
						// End
						if(!empty($name))
						{
						    $message = $name." is now following you.";
						    $icon = 'follow.png';
						    $icon = 'http://' . $_ENV[ 'URL' ] . '/images/' . $icon;
						    $pushMessage = $message;
						    $pushData = array(
						        "badge"=> (int)$notification_count,
						        "userId"=>$to,
						        "diaplayname"=>$name,
						        "Type"=>"Follow",
						        "notificationIcon"=>$icon,
						    );

						    \MobStar\SnsHelper::sendNotification( $to, $pushMessage, $pushData );
						}
					}
					else
					{
						return Response::make( [ 'error' => 'Already a star' ], 403 );
					}
				}
			}
			else
			{
				$input = array(
					'user_star_user_id' => $session->token_user_id,
					'user_star_star_id' => Input::get( 'star' ),
					'user_star_deleted' => 0,
				);

				$star = Star::firstOrNew( $input );
				if( isset( $star->user_star_created_date ) )
				{
					return Response::make( [ 'error' => 'Already a star' ], 403 );
				}
				$star->user_star_created_date = date( 'Y-m-d H:i:s' );
				$star->save();
				$userid = $session->token_user_id;
				$name = getusernamebyid($userid);
				$to = $star->user_star_star_id;
				//$to = $starCheck->user_star_star_id;
				// For Notification Table entry
				$prev_not = Notification::where( 'notification_user_id', '=', Input::get( 'star' ), 'and' )
								->where( 'notification_entry_id', '=', $session->token_user_id, 'and' )
								->where( 'notification_details', '=', ' is now following you.', 'and' )
								->orderBy( 'notification_updated_date', 'desc' )
								->first();

				if( !count( $prev_not ) )
				{
					Notification::create( [ 'notification_user_id'      => Input::get( 'star' ),
											'notification_subject_ids'  => json_encode( [ $session->token_user_id ] ),
											'notification_details'      => ' is now following you.',
											'notification_icon'			=> 'follow.png',
											'notification_read'         => 0,
											'notification_entry_id'     => $session->token_user_id,
											'notification_type'         => 'Follow',
											'notification_created_date' => date( 'Y-m-d H:i:s' ),
											'notification_updated_date' => date( 'Y-m-d H:i:s' )
										  ] );
				}
				else
				{

					$subjects = json_decode( $prev_not->notification_subject_ids );

					if( !in_array( $session->token_user_id, $subjects ) )
					{
						array_push( $subjects, $session->token_user_id );

						$prev_not->notification_subject_ids = json_encode( $subjects );
						$prev_not->notification_read = 0;
						$prev_not->notification_updated_date = date( 'Y-m-d H:i:s' );

						$prev_not->save();
					}

				}
				// End For Notification Table entry
				// Added for make entry for push badge count
				$notification_count = 0;
				$inputbadge = array(
							'user_id' => $star->user_star_star_id,
						);

				$notificationcount = NotificationCount::firstOrNew( $inputbadge );
				if( isset( $notificationcount->id ) )
				{
					$notification_count = DB::table('notification_counts')
						->where('user_id','=',$star->user_star_star_id)
						->pluck( 'notification_count' );
					$notification_count = $notification_count + 1;
					$notificationcount->notification_count = $notification_count;
					$notificationcount->save();
				}
				else
				{
					$notificationcount->notification_count = 1;
					$notificationcount->save();
				}
				// End
				if(!empty($name))
				{
					$message = $name." is now following you.";
					$icon = 'follow.png';
					$icon = 'http://' . $_ENV[ 'URL' ] . '/images/' . $icon;

					$pushMessage = $message;
					$pushData = array(
					    "badge"=> (int)$notification_count,
					    "userId"=>$to,
					    "diaplayname"=>$name,
					    "Type"=>"Follow",
					    "notificationIcon"=>$icon,
					);

					\MobStar\SnsHelper::sendNotification( $to, $pushMessage, $pushData );
				}
			}
			$response[ 'message' ] = "star added";
			$status_code = 201;
		}

		return Response::make( $response, $status_code );
	}

	/**
	 *
	 * @SWG\Api(
	 *   description="Operations about Stars",
	 *   path="/star/",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       method="DELETE",
	 *       summary="Remove star",
	 *       notes="Operation for user to remove a user from their list of 'Stars'",
	 *       nickname="removeStar",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="star",
	 *           description="The stars User ID.",
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
	public function destroy( $id )
	{
		$token = Request::header( "X-API-TOKEN" );

		$session = $this->token->get_session( $token );

		$stars = Star::where( 'user_star_star_id', '=', $id )->where( 'user_star_user_id', '=', $session->token_user_id )->get();

		foreach( $stars as $star )
		{
			$star->user_star_deleted = 1;
			$star->save();
		}

		$response[ 'message' ] = "star removed";
		$status_code = 200;

		return Response::make( $response, $status_code );
	}
}
