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

	public static function onResponse($request, $response, $uri, $cached = FALSE)
	{
		if(is_array($response->header))
		{
			$response->header = (object) $response->header;
		}
		else if(!$response)
		{
			$response->header = (object) [];
		}

		if(static::$preventCookies)
		{
			unset($response->header->{'Set-Cookie'});
		}

		$response->header->{'X-THRUPUT-CACHE-HIT'} = $cached
			? 'TRUE'
			: 'FALSE'
		;

		$response->header->{'X-THRUPUT-CACHE-HASH'} = sha1(
			json_encode($request)
		);
	}

	public static function onCache(&$cacheHash, $request, $response, $uri)
	{
		if(property_exists($response->header, 'HTTP/1.1 400 Bad Request'))
		{
			return FALSE;
		}
	}

	public static function onDisconnect($request, $response, $uri, $cacheHash, $cached = FALSE)
	{
		$contentType = NULL;

		if(isset($response->header, $response->header->{'Content-Type'}))
		{
			$contentType = strtok($response->header->{'Content-Type'}, ';');
		}

		\SeanMorris\Ids\Log::debug($cached, $contentType);

		if($cached || $contentType !== 'text/html')
		{
			return;
		}

		$prendererCommand = sprintf(
			'prenderer %s --timeout=5000'
			, escapeshellarg($uri)
		);

		\SeanMorris\Ids\Log::debug('prend start');
		\SeanMorris\Ids\Log::debug($prendererCommand);
		$prerendered = `$prendererCommand`;
		\SeanMorris\Ids\Log::debug($prerendered);
		\SeanMorris\Ids\Log::debug('prend done');

		$_response = clone $response;

		$_response->body = $prerendered;

		\SeanMorris\ThruPut\Cache::store($cacheHash, (object)[
			'response'  => $_response
			, 'request' => $request
		]);
	}
}
