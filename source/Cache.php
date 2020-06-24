<?php
namespace SeanMorris\ThruPut;
class Cache
{
	protected $meta, $hash;

	protected function __construct($meta = NULL, $handle = NULL, $offset = NULL)
	{
		$this->meta   = $meta;
		$this->handle = $handle;
		$this->offset = $offset;
	}

	public static function hash($request)
	{
		\SeanMorris\Ids\Log::info($request);

		$request = json_decode(json_encode($request));

		return sha1(json_encode($request));
	}

	public static function store($hash, $content, $time = 86400)
	{
		// \SeanMorris\Ids\Log::info($hash, $content, $time);

		$adapters = \SeanMorris\Ids\Settings::read('thruput', 'adapters');

		if($adapters)
		{
			$scope = (object) [];

			foreach($adapters as $adapterClass)
			{

				$cacheRes = $adapterClass::onCache(
					$cacheHash
					, $content->request
					, $content->response
					, $content->realUri
					, $scope
				);

				if($cacheRes === FALSE)
				{
					break;
				}
			}
		}

		// \SeanMorris\Ids\Log::info($content->response);

		$_content           = clone $content;
		$_content->hash     = $hash;
		$_content->response = clone $_content->response;

		$body = $_content->response->body;

		unset($_content->response->body);

		$_content->meta = (object)[];

		$_content->meta->expiry = false;

		if(isset($scope->expiry))
		{
			// if($scope->expiry == 0)
			// {
			// 	return;
			// }

			$_content->meta->expiry = time() + $scope->expiry;
		}
		else if($time >= 0)
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

		$redis->pconnect($redisSettings->host, $redisSettings->port);

		$key = sprintf(
			'proxy;%s;%s'
			, $origin
			, $hash
		);

		$redis->set($key, $cacheBlob);
	}

	public static function load($hash)
	{
		$origin = \SeanMorris\Ids\Settings::read('origin');

		if(!$redisSettings = \SeanMorris\Ids\Settings::read('redis'))
		{
			throw new Exception('No redis servers specified.');
		}

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

		$redis->pconnect($redisSettings->host, $redisSettings->port);

		$key = sprintf(
			'proxy;%s;%s'
			, $origin
			, $hash
		);

		$content = $redis->get($key);
		$body    = '';

		if($redis->exists($key))
		{
			$contentLines  = explode("\n", $content);

			$metaString  = '';
			$meta        = FALSE;

			foreach($contentLines as $line)
			{
				if(!$meta && substr($line, 0, 2) == '==')
				{
					if($meta = json_decode($metaString))
					{
						continue;
					}
				}

				if($meta)
				{
					$body .= $line . PHP_EOL;
					continue;
				}
				else
				{
					$metaString .= $line . PHP_EOL;
					continue;
				}
			}

			\SeanMorris\Ids\Log::info($meta);

			if(isset($meta->meta)
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

			$meta->response->body = $body;

			$this->meta = $meta;

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
		\SeanMorris\Ids\Log::debug($this->meta);

		$callback($this->meta->response->body);
	}

	public function meta($property)
	{
		return $this->meta->{$property} ?? NULL;
	}
}
