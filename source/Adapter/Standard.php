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

	public static function onResponse($request, $response, $uri, $scope, $cached = FALSE)
	{
		if($response
			&& isset($response->header)
			&& is_array($response->header)
		){
			$response->header = (object) $response->header;
		}
		else if(!$response || !isset($response->header))
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

		$contentType = NULL;

		if(isset($response->header, $response->header->{'Content-Type'}))
		{
			$contentType = strtok($response->header->{'Content-Type'}, ';');
		}

		\SeanMorris\Ids\Log::info($scope, $cached, $contentType);

		if(isset($scope->cache) && $scope->cache->meta('meta'))
		{
			\SeanMorris\Ids\Log::info($scope->cache->meta('meta'));
			$response->header->{'X-THRUPUT-TTL'} = $scope->cache->meta('meta')->expiry - time();
		}

		$contentType = NULL;

		if(isset($response->header, $response->header->{'Content-Type'}))
		{
			$contentType = strtok($response->header->{'Content-Type'}, ';');
		}

		$cachable = \SeanMorris\Ids\Settings::read('thruput', 'cachable');

		if(is_object($cachable) && method_exists($cachable, 'dumpStruct'))
		{
			$cachable = $cachable->dumpStruct();
		}

		if(!in_array($contentType, $cachable))
		{
			\SeanMorris\Ids\Log::debug('Aww...');
			return;
		}

		$returner = \SeanMorris\ThruPut\Queue\CacheWarmer::rpc($request);

		$time = time();

		while(TRUE)
		{
			if($message = $returner())
			{
				\SeanMorris\Ids\Log::error($response, $message);

				if(is_object($message)
					&& is_object($message)
					&& $message->body ?? FALSE
				){
					$response->body = $message->body;

					\SeanMorris\Ids\Log::error($response);
					break;
				}

				break;

			}

			if(time() - $time > 15)
			{
				break;
			}
		}
	}

	public static function onCache(&$cacheHash, $request, $response, $uri, $scope)
	{
		return;
		$contentType = NULL;

		if(isset($response->header, $response->header->{'Content-Type'}))
		{
			$contentType = strtok($response->header->{'Content-Type'}, ';');
		}

		if($contentType === 'text/html')
		{
			// return FALSE;
		}

		if(property_exists($response->header, 'HTTP/1.1 400 Bad Request'))
		{
		}
	}

	public static function onDisconnect($request, $response, $uri, $cacheHash, $cached = FALSE)
	{
		// \SeanMorris\ThruPut\Queue\CacheWarmer::send($request);
	}
}
