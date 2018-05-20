<?php
namespace SeanMorris\ThruPut\Adapter;
class Log extends \SeanMorris\ThruPut\Adapter
{
	public static function onRequest($request, &$uri, &$headers)
	{
		\SeanMorris\Ids\Log::info('Request', $uri);
		\SeanMorris\Ids\Log::debug($headers);
		\SeanMorris\Ids\Log::debug($request);
	}

	public static function onCache(&$cacheHash, $request, $uri, $response)
	{
		\SeanMorris\Ids\Log::info('Caching', $cacheHash);
		\SeanMorris\Ids\Log::debug($request, $response);
	}

	public static function onResponse($request, $response, $uri, $cached = FALSE)
	{
		\SeanMorris\Ids\Log::info(
			$cached ? 'Cached Response' : 'Fresh Response'
			, $cached
		);

		\SeanMorris\Ids\Log::debug($response);
	}
}
