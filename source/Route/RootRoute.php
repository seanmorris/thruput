<?php
namespace SeanMorris\ThruPut\Route;
class RootRoute implements \SeanMorris\Ids\Routable
{
	public function _dynamic($router)
	{
		session_write_close();

		return \SeanMorris\ThruPut\Request::handle(
			'https://dev.letsvue.com'
			, [
				'SeanMorris\ThruPut\Adapter\Standard'
				, 'SeanMorris\ThruPut\Adapter\Xpath'
				, 'SeanMorris\ThruPut\Adapter\Log'
			]
		);
	}
}
