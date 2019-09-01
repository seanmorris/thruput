<?php
namespace SeanMorris\ThruPut;
class Cache
{
	protected $meta, $handle, $offet, $hash;

	protected function __construct($meta = NULL, $handle = NULL, $offset = NULL)
	{
		$this->meta   = $meta;
		$this->handle = $handle;
		$this->offset = $offset;
	}

	public static function hash($request)
	{
		$request = json_decode(json_encode($request));

		return sha1(json_encode($request));
	}

	public static function store($hash, $content, $time = 86400)
	{
		$adapters = \SeanMorris\Ids\Settings::read('thruput', 'adapters');

		if($adapters)
		{
			foreach($adapters as $adapterClass)
			{

				$cacheRes = $adapterClass::onCache(
					$cacheHash
					, $content->request
					, $content->response
					, $content->realUri
				);

				if($cacheRes === FALSE)
				{
					break;
				}
			}
		}

		$_content           = clone $content;
		$_content->hash     = $hash;
		$_content->response = clone $_content->response;

		$body = $_content->response->body;

		unset($_content->response->body);

		$_content->meta = (object)[];

		$_content->meta->expiry = false;

		if($time >= 0)
		{
			$_content->meta->expiry = time() + $time;
		}

		$cacheBlob = json_encode($_content, JSON_PRETTY_PRINT)
			. PHP_EOL
			. '==' . PHP_EOL
			. $body;

		$origin        = \SeanMorris\Ids\Settings::read('origin');
		$redisSettings = \SeanMorris\Ids\Settings::read('redis');

		$redis = new \Redis();

		$redis->connect($redisSettings->host, $redisSettings->port);

		$key = sprintf(
			'proxy;%s;%s'
			, $origin
			, $hash
		);

		$redis->set($key, $cacheBlob);
	}

	public static function load($hash)
	{
		$cacheObject = new Static();

		if($cacheObject->refresh($hash))
		{
			return $cacheObject;
		}
	}

	public function refresh($hash)
	{
		$origin        = \SeanMorris\Ids\Settings::read('origin');
		$redisSettings = \SeanMorris\Ids\Settings::read('redis');

		$redis = new \Redis();

		$redis->connect($redisSettings->host, $redisSettings->port);

		$key = sprintf(
			'proxy;%s;%s'
			, $origin
			, $hash
		);

		if($redis->exists($key))
		{
			$cacheFile =(
				'data:text/plain;base64,'
					. base64_encode($redis->get($key))
			);

			$cacheHandle = fopen($cacheFile, 'rb');
			$metaString  = '';
			$meta        = (object)[];

			while($line = fgets($cacheHandle))
			{
				if(strlen($line) === 3 && substr($line, 0, 2) == '==')
				{
					if($meta = json_decode($metaString))
					{
						break;
					}
				}
				$metaString .= $line;
			}

			if($meta->meta
				&& $meta->meta->expiry !== FALSE
				&& $meta->meta->expiry > 0
				&& $meta->meta->expiry < time()
			){
				\SeanMorris\Ids\Log::debug(
					'FAIL!'
					, $meta->meta->expiry
					, time()
					, $meta->meta->expiry < time()
				);

				return FALSE;
			}

			\SeanMorris\Ids\Log::debug(
				'PASS!!!!'
				, $meta->meta->expiry
				, time()
				, $meta->meta->expiry < time()
			);

			$this->meta   = $meta;
			$this->handle = $cacheHandle;
			$this->offset = ftell($cacheHandle);

			return TRUE;
		}

		return FALSE;
	}

	public function delete($hash)
	{
		if(!file_exists($filePath = static::cachePath($hash)))
		{
			return;
		}

		$cacheFile = new \SeanMorris\Ids\Disk\File($filePath);
		$cacheFile->delete();
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
			$callback(fread($this->handle, 1024));
		}
	}

	public function meta($property)
	{
		return $this->meta->{$property} ?? NULL;
	}
}
