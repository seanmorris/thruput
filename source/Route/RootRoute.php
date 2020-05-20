<?php
namespace SeanMorris\ThruPut\Route;
class RootRoute implements \SeanMorris\Ids\Routable
{
	public function _dynamic($router)
	{
		session_write_close();

		$response = \SeanMorris\ThruPut\Request::handle(
			\SeanMorris\Ids\Settings::read('origin')
			, 'SeanMorris\ThruPut\Client\Standard' // 'SeanMorris\ThruPut\Client\Tor'
			, \SeanMorris\Ids\Settings::read('thruput', 'adapters')
			// , [
			// 	'SeanMorris\ThruPut\Adapter\Standard'
			// 	, 'SeanMorris\ThruPut\Adapter\Xpath'
			// 	// , 'SeanMorris\ThruPut\Adapter\MetaHttpHeader'
			// 	// , 'SeanMorris\ThruPut\Adapter\Log'
			// 	// , 'SeanMorris\ThruPut\Adapter\Plain'
			// ]
		);

		header('Server: LETSVUE TECH 3');

		if($_SERVER['REQUEST_METHOD'] ?? 0 === 'OPTIONS')
		{
			header('Content-length: ' . count($response));
			header('Allow: OPTIONS, GET, HEAD, POST');
			return;
		}

		return $response;
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
