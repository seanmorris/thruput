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
		$adaptersRev = array_reverse($adapters ?? []);

		$headers = [];

		foreach($_SERVER as $k => $v)
		{
			if(substr($k, 0, 4) == 'HTTP')
			{
				$headers[$k] = $v;
			}
		}

		$realUri = static::uri($origin, $request);

		foreach($adapters as $adapterClass)
		{
			$reqRes = $adapterClass::onRequest($request, $realUri, $headers);

			if($reqRes === FALSE)
			{
				return FALSE;
			}
		}

		$return = '';

		if($cache)
		{
			\SeanMorris\Ids\Log::debug('CACHE HIT!', $cacheHash);

			if($adapters)
			{
				$scope = (object) [];

				foreach($adapters as $adapterClass)
				{
					\SeanMorris\Ids\Log::error('CACHE HIT!', $adapterClass);
					
					$respRes = $adapterClass::onResponse(
						$request
						, $cache->meta('response')
						, $realUri
						, $scope
						, $cacheHash
					);

					if($respRes === FALSE)
					{
						return FALSE;
					}
				}
			}

			static::sendHeaders($cache->meta('response')->header);

			$response = $cache->meta('response');

			$cache->readOut(function($chunk) use(&$return){
				$return .= $chunk;
			});

			return $return;
		}
		else
		{
			\SeanMorris\Ids\Log::debug('CACHE MISS', $headers);

			$response = $client::request($realUri);

			$contentType = NULL;

			if(isset($response->header, $response->header->{'Content-Type'}))
			{
				$contentType = strtok($response->header->{'Content-Type'}, ';');
			}

			$cacheRes = NULL;

			if($cacheRes !== FALSE && in_array($contentType, \SeanMorris\Ids\Settings::read('thruput', 'cacheableTypes') ?? ['text/html']))
			{
				\SeanMorris\ThruPut\Cache::store(
					$cacheHash
					, (object)[
						'response'  => $response
						, 'request' => $request
						, 'realUri' => $realUri
					]
					, \SeanMorris\Ids\Settings::read('cacheTime') ?? 60
				);
			}

			$scope = (object) [];

			foreach($adapters as $adapterClass)
			{
				\SeanMorris\Ids\Log::error('CACHE HIT!', $adapterClass, $response,$scope);

				$respRes = $adapterClass::onResponse(
					$request
					, $response
					, $realUri
					, $scope
					, FALSE
				);

				if($respRes === FALSE)
				{
					$return = FALSE;
				}
			}

			\SeanMorris\Ids\Log::debug($response);

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
		$suppress = \SeanMorris\Ids\Settings::read('thruput', 'suppressHeaders') ?? [];

		foreach($headers as $headerName => $header)
		{
			if($headerName == 'Transfer-Encoding')
			{
				continue;
			}

			if(in_array($headerName, $suppress))
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
