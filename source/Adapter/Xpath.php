<?php
namespace SeanMorris\ThruPut\Adapter;
class Xpath extends \SeanMorris\ThruPut\Adapter
{
	protected static $prefix = '';

	protected static function responseProcessors(){ return []; }
	protected static function requestProcessors(){ return []; }
	protected static function cacheProcessors(){ return []; }
	// {
	// 	return [
	// 		// '//body' => function($node, $index, $response) {
	// 		// 	$node->nodeValue = sprintf(
	// 		// 		'I was cached at %s!'
	// 		// 		, date('h:i:s Y-m-d')
	// 		// 	);
	// 		// }
	// 		// , '//a' => function($node, $index, $response) {
	// 		// 	static::$prefix = trim($node->nodeValue) . PHP_EOL . static::$prefix;

	// 		// 	\SeanMorris\Ids\Log::debug($node->nodeValue);
	// 		// }
	// 	];
	// }

	public static function onResponse($request, $response, $uri, $scope, $cached = FALSE)
	{
		$header = (object) $response->header;

		if(
			$header->{'X-THRUPUT-PRERENDERED-AT'}?? FALSE

			|| ($header->{'Content-Type'}?? FALSE) == 'text/html; charset=UTF-8'
		){
			$processors = static::responseProcessors();

			$dom = new \DomDocument;
			libxml_use_internal_errors(true);
			$dom->loadHTML($response->body);
			libxml_use_internal_errors(false);

			$xpath = new \DomXPath($dom);

			foreach($processors as $xQuery => $processor)
			{
				$nodes = $xpath->query($xQuery);

				foreach ($nodes as $i => $node)
				{
					$processor($node, $i, $response, $uri, $scope);
				}
			}

			$dom->normalizeDocument();

			$prefix = static::$prefix ? static::$prefix . PHP_EOL : NULL;

			$collapse = $prefix . $dom->saveHTML();

			$tidy = new \Tidy();
			$tidy->parseString($collapse, [
				'vertical-space'        => FALSE
				, 'hide-comments'       => TRUE
				, 'drop-empty-elements' => FALSE
				, 'output-html'         => TRUE
				, 'clean'               => TRUE
				, 'tidy-mark'           => FALSE
				, 'indent'              => TRUE
				, 'indent-spaces'       => 4
				, 'tab-size'            => 4
				, 'wrap'                => 80
			], 'utf8');

			$tidy->cleanRepair();

			$collapse = (string) $tidy;

			$response->body = $collapse;
		}
	}

	public static function onRequest($request, &$uri, &$headers)
	{
	}

	public static function onCache(&$cacheHash, $request, $response, $uri, $scope)
	{
		$header = (object) $response->header;

		if($header->{'X-THRUPUT-PRERENDERED-AT'} ?? FALSE
			|| ($header->{'Content-Type'} ?? FALSE) == 'text/html; charset=UTF-8'
		){
			$processors = static::cacheProcessors();

			$dom = new \DomDocument;
			libxml_use_internal_errors(true);
			$dom->loadHTML($response->body);
			libxml_use_internal_errors(false);

			$xpath = new \DomXPath($dom);

			foreach($processors as $xQuery => $processor)
			{
				$nodes = $xpath->query($xQuery);

				foreach ($nodes as $i => $node)
				{
					$processor($node, $i, $response, $uri, $scope);
				}
			}

			$dom->normalizeDocument();

			$prefix = static::$prefix ? static::$prefix . PHP_EOL : NULL;

			$collapse = $prefix . $dom->saveHTML();

			$tidy = new \Tidy();
			$tidy->parseString($collapse, [
				'vertical-space'        => FALSE
				, 'hide-comments'       => TRUE
				, 'drop-empty-elements' => FALSE
				, 'output-html'         => TRUE
				, 'clean'               => TRUE
				, 'tidy-mark'           => FALSE
				, 'indent'              => TRUE
				, 'indent-spaces'       => 4
				, 'tab-size'            => 4
				, 'wrap'                => 80
			], 'utf8');

			$tidy->cleanRepair();

			$collapse = (string) $tidy;

			$response->body = $collapse;
		}
	}
}
