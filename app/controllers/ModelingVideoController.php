<?php

class ModelingVideoController extends BaseController 
{

/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function show()
	{
		$videoData = DB::table('modeling_video')->first();
		$url = $videoData->vModelingVideoURL;
		$text = $videoData->txDescription;
	
		$return['url'] = "http://admin.mobstar.com/uploads/modelingVideo/1/".$url;
		$return['text'] = $text;
		$response = Response::make($return, 200);

		return $response;
	}

}
