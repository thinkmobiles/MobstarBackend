<?php namespace MobStar\Storage\Token;
 
interface TokenRepository {
	
	public function get_session($token);

	public function create_session($token);

}