<?php
namespace SeanMorris\ThruPut\Adapter;
class MetaHttpHeader extends Xpath
{
	protected static $prefix = '';

	protected static function processors()
	{
		return [
			'//meta[name="x-thruput-http-code"]' => function($node, $index, $response) {
				// \SeanMorris\Ids\Log::debug($node->getAttribute('content'));
				$node->nodeValue = date('h:i:s Y-m-d');
			}
		];
	}
}
