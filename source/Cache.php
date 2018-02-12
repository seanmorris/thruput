<?php
namespace SeanMorris\ThruPut;
class Cache
{
	protected function __construct($meta, $handle, $offset)
	{
		$this->meta   = json_decode($meta);
		$this->handle = $handle;
		$this->offset = $offset;
	}

	public static function store($hash, $response)
	{
		$_response           = clone $response;
		$_response->response = clone $_response->response;

		$body = $_response->response->body;

		unset($_response->response->body);

		file_put_contents(
			static::cachePath($hash)
			, json_encode($_response, JSON_PRETTY_PRINT)
				. PHP_EOL
		);

		file_put_contents(
			static::cachePath($hash)
			, '==' . PHP_EOL
			, FILE_APPEND
		);

		file_put_contents(
			static::cachePath($hash)
			, $body
			, FILE_APPEND
		);
	}

	public static function load($hash)
	{
		if(file_exists(static::cachePath($hash)))
		{
			$cacheFile = static::cachePath($hash);

			$cacheHandle = fopen($cacheFile, 'r');

			$metaString = '';

			while($line = fgets($cacheHandle))
			{
				if(strlen($line) === 3 && substr($line, 0, 2) == '==')
				{
					break;
				}
				$metaString .= $line;
			}
			return new static(
				$metaString
				, $cacheHandle
				, ftell($cacheHandle)
			);
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

	public function readOut($callback)
	{
		fseek($this->handle, $this->offset);

		while(!feof($this->handle))
		{
			$callback(fread($this->handle, 128));
		}
	}
}
