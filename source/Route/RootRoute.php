<?php
namespace SeanMorris\ThruPut\Route;
class RootRoute implements \SeanMorris\Ids\Routable
{
	public function _dynamic($router)
	{
		return \SeanMorris\ThruPut\Request::handle(
			'http://127.0.0.1:3333'
			, [
				'SeanMorris\ThruPut\Adapter\Standard'
				, 'SeanMorris\ThruPut\Adapter\Log'
			]
		);
	}
}
