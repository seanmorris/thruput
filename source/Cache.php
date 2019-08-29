<?php
namespace SeanMorris\ThruPut;
class Cache
{
	protected function __construct($meta, $handle, $offset)
	{
		$this->meta    = $meta;
		$this->handle = $handle;
		$this->offset = $offset;
	}

	public static function hash($request)
	{
		$request = json_decode(json_encode($request));

		// $request->queryString = NULL;
		// $request->query       = [];

		return sha1(json_encode($request));
	}

	public static function store($hash, $response, $time = 86400)
	{
		$adapters = \SeanMorris\Ids\Settings::read('thruput', 'adapters');

		// \SeanMorris\Ids\Log::error($response->response);

		if($adapters)
		{
			foreach($adapters as $adapterClass)
			{

				// \SeanMorris\Ids\Log::error($adapterClass);

				$cacheRes = $adapterClass::onCache(
					$cacheHash
					, $response->request
					, $response->response
					, $response->realUri
				);

				if($cacheRes === FALSE)
				{
					break;
				}
			}
		}

		\SeanMorris\Ids\Log::error($response->response);

		$_response           = clone $response;
		$_response->response = clone $_response->response;

		$body = $_response->response->body;

		unset($_response->response->body);

		$_response->meta = (object)[];

		$_response->meta->expiry = false;

		if($time >= 0)
		{
			$_response->meta->expiry = time() + $time; 
		}

		$content = json_encode($_response, JSON_PRETTY_PRINT)
			. PHP_EOL
			. '==' . PHP_EOL
			. $body;

		// file_put_contents(
		// 	static::cachePath($hash)
		// 	, $content
		// );

		$origin        = \SeanMorris\Ids\Settings::read('origin');
		$redisSettings = \SeanMorris\Ids\Settings::read('redis');

		$redis = new \Redis();

		$redis->connect($redisSettings->host, $redisSettings->port);

		$key = sprintf(
			'proxy;%s;%s'
			, $origin
			, $hash
		);

		$redis->set($key, $content);
	}

	public static function load($hash)
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

		// $cacheFile = static::cachePath($hash);

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

			return new static(
				$meta
				, $cacheHandle
				, ftell($cacheHandle)
			);
		}
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
}
