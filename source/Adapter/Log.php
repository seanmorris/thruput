<?php
namespace SeanMorris\ThruPut\Adapter;
class Log extends \SeanMorris\ThruPut\Adapter
{
	public static function onRequest($request, &$uri)
	{
		\SeanMorris\Ids\Log::info('Request', $uri);
		\SeanMorris\Ids\Log::debug($request);
	}

	public static function onCache(&$cacheHash, $request, $response)
	{
		\SeanMorris\Ids\Log::info('Caching');
		\SeanMorris\Ids\Log::debug(
			$cacheHash
			, $request
			, $response
		);
	}

	public static function onResponse($request, $response, $cached = FALSE)
	{
		\SeanMorris\Ids\Log::info(
			$cached ? 'Cached Response' : 'Response'
			, $cached
		);

		\SeanMorris\Ids\Log::debug($response);
	}
}
