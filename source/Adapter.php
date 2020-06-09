<?php
namespace SeanMorris\ThruPut;
abstract class Adapter
{
	public static function onCache(&$cacheHash, $request, $response, $uri, $scope) {}
	public static function onRequest($request, &$uri, &$headers){}
	public static function onResponse($request, $response, $uri, $scope, $cached = FALSE){}
	public static function onDisconnect($request, $response, $uri, $cacheHash, $cached = FALSE){}
}