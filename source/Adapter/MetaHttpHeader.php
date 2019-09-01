<?php
namespace SeanMorris\ThruPut\Adapter;
class MetaHttpHeader extends Xpath
{
	protected static $prefix = '';

	protected static function processors()
	{
		return [
			'//meta[@name="x-thruput-http-code"]' => function($node, $index, $response) {

				$node->setAttribute('content', sprintf(
					'I was cached at %s!'
					, date('h:i:s Y-m-d')
				));
			}
		];
	}
}
