<?php namespace MobStar\Storage\Token;

use Token;

class EloquentTokenRepository implements TokenRepository {
	
	public function get_session($token){
		$session = Token::where('token_value', '=', $token)->first();

		return $session;
	}
}