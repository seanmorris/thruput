<?php
namespace SeanMorris\ThruPut;
class Request
{
	public static function skeleton()
	{
		$parsedUrl   = parse_url($_SERVER['REQUEST_URI'] ?? NULL);
		$queryString = isset($parsedUrl['query']) ? $parsedUrl['query'] : NULL;

		parse_str($queryString, $query);

		return [
			'method'        => $_SERVER['REQUEST_METHOD'] ?? NULL
			, 'scheme'      => $_SERVER['REQUEST_SCHEME'] ?? NULL
			, 'host'        => $_SERVER['SERVER_NAME']    ?? NULL
			, 'path'        => $parsedUrl['path']         ?? NULL
			, 'port'        => $_SERVER['SERVER_PORT']    ?? NULL
			, 'query'       => $query                     ?? NULL
			, 'queryString' => $queryString               ?? NULL
		];
	}

	public static function handle($origin, $client, $adapters)
	{
		$request     = static::skeleton();
		$cacheHash   = sha1(json_encode($request));
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

		$realUri = sprintf(
			'%s%s?%s'
			, $origin
			, substr($request['path'], 1)
			, $request['queryString']
		);

		foreach($adaptersRev as $adapterClass)
		{
			$reqRes = $adapterClass::onRequest($request, $realUri, $headers);

			if($reqRes === FALSE)
			{
				return FALSE;
			}
		}

		if($cache)
		{
			\SeanMorris\Ids\Log::debug('CACHE HIT');

			\SeanMorris\Ids\Log::debug($cache);

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

			$return = '';

			$cache->readOut(function($chunk) use(&$return){
				$return .= $chunk;
			});
		}
		else
		{
			\SeanMorris\Ids\Log::debug('CACHE MISS', $headers);

			$response = $client::request($realUri);

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

			if($cacheRes !== FALSE)
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
