<?php
namespace SeanMorris\ThruPut;
abstract class Adapter
{
	public static function onCache(&$cacheHash, $request, $response) {}
	public static function onRequest($request, &$uri, &$headers){}
	public static function onResponse($request, $response, $cached = FALSE){}
}