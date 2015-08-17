<?php
//use Swagger\Annotations as SWG;

class BlogsController extends BaseController
{

/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$fields = array_values( explode( ',', Input::get( "fields" ) ) );

		if( $fields[ 0 ] == "" )
		{
			unset( $fields );
		}

		$return = [ ];
		$valid = false;

		if( !empty( $fields ) )
		{
			//Check if fields are valid
			foreach( $fields as $field )
			{
				if( !in_array( $field, $this->valid_fields ) )
				{
					$return[ 'errors' ][ ] = [ $field . " is not a valid field." ];
				}
				else
				{
					$valid = true;
				}
			}

		}

		//Get limit to calculate pagination
		$limit = ( Input::get( 'limit', '50' ) );

		//If not numeric set it to the default limit
		$limit = ( !is_numeric( $limit ) || $limit < 1 ) ? 5 : $limit;

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
		$count = Blog::count();

		if( $count == 0 )
		{
			$return = [ 'error' => 'No Blogs Found' ];
			$status_code = 404;

			return Response::make( $return, $status_code );
		}

		//If the count is greater than the highest number of items displayed show a next link
		elseif( $count > ( $limit * $page ) )
		{
			$next = true;
		}
		else
		{
			$next = false;
		}

		$blogs = Blog::all();

		foreach( $blogs as $blog )
		{

			$current = array();

			//check to see if fields were specified and at least one is valid
			if( ( !empty( $fields ) ) && $valid )
			{

				if( in_array( "id", $fields ) )
				{
					$current[ 'id' ] = $blog->iBlogId;
				}

				if( in_array( "blogTitle", $fields ) )
				{
					$current[ 'blogTitle' ] = $blog->vBlogTitle;
				}

				if( in_array( "blogHeader", $fields ) )
				{
					$current[ 'blogHeader' ] = $blog->vBlogHeader;
				}

				if( in_array( "blogImage", $fields ) )
				{
					if ( file_exists($blog->vBlogImage) )
						$current[ 'blogImage' ] = "http://" . $_ENV[ 'URL' ] . '/' . $blog->vBlogImage;
					else
						$current[ 'blogImage' ] = "http://".Config::get('app.url_admin')."/" . $blog->vBlogImage;
				}

				if( in_array( "description", $fields ) )
				{
					$current[ 'description' ] = $blog->txDescription;
				}

				if( in_array( "CreatedAt", $fields ) )
				{
					$current[ 'CreatedAt' ] = date('d/m/Y',strtotime($blog->tsCreatedAt));
				}

				$return[ 'blogs' ][ ][ 'blog' ] = $current;
			}

			else
			{

				$current[ 'id' ] = $blog->iBlogId;
				$current[ 'blogTitle' ] = $blog->vBlogTitle;
				$current[ 'blogHeader' ] = $blog->vBlogHeader;
				if ( file_exists($blog->vBlogImage) )
					$current[ 'blogImage' ] = "http://" . $_ENV[ 'URL' ] . '/' . $blog->vBlogImage;
				else
					$current[ 'blogImage' ] = "http://".Config::get('app.url_admin')."/" . $blog->vBlogImage;
				$current[ 'description' ] = $blog->txDescription;
				$current[ 'CreatedAt' ] = date('d/m/Y',strtotime($blog->tsCreatedAt));
				$return[ 'blogs' ][ ][ 'blog' ] = $current;
			}
		}

		$status_code = 200;

		//If next is true create next page link
		if( $next )
		{
			$return[ 'next' ] = "http://".$_ENV['URL']."/blogs/?" . http_build_query( [ "limit" => $limit, "page" => $page + 1 ] );
		}

		if( $previous )
		{
			$return[ 'previous' ] = "http://".$_ENV['URL']."/blogs/?" . http_build_query( [ "limit" => $limit, "page" => $page - 1 ] );
		}

		$response = Response::make( $return, $status_code );

		$response->header( 'X-Total-Count', $count );

		return $response;
	}

}
