<?php
namespace SeanMorris\ThruPut\Adapter;
class Standard extends \SeanMorris\ThruPut\Adapter
{
	public static function onResponse($request, $response, $cached = FALSE)
	{
		if($response->header)
		{
			$response->header[] = sprintf(
				'X-THRUPUT-CACHE-HIT: %s' . PHP_EOL
				, $cached ? 'TRUE' : 'FALSE'
			);

			$cacheHash = sha1(json_encode($request));

			$response->header[] = sprintf(
				'X-THRUPUT-CACHE-HASH: %s' . PHP_EOL
				, $cacheHash
			);
		}
	}
}
