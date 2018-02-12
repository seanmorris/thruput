<?php
namespace SeanMorris\ThruPut;
class Cache
{
	protected function __construct()
	{

	}

	public static function store($hash, $response)
	{
		$_response           = clone $response;
		$_response->response = clone $_response->response;

		$_response->response->body = base64_encode($_response->response->body);

		file_put_contents(
			static::cachePath($hash)
			, json_encode($_response, JSON_PRETTY_PRINT)
		);
	}

	public static function load($hash)
	{
		if(file_exists(static::cachePath($hash)))
		{
			$cache = json_decode(
				file_get_contents(
					static::cachePath($hash)
				)
			);

			if($cache)
			{
				$cache->response->body = base64_decode($cache->response->body);
			}

			return $cache;
		}
	}

	public static function clear($router)
	{
		$cacheDir = new \SeanMorris\Ids\Disk\Directory(
			$this->cachePath()
		);

		while($file = $cacheDir->read())
		{
			printf('Removing %s...' . PHP_EOL, $file->name());
			$file->delete();
		}
	}

	public static function cachePath($hash)
	{
		return sprintf(
			'%s/../temporary/thruput/%s'
			, IDS_VENDOR_ROOT
			, $hash
		);
	}
}
