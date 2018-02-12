<?php
namespace SeanMorris\ThruPut\Adapter;
class Xpath extends \SeanMorris\ThruPut\Adapter
{
	protected static function processors()
	{
		return [
			'//div[@class="messageContainer"]' => function($node) {
				$node->nodeValue = sprintf(
					'I was cached at %s!'
					, date('h:i:s Y-m-d')
				);
			}
		];
	}
	public static function onRequest($request, &$uri, &$headers)
	{
		
	}

	public static function onCache(&$cacheHash, $request, $response)
	{
		if($response->header->{'Content-Type'} == 'text/html; charset=UTF-8')
		{
			$processors = static::processors();

			$dom = new \DomDocument;
			$dom->loadHTML($response->body);
			$xpath = new \DomXPath($dom);

			foreach($processors as $xQuery => $processor)
			{
				$nodes = $xpath->query($xQuery);
				foreach ($nodes as $i => $node)
				{
					$processor($node, $i);
				}
			}

			$response->body = $dom->saveHTML();
		}
	}

	public static function onResponse($request, $response, $cached = FALSE)
	{
		// var_dump($response);die;
	}
}
