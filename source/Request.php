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

	public static function handle($origin, $adapters)
	{
		$request     = static::skeleton();
		$cacheHash   = sha1(json_encode($request));
		$cache       = \SeanMorris\ThruPut\Cache::load($cacheHash);
		$adaptersRev = array_reverse($adapters);

		$headers = [];

		foreach($_SERVER as $k => $v)
		{
			if($k == 'HTTP_COOKIE')
			{
				continue;
			}

			if(substr($k, 0, 4) == 'HTTP')
			{
				$headers[$k] = $v;
			}
		}

		$realUri = sprintf(
			'%s%s?%s'
			, $origin
			, $request['path']
			, $request['queryString']
		);

		foreach($adaptersRev as $adapterClass)
		{
			$adapterClass::onRequest($request, $realUri);
		}

		if($cache)
		{
			foreach($adapters as $adapterClass)
			{
				$adapterClass::onResponse(
					$request
					, $cache->response
					, $cacheHash
				);
			}

			static::sendHeaders($cache->response->header);

			return $cache->response->body;
		}

		$response = static::curl($realUri, $headers);

		foreach($adapters as $adapterClass)
		{
			$adapterClass::onCache(
				$cacheHash
				, $request
				, $response
			);
		}

		\SeanMorris\ThruPut\Cache::store($cacheHash, (object)[
			'response'  => $response
			, 'request' => $request
		]);

		foreach($adaptersRev as $adapterClass)
		{
			$adapterClass::onResponse(
				$request
				, $response
				, FALSE
			);
		}

		static::sendHeaders($response->header);

		return $response->body;
	}

	protected static function curl($url, $headers = [])
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$response = curl_exec($ch);

		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$body = substr($response, $header_size);

		$headers = array_filter(
			array_map('trim', explode(PHP_EOL, $header))
		);

		$headers = array_map(
			function($header) {return explode(': ', $header, 2);}
			, $headers
		);

		curl_close($ch);

		return (object)[
			'header' => $headers
			, 'body' => $body
		];
	}

	protected static function sendHeaders($headers)
	{
		foreach($headers as $header)
		{
			if($header[0] == 'Transfer-Encoding')
			{
				continue;
			}
			if(is_array($header))
			{
				header(join(': ', $header) . PHP_EOL);
			}
			else
			{
				header($header);
			}
		}
	}
}