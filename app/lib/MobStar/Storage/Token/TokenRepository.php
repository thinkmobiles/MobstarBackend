<?php namespace MobStar\Storage\Token;
 
interface TokenRepository {
	
	public function get_session($token);

}