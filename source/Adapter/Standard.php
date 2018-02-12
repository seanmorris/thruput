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
			unset($response->header->{'Set-Cookie'});
		}

		if($response->header)
		{
			$response->header->{'X-THRUPUT-CACHE-HIT'} = $cached
				? 'TRUE'
				: 'FALSE'
			;

			$response->header->{'X-THRUPUT-CACHE-HASH'} = sha1(
				json_encode($request)
			);
		}
	}
}
