<?php
namespace SeanMorris\ThruPut\Route;
class RootRoute implements \SeanMorris\Ids\Routable
{
	public function _dynamic($router)
	{
		session_write_close();

		return \SeanMorris\ThruPut\Request::handle(
			'http://dev.beta.thewhtrbt.com'
			, [
				'SeanMorris\ThruPut\Adapter\Standard'
				, 'SeanMorris\ThruPut\Adapter\Log'
			]
		);
	}
}
