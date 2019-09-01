<?php
namespace SeanMorris\ThruPut\Adapter;
class MetaHttpHeader extends Xpath
{
	protected static $prefix = '';

	protected static function cacheProcessors()
	{
		return [
			'//meta[@name="x-thruput-http-code"]' => function($node, $index, $response, $realUri, $scope) {

				$node->setAttribute('content', 200);
			}
			, '//meta[@name="x-thruput-cache-time"]' => function($node, $index, $response, $realUri, $scope) {

				$node->setAttribute('content', sprintf(
					'I was cached at %s!'
					, date('Y-m-d h:i:s')
				));
			}
			, '//meta[@name="x-thruput-cache-expiry"]' => function($node, $index, $response, $realUri, $scope) {

				$expiry = $node->getAttribute('content');

				$scope->expiry = $expiry;
			}
		];
	}

	protected static function responseProcessors()
	{
		return [
			'//meta[@name="x-thruput-cache-expiry"]' => function($node, $index, $response, $realUri, $scope) {

				$expiry = $node->getAttribute('content');

				$scope->expiry = $expiry;
			}
		];
	}
}
