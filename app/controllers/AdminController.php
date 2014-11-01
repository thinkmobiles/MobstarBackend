<?php

use MobStar\Storage\Entry\EntryRepository as Entry;
use MobStar\Storage\Token\TokenRepository as Token;

class AdminController extends BaseController
{
	public function __construct( Entry $entry, Token $token )
	{
		$this->entry = $entry;
		$this->token = $token;
	}

	public function index()
	{
		$data['entries'] = $this->entry->all();

		return View::make('hello', $data);
	}
}