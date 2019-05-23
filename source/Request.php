<?php
namespace SeanMorris\ThruPut;
class Request
{
	public static function skeleton($uri = NULL, $origin = NULL)
	{
		$parsedUrl    = parse_url($uri ?? $_SERVER['REQUEST_URI'] ?? NULL);
		$parsedOrigin = parse_url($origin ?? $uri);
		$queryString  = isset($parsedUrl['query']) ? $parsedUrl['query'] : NULL;

		parse_str($queryString, $query);

		$port = $parsedUrl['port']
			?? $_SERVER['port']
			?? NULL;

		if($origin)
		{
			$port = $parsedOrigin['port'] ?? NULL;
		}

		return [
			'method'        => 'GET'
			, 'scheme'      => $parsedOrigin['scheme'] ?? 'http'
			, 'host'        => $parsedOrigin['host']
			, 'path'        => $parsedUrl['path'] ?? NULL
			, 'port'        => $port
			, 'query'       => $query ?? NULL
			, 'queryString' => $queryString ?? NULL
		];
	}

	public static function uri($origin, $skeleton)
	{
		return sprintf(
			'%s/%s?%s'
			, $origin
			, substr($skeleton['path'], 1)
			, $skeleton['queryString']
		);
	}

	public static function handle($origin, $client, $adapters)
	{
		$request     = static::skeleton(NULL, $origin);
		$cacheHash   = \SeanMorris\ThruPut\Cache::hash($request);
		$cache       = \SeanMorris\ThruPut\Cache::load($cacheHash);
		$adaptersRev = array_reverse($adapters);

		$headers = [];

		foreach($_SERVER as $k => $v)
		{
			if(substr($k, 0, 4) == 'HTTP')
			{
				$headers[$k] = $v;
			}
		}

		$realUri = static::uri($origin, $request);

		foreach($adaptersRev as $adapterClass)
		{
			$reqRes = $adapterClass::onRequest($request, $realUri, $headers);

			if($reqRes === FALSE)
			{
				return FALSE;
			}
		}

		$return = '';

		$response = $client::request($realUri);

		$contentType = NULL;

		if(isset($response->header, $response->header->{'Content-Type'}))
		{
			$contentType = strtok($response->header->{'Content-Type'}, ';');
		}

		if($cache)
		{
			\SeanMorris\Ids\Log::debug('CACHE HIT!', $cacheHash);

			\SeanMorris\Ids\Log::debug((int)!!$cache);

			foreach($adapters as $adapterClass)
			{
				$respRes = $adapterClass::onResponse(
					$request
					, $cache->meta->response
					, $realUri
					, $cacheHash
					, TRUE
				);

				if($respRes === FALSE)
				{
					return FALSE;
				}
			}

			static::sendHeaders($cache->meta->response->header);

			$response = $cache->meta->response;

			$cache->readOut(function($chunk) use(&$return){
				$return .= $chunk;
			});

			return $return;
		}
		else
		{
			\SeanMorris\Ids\Log::debug('CACHE MISS', $headers);

			$cacheRes = NULL;

			foreach($adapters as $adapterClass)
			{
				$cacheRes = $adapterClass::onCache(
					$cacheHash
					, $request
					, $response
					, $realUri
				);

				if($cacheRes === FALSE)
				{
					break;
				}
			}

			if($cacheRes !== FALSE && $contentType === 'text/html')
			{
				\SeanMorris\ThruPut\Cache::store($cacheHash, (object)[
					'response'  => $response
					, 'request' => $request
				]);
			}

			foreach($adaptersRev as $adapterClass)
			{
				$respRes = $adapterClass::onResponse(
					$request
					, $response
					, $realUri
					, FALSE
				);

				if($respRes === FALSE)
				{
					$return = FALSE;
				}
			}

			static::sendHeaders($response->header);

			$return = $response->body;
		}

		\SeanMorris\Ids\Http\Http::onDisconnect(function()
			use($request, $response, $cache, $cacheHash, $adapters, $realUri){

			foreach($adapters as $adapterClass)
			{
				$cacheRes = $adapterClass::onDisconnect(
					$request, $response, $realUri, $cacheHash, $cache
				);
			}
		});

		return $return;
	}

	protected static function sendHeaders($headers)
	{
		foreach($headers as $headerName => $header)
		{
			if($headerName == 'Transfer-Encoding')
			{
				continue;
			}

			if(is_array($header))
			{
				$header = $header[0];
			}

			header(sprintf(
				'%s: %s' . PHP_EOL
				, $headerName
				, $header
			));
		}
	}
}
