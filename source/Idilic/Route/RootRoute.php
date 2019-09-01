<?php
namespace SeanMorris\ThruPut\Idilic\Route;
class RootRoute implements \SeanMorris\Ids\Routable
{
	public function warmPar()
	{
		\SeanMorris\ThruPut\Queue\CacheWarmer::produce();
	}

	public function warmDaemon()
	{
		\SeanMorris\ThruPut\Queue\CacheWarmer::listen();
	}

	public function warm()
	{
		$origin  = \SeanMorris\Ids\Settings::read('origin');
		$realUri = sprintf(
			'%s/sitemap.xml'
			, $origin
		);

		$xml       = new \XMLReader();
		$response  = \SeanMorris\ThruPut\Client\Standard::request($realUri);

		$xml->xml($response->body);

		while($xml->read())
		{
			if($xml->nodeType == \XMLReader::END_ELEMENT)
			{
				continue;
			}

			if($xml->name == 'loc')
			{
				$skeleton  = \SeanMorris\ThruPut\Request::skeleton(
					$xml->readString()
					, 'thruput'
				);

				$cacheHash = \SeanMorris\ThruPut\Cache::hash($skeleton);

				$prendererUri = sprintf(
					'%s?timeout=%d&url=%s'
					, $origin
					, 5000
					, urlencode($xml->readString())
				);

				fwrite(STDERR, $prendererUri . PHP_EOL);

				$response = \SeanMorris\ThruPut\Client\Standard::request($prendererUri);

				\SeanMorris\ThruPut\Cache::store($cacheHash, (object)[
					'response'  => $response
					, 'request' => $skeleton
				], \SeanMorris\Ids\Settings::read('cacheTime') ?? 60);
			}
		}
	}

	public function clear($router)
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

	public function cachePath()
	{
		return sprintf(
			'%s/../temporary/thruput/'
			, IDS_VENDOR_ROOT
		);
	}

	public function feeder($router)
	{
		$args = $router->path()->consumeNodes();
		$file = array_shift($args);

		if($inputFile = fopen($file, 'r'))
		{
			while($line = fgets($inputFile))
			{
				fwrite(STDOUT, $line);

				$next = trim(fgets(STDIN));

				if($next === 'x')
				{
					die;
				}
			}
		}
	}
}
