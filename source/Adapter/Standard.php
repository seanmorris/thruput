<?php
namespace SeanMorris\ThruPut\Adapter;
class Standard extends \SeanMorris\ThruPut\Adapter
{
	protected static $preventCookies = TRUE;

	public static function onRequest($request, &$uri, &$headers)
	{
		if(static::$preventCookies)
		{
			unset($headers['HTTP_COOKIE']);
		}
	}

	public static function onResponse($request, $response, $cached = FALSE)
	{
		if(static::$preventCookies)
		{
			foreach($response->header as $i => $header)
			{
				if(is_array($header) && $header[0] == 'Set-Cookie')
				{
					unset($response->header[$i]);
				}
			}
		}

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
