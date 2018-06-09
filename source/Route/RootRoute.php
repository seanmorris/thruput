<?php
namespace SeanMorris\ThruPut\Route;
class RootRoute implements \SeanMorris\Ids\Routable
{
	public function _dynamic($router)
	{
		session_write_close();

		return \SeanMorris\ThruPut\Request::handle(
			\SeanMorris\Ids\Settings::read('origin')
			//, 'SeanMorris\ThruPut\Client\Tor'
			, 'SeanMorris\ThruPut\Client\Standard'
			, [
				'SeanMorris\ThruPut\Adapter\Standard'
				// , 'SeanMorris\ThruPut\Adapter\Xpath'
				// , 'SeanMorris\ThruPut\Adapter\MetaHttpHeader'
				// , 'SeanMorris\ThruPut\Adapter\Log'
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
