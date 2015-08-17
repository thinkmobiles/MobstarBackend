<?php
use Aws\S3\S3Client;
use Aws\Sns\SnsClient;
use Swagger\Annotations as SWG;

/**
 * @package
 * @category
 * @subpackage
 *
 * @SWG\Resource(
 *  apiVersion=0.2,
 *  swaggerVersion=1.2,
 *  resourcePath="/category",
 *  basePath="http://api.mobstar.com"
 * )
 */

class CategoryController extends BaseController {

	public $valid_fields = ["id", "categoryName", "categoryDescription", "categoryActive", "categoryIcon", "mentors"];

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */

	/**
     *
     * @SWG\Api(
     *   path="/category/",
     *   description="Operation about Categories",
     *   @SWG\Operations(
     *     @SWG\Operation(
     *       method="GET",
     *       summary="View all categories",
     *       notes="Shows all categories available.",
     *       nickname="allCateogries",
     *       @SWG\Parameters(
     *         @SWG\Parameter(
     *           name="fields",
     *           description="Accepted values for the fields parameter are: id, categoryName, categoryActive, categoryDescription, mentors.",
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
     *            message="Cateogry not found"
     *          )
     *       )
     *     )
     *   )
     * )
     */

	public function index()
	{
		$client = getS3Client();
		$fields = array_values(explode(',',Input::get("fields")));

		if ($fields[0] == "")
			unset($fields);

		$return = [];
		$valid = false;

		if(!empty($fields))
		{
			//Check if fields are valid
			foreach($fields as $field)
			{
				if(!in_array($field, $this->valid_fields))
					$return['errors'][] = [$field . " is not a valid field."];
				else
					$valid = true;
			}

		}
		$isMobIT = (Input::get('mobit', 'no'));
		$exclude = array();
		if($isMobIT=='yes')
			$exclude = [7];
		else
			$exclude = [7,8];
		//Get limit to calculate pagination
		$limit = (Input::get('limit', '50'));

		//If not numeric set it to the default limit
		$limit = (!is_numeric($limit) || $limit < 1) ? 50 : $limit;

		//Get page
		$page = (Input::get('page', '1'));
		$page = (!is_numeric($page)) ? 1 : $page;

		//Calculate offset
		$offset = ($page * $limit) - $limit;

		//If page is greter than one show a previous link
		if($page > 1)
			$previous = true;
		else
			$previous = false;

		//Find total number to put in header
		//$count =  Category::count();
		$count =  Category::whereNotIn('category_id', $exclude )->count();


		if($count == 0){
			$return = ['error' => 'No Categories Found'];
			$status_code = 404;
			return Response::make($return, $status_code);
		}

		//If the count is greater than the highest number of items displayed show a next link
		elseif($count > ($limit * $page))
			$next = true;
		else
			$next = false;
		//$orderby =  Category::whereNotIn('category_id', $exclude )->orderBy( 'category_id', 'asc' )->get();
		$orderby =  Category::whereNotIn('category_id', $exclude )->orderBy( 'category_active', 'desc' )->get();
		$ids= [];
		foreach( $orderby as $order )
		{
			$ids[] = $order->category_id;
		}
		array_unshift($ids , '8');
		$newOrderBy = '';
		$newOrderBy = implode(",",$ids);
		//$categories =  Category::take($limit)->skip($offset)->get();
		$categories =  Category::whereNotIn('category_id', $exclude )->orderByRaw(DB::raw("FIELD(category_id,$newOrderBy)"))->take($limit)->skip($offset)->get();


		foreach ($categories as $category){


			//check to see if fields were specified and at least one is valid
			if((!empty($fields)) && $valid)
			{
				$current = array();

				if(in_array("id",$fields))
					$current['id'] = $category->category_id;

				if(in_array("categoryName",$fields))
					$current['categoryName'] = $category->category_name;

				if(in_array("categoryActive",$fields))
					$current['categoryActive'] = ($category->category_active) ? true : false;

				if(in_array("categoryDescription",$fields))
					$current['categoryDescription'] = $category->category_description;

				if(in_array("categoryIcon",$fields) && !empty( $category->category_icon ))
					$current['categoryIcon'] = $client->getObjectUrl( Config::get('app.bucket'), $category->category_icon, '+60 minutes' );

				/*
				if(in_array("mentors",$fields)){

					$mentors = $category->mentors()->getResults();
					$current['mentors'] = array();

					foreach($mentors as $mentor)
					{
						$current['mentors'][] = [
							'mentorId' => $mentor->mentor_id,
							'mentorDisplayName' => $mentor->mentor_display_name,
							'mentorFirstName' => $mentor->mentor_first_name,
							'mentorSurname' => $mentor->mentor_surname,
							'mentorProfilePicture' => $mentor->mentor_profile_picture,
							'mentorVideo' => $mentor->mentor_video,
							'mentorInfo' => $mentor->mentor_bio,
							];
					}
				}*/

					$return['categories'][]['category'] = $current;
			}

			else
			{
				//var_dump($category->mentors()->getResults());
				$current['id'] = $category->category_id;
				$current['categoryName'] = $category->category_name;
				$current['categoryActive'] = ($category->category_active) ? true : false;
				$current['categoryDescription'] = $category->category_description;
				$current['categoryDescription'] = (!empty($category->category_icon)) ? $client->getObjectUrl( Config::get('app.bucket'), $category->category_icon, '+60 minutes' ) : '';
				/*
				$mentors = $category->mentors()->getResults();
				$current['mentors'] = array();

				foreach($mentors as $mentor)
				{
					$current['mentors'][] = [
						'mentorId' => $mentor->mentor_id,
						'mentorDisplayName' => $mentor->mentor_display_name,
						'mentorFirstName' => $mentor->mentor_first_name,
						'mentorSurname' => $mentor->mentor_surname,
						'mentorProfilePicture' => $mentor->mentor_profile_picture,
						'mentorVideo' => $mentor->mentor_video,
						'mentorInfo' => $mentor->mentor_bio,
						];

				}
				*/

				$return['categories'][]['category'] = $current;
			}
		}

		$status_code = 200;

		//If next is true create next page link
		if($next)
			$return['next'] = "http://".$_ENV['URL']."/category/?" . http_build_query(["limit" => $limit, "page" => $page+1]);

		if($previous)
			$return['previous'] = "http://".$_ENV['URL']."/category/?" . http_build_query(["limit" => $limit, "page" => $page-1]);

		$response = Response::make($return, $status_code);

		$response->header('X-Total-Count', $count);

		return $response;
	}

