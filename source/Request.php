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
			$reqRes = $adapterClass::onRequest($request, $realUri, $headers);

			if($reqRes === FALSE)
			{
				return FALSE;
			}
		}

		if($cache)
		{
			foreach($adapters as $adapterClass)
			{
				$respRes = $adapterClass::onResponse(
					$request
					, $cache->meta->response
					, $cacheHash
				);

				if($respRes === FALSE)
				{
					return FALSE;
				}
			}

			static::sendHeaders($cache->meta->response->header);

			$cache->readOut(function($chunk){
				print $chunk;
			});

			die;
		}

		$response = static::curl($realUri, $headers);

		foreach($adapters as $adapterClass)
		{
			$cacheRes = $adapterClass::onCache(
				$cacheHash
				, $request
				, $response
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
				, FALSE
			);

			if($respRes === FALSE)
			{
				return FALSE;
			}
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

		$_headers = array_filter(
			array_map('trim', explode(PHP_EOL, $header))
		);

		$_headers = array_map(
			function($header) {
				return explode(': ', $header, 2);
			}
			, $_headers
		);

		foreach($_headers as $header)
		{
			$headers[$header[0]] = $header[1] ?? NULL;
		}

		curl_close($ch);

		$headers = (object) $headers;

		return (object)[
			'header' => $headers
			, 'body' => $body
		];
	}

	protected static function sendHeaders($headers)
	{
		foreach($headers as $headerName => $header)
		{
			if($headerName == 'Transfer-Encoding')
			{
				continue;
			}

			header(sprintf(
				'%s: %s' . PHP_EOL
				, $headerName
				, $header
			));
		}
	}
}