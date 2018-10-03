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
	}

	public static function onCache(&$cacheHash, $request, $response, $uri)
	{
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
		$contentType = NULL;

		if(isset($response->header, $response->header->{'Content-Type'}))
		{
			$contentType = strtok($response->header->{'Content-Type'}, ';');
		}

		\SeanMorris\Ids\Log::debug($cached, $contentType);

		if($cached || $contentType !== 'text/html')
		{
			\SeanMorris\Ids\Log::debug('Aww...');
			return;
		}

		$renderer = new \SeanMorris\Ids\ChildProcess(
			'/home/sean/prenderer/stream.js'
		);

		$renderer->write($uri . PHP_EOL);

		$prerendered = json_decode($renderer->read());

		$response->body = $prerendered;

		\SeanMorris\Ids\Log::debug($prerendered);

		$dom = new \DomDocument;
		$dom->loadHTML($response->body);
		$xpath = new \DomXPath($dom);

		$nodes = $xpath->query('//meta[@name="x-thruput-http-code"]');

		$responseCode = 200;

		foreach ($nodes as $i => $node)
		{
			$responseCode = $node->getAttribute('content');
		}

		$replaceHeader = FALSE;

		foreach($response->header as $header => $value)
		{
			if($value !== NULL)
			{
				continue;
			}

			if(preg_match('/^HTTP\/\d+\.\d+\s+(\d+)/', $header, $groups))
			{
				$replaceHeader        = $header;
				$originalResponseCode = $groups[1];
				break;
			}
		}

		// if($originalResponseCode !== 200 || $responseCode !== 200)
		// {
		// 	\SeanMorris\ThruPut\Cache::delete($cacheHash);
		// 	return;
		// }

		if($replaceHeader)
		{
			unset($response->header->{$replaceHeader});
		}

		$response->header = [
			sprintf('HTTP/1.1 %d', $responseCode) => NULL
		] + (array) $response->header;

		\SeanMorris\Ids\Log::debug($replaceHeader, $response->header);

		\SeanMorris\ThruPut\Cache::store($cacheHash, (object)[
			'response'  => $response
			, 'request' => $request
		]);
	}
}
