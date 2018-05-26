<?php
namespace SeanMorris\ThruPut\Adapter;
class Plain extends \SeanMorris\ThruPut\Adapter
{
	public static function onResponse($request, $response, $uri, $cached = FALSE)
	{
		$response->header->{'Content-type'} = 'text/plain';
		unset($response->header->{'Content-disposition'});
		;
	}
}
