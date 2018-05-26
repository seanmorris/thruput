<?php
namespace SeanMorris\ThruPut\Route;
class RootRoute implements \SeanMorris\Ids\Routable
{
	public function _dynamic($router)
	{
		session_write_close();

		return \SeanMorris\ThruPut\Request::handle(
			'http://isotope-frontend:3333/'
			//, 'SeanMorris\ThruPut\Client\Tor'
			, 'SeanMorris\ThruPut\Client\Standard'
			, [
				'SeanMorris\ThruPut\Adapter\Standard'
				, 'SeanMorris\ThruPut\Adapter\Log'
				// , 'SeanMorris\ThruPut\Adapter\Xpath'
				// , 'SeanMorris\ThruPut\Adapter\Plain'
			]
		);
	}
}

// [
// 	'SeanMorris\ThruPut\Adapter\Standard'
// 	'SeanMorris\ThruPut\Adapter\Cache' => [
// 		'expiry' => 3600
// 	]
// 	, 'SeanMorris\ThruPut\Adapter\Log'
// 	, 'SeanMorris\ThruPut\Adapter\Xpath'
// 	, 'SeanMorris\ThruPut\Adapter\Plain'
// ]
