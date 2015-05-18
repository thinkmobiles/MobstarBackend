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
						// Added for make entry for push badge count
						$notification_count = 0;						
						$input = array(
									'user_id' => $starCheck->user_star_star_id,
								);

						$notificationcount = NotificationCount::firstOrNew( $input );
						if( isset( $notificationcount->id ) )
						{
							$notification_count = DB::table('notification_count')
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
							$usersDeviceData = DB::select( DB::raw("SELECT t1.* FROM 
								(select device_registration_id,device_registration_device_type,device_registration_device_token,device_registration_date_created,device_registration_user_id 
								from device_registrations where device_registration_device_token  != '' AND device_registration_device_token != 'mobstar'
								order by device_registration_date_created desc
								) t1 left join users u on t1.device_registration_user_id = u.user_id 
								where u.user_deleted = 0 
								AND u.user_id = $to
								order by t1.device_registration_date_created desc LIMIT 1"));

							if(!empty($usersDeviceData))
							{	
								$this->registerSNSEndpoint($usersDeviceData[0],$message,$to,$name);
							}
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
				// Added for make entry for push badge count
				$notification_count = 0;						
				$inputbadge = array(
							'user_id' => $star->user_star_star_id,
						);

				$notificationcount = NotificationCount::firstOrNew( $inputbadge );
				if( isset( $notificationcount->id ) )
				{
					$notification_count = DB::table('notification_count')
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
					$usersDeviceData = DB::select( DB::raw("SELECT t1.* FROM 
						(select device_registration_id,device_registration_device_type,device_registration_device_token,device_registration_date_created,device_registration_user_id 
						from device_registrations where device_registration_device_token  != '' AND device_registration_device_token != 'mobstar'
						order by device_registration_date_created desc
						) t1 left join users u on t1.device_registration_user_id = u.user_id 
						where u.user_deleted = 0 
						AND u.user_id = $to
						order by t1.device_registration_date_created desc LIMIT 1"));

					if(!empty($usersDeviceData))
					{	
						$this->registerSNSEndpoint($usersDeviceData[0],$message,$to,$name);
					}
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
	public function registerSNSEndpoint( $device , $message, $to=NULL, $name=NULL)
	{
		$badge_count = 0;
		$badge_count = DB::table('notification_count')
					->where('user_id','=',$record->entry_user_id)
					->pluck( 'notification_count' );
		if( $device->device_registration_device_type == "apple" )
		{
			$arn = "arn:aws:sns:eu-west-1:830026328040:app/APNS/adminpushdemo";
			//$arn = "arn:aws:sns:eu-west-1:830026328040:app/APNS_SANDBOX/adminsandbox";
		}
		else
		{
			$arn = "arn:aws:sns:eu-west-1:830026328040:app/GCM/admin-android-notification";
		}

		$sns = getSNSClient();

		$Model1 = $sns->listPlatformApplications();  
		
		$result1 = $sns->listEndpointsByPlatformApplication(array(
			// PlatformApplicationArn is required
			'PlatformApplicationArn' => $arn,
		));
		foreach($result1['Endpoints'] as $Endpoint){
			$EndpointArn = $Endpoint['EndpointArn']; 
			$EndpointToken = $Endpoint['Attributes'];
			foreach($EndpointToken as $key=>$newVals){
				if($key=="Token"){
					if($device->device_registration_device_token==$newVals){
					//Delete ARN
						$result = $sns->deleteEndpoint(array(
							// EndpointArn is required
							'EndpointArn' => $EndpointArn,
						));
					}
				}
			}
		}

		$result = $sns->createPlatformEndpoint(array(
			 // PlatformApplicationArn is required
			 'PlatformApplicationArn' => $arn,
			 // Token is required
			 'Token' => $device->device_registration_device_token,

		));

		$endpointDetails = $result->toArray();		 
		if($device->device_registration_device_type == "apple")
		{	
			if(!empty($to) && !empty($name))
			{	
				$publisharray = array(
					'TargetArn' => $endpointDetails['EndpointArn'],
					'MessageStructure' => 'json',
					 'Message' => json_encode(array(
						'default' => $message,
						//'APNS_SANDBOX' => json_encode(array(
						'APNS' => json_encode(array(
							'aps' => array(
								"sound" => "default",
								"alert" => $message,
								"badge"=> intval($badge_count),
								"userId"=>$to,
								"diaplayname"=>$name,
								"Type"=>"Follow",

							)
						)),
					))
				 );
			}
			else
			{
				$publisharray = array(
					'TargetArn' => $endpointDetails['EndpointArn'],
					'MessageStructure' => 'json',
					 'Message' => json_encode(array(
						'default' => $message,
						//'APNS_SANDBOX' => json_encode(array(
						'APNS' => json_encode(array(
							'aps' => array(
								"sound" => "default",
								"alert" => $message,
								"badge"=> intval($badge_count),
							)
						)),
					))
				 );
			}
		}
		else
		{
			if(!empty($to) && !empty($name))
			{
				$publisharray = array(
					'TargetArn' => $endpointDetails['EndpointArn'],
					'MessageStructure' => 'json',
					'Message' => json_encode(array(
						'default' => $message,
						'GCM'=>json_encode(array(
							'data'=>array(
								'message'=> $message,
								"badge"=> intval($badge_count),
								"userId"=>$to,
								"diaplayname"=>$name,
								"Type"=>"Follow"
							)
						))
					))
				);
			}
			else
			{
				$publisharray = array(
					'TargetArn' => $endpointDetails['EndpointArn'],
					'MessageStructure' => 'json',
					'Message' => json_encode(array(
						'default' => $message,
						'GCM'=>json_encode(array(
							'data'=>array(
								'message'=> $message,
								"badge"=> intval($badge_count)
							)
						))
					))
				);
			}
		}
		try
		{
			$sns->publish($publisharray);
			$myfile = 'sns-log.txt';
			file_put_contents($myfile, date('d-m-Y H:i:s') . ' debug log:', FILE_APPEND);
			file_put_contents($myfile, print_r($endpointDetails, true), FILE_APPEND);
		}   
		catch (Exception $e)
		{
			//print($endpointDetails['EndpointArn'] . " - Failed: " . $e->getMessage() . "!\n");
		}
	}
}