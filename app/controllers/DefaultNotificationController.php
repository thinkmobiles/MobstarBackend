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
class DefaultNotificationController extends BaseController
{

	public $valid_fields = [ "iDefaultNotificationId", "vDefaultNotificationTitle", "vDefaultNotificationImage", "txDescription" ];

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

		//Find total number to put in header
		$count = DefaultNotification::count();

		if( $count == 0 )
		{
			$return = [ 'error' => 'No Default Notification Found' ];
			$status_code = 404;

			return Response::make( $return, $status_code );
		}
		else
		{
			$settings = DB::table( 'settings' )->where( 'vUniqueName', '=', 'SHOW_SYSTEM_NOTIFICATION' )->pluck( 'vSettingValue' );
			
			$defaultNotifications = DefaultNotification::all();
			foreach( $defaultNotifications as $defaultNotification )
			{
				$current = array();

				$current[ 'id' ] = $defaultNotification->iDefaultNotificationId;
				$current[ 'defaultNotificationTitle' ] = $defaultNotification->vDefaultNotificationTitle;
				if ( file_exists($defaultNotification->vDefaultNotificationImage) )
					$current[ 'defaultNotificationImage' ] = "http://" . $_ENV[ 'URL' ] . '/' . $defaultNotification->vDefaultNotificationImage;
				else
					$current[ 'defaultNotificationImage' ] = "http://admin.mobstar.com/" . $defaultNotification->vDefaultNotificationImage;
				$current[ 'description' ] = $defaultNotification->txDescription;
				$current[ 'show_system_notification' ] = $settings;
			}
			
			$status_code = 200;
			
			return Response::make( $current, $status_code );
		}
		
	}
}