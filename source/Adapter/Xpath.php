<?php
namespace SeanMorris\ThruPut\Adapter;
class Xpath extends \SeanMorris\ThruPut\Adapter
{
	protected static $prefix = '';

	protected static function processors()
	{
		return [
			// '//body' => function($node, $index, $response) {
			// 	$node->nodeValue = sprintf(
			// 		'I was cached at %s!'
			// 		, date('h:i:s Y-m-d')
			// 	);
			// }
			// , '//a' => function($node, $index, $response) {
			// 	static::$prefix = trim($node->nodeValue) . PHP_EOL . static::$prefix;

			// 	\SeanMorris\Ids\Log::debug($node->nodeValue);
			// }
		];
	}

	public static function onResponse($request, $response, $uri, $cached = FALSE)
	{
	}

	public static function onRequest($request, &$uri, &$headers)
	{	
	}

	public static function onCache(&$cacheHash, $request, $response, $uri)
	{
		if(!$response->header
			|| (isset($response->body, $response->header->{'Content-Type'})
				&& $response->header->{'Content-Type'} == 'text/html; charset=UTF-8'
			)
		){
			$processors = static::processors();

			$dom = new \DomDocument;
			$dom->loadHTML($response->body);
			$dom->normalizeDocument();
			$xpath = new \DomXPath($dom);

			foreach($processors as $xQuery => $processor)
			{
				$nodes = $xpath->query($xQuery);
				foreach ($nodes as $i => $node)
				{
					$processor($node, $i, $response);
				}
			}

			$response->body = static::$prefix . PHP_EOL . $dom->saveHTML();
		}
	}
}