	/**
     *
     * @SWG\Api(
     *   path="/category/{categoryIds}",
     *   description="Operation about Categories",
     *   @SWG\Operations(
     *     @SWG\Operation(
     *       method="GET",
     *       summary="View specified categories",
     *       notes="Shows specifeid categories.",
     *       nickname="showSpecificCateogry",
     *       @SWG\Parameters(
     *			@SWG\Parameter(
     *           name="categoryIds",
     *           description="Category ID/IDs you want returned.",
     *           paramType="path",
     *           required=true,
     *           type="comma seperated list"
     *         ),
     *         @SWG\Parameter(
     *           name="fields",
     *           description="Accepted values for the fields parameter are: id, categoryName,  categoryActive, categoryDescription, mentors.",
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
     *            message="Cateogry not found"
     *          )
     *       )
     *     )
     *   )
     * )
     */



	public function show($id_commas)
	{

		$id = array_values(explode(',',$id_commas));

		$fields = array_values(explode(',',Input::get("fields")));

		if ($fields[0] == "")
			unset($fields);

		$return = [];
		$valid = false;

		if(!empty($fields))
		{
			//Check if fields are valid
			foreach($fields as $field)
			{
				if(!in_array($field, $this->valid_fields))
					$return['errors'][] = [$field . " is not a valid field."];
				else
					$valid = true;
			}
		}

		//Get limit to calculate pagination
		$limit = (Input::get('limit', '50'));

		//If not numeric set it to the default limit
		$limit = (!is_numeric($limit) || $limit < 1) ? 50 : $limit;

		//Get page
		$page = (Input::get('page', '1'));
		$page = (!is_numeric($page)) ? 1 : $page;

		//Calculate offset
		$offset = ($page * $limit) - $limit;

		//If page is greter than one show a previous link
		if($page > 1)
			$previous = true;
		else
			$previous = false;

		//Find total number to put in header
		$count =  Category::whereIn('category_id', $id)->count();


		if($count == 0){
			$return = ['error' => 'No Categories Found'];
			$status_code = 404;
			return Response::make($return, $status_code);
		}

		//If the count is greater than the highest number of items displayed show a next link
		elseif($count > ($limit * $page))
			$next = true;
		else
			$next = false;

		$categories =  Category::whereIn('category_id', $id)->take($limit)->skip($offset)->get();


		foreach ($categories as $category){


			//check to see if fields were specified and at least one is valid
			if((!empty($fields)) && $valid)
			{
				$current = array();

				if(in_array("id",$fields))
					$current['id'] = $category->category_id;

				if(in_array("categoryName",$fields))
					$current['categoryName'] = $category->category_name;

				if(in_array("categoryActive",$fields))
					$current['categoryActive'] = ($category->category_active) ? true : false;

				if(in_array("categoryDescription",$fields))
					$current['categoryDescription'] = $category->category_description;

				if(in_array("mentors",$fields)){

					$mentors = $category->mentors()->getResults();
					$current['mentors'] = array();

					foreach($mentors as $mentor)
					{
						$current['mentors'][] = [
							'mentorId' => $mentor->mentor_id,
							'mentorDisplayName' => $mentor->mentor_display_name,
							'mentorFirstName' => $mentor->mentor_first_name,
							'mentorSurname' => $mentor->mentor_surname,
							'mentorProfilePicture' => $mentor->mentor_profile_picture,
							'mentorVideo' => $mentor->mentor_video,
							'mentorInfo' => $mentor->mentor_bio,
							];

					}
				}


				$return['categories'][]['category'] = $current;
			}

			else
			{
				//var_dump($category->mentors()->getResults());
				$current['id'] = $category->category_id;
				$current['categoryName'] = $category->category_name;
				$current['categoryActive'] = ($category->category_active) ? true : false;
				$current['categoryDescription'] = $category->category_description;

				$mentors = $category->mentors()->getResults();
				$current['mentors'] = array();

				foreach($mentors as $mentor)
				{
					$current['mentors'][] = [
						'mentorId' => $mentor->mentor_id,
						'mentorDisplayName' => $mentor->mentor_display_name,
						'mentorFirstName' => $mentor->mentor_first_name,
						'mentorSurname' => $mentor->mentor_surname,
						'mentorProfilePicture' => $mentor->mentor_profile_picture,
						'mentorVideo' => $mentor->mentor_video,
						'mentorInfo' => $mentor->mentor_bio,
						];

				}

				$return['categories'][]['category'] = $current;
			}
		}

		$status_code = 200;

		//If next is true create next page link
		if($next)
			$return['next'] = "http://".$_ENV['URL']."/category/" . $id_commas . "?" . http_build_query(["limit" => $limit, "page" => $page+1]);

		if($previous)
			$return['previous'] = "http://".$_ENV['URL']."/category/" . $id_commas . "?" . http_build_query(["limit" => $limit, "page" => $page-1]);

		$response = Response::make($return, $status_code);

		$response->header('X-Total-Count', $count);

		return $response;
	}
}