<?php
namespace SeanMorris\ThruPut\Queue;
class CacheWarmer extends \SeanMorris\Ids\Queue
{
	const CHANNEL_NO_ACK = FALSE;

	protected static $renderer;

	public static function produce()
	{
		$origin  = \SeanMorris\Ids\Settings::read('origin');
		$realUri = sprintf(
			'%s/sitemap.xml'
			, $origin
		);

		$response  = \SeanMorris\ThruPut\Client\Standard::request($realUri);
		$xml       = new \XMLReader();

		$xml->xml($response->body);

		while($xml->read())
		{
			if($xml->nodeType == \XMLReader::END_ELEMENT)
			{
				continue;
			}

			if($xml->name == 'loc')
			{
				print $xml->readString();
				print PHP_EOL;

				$request = \SeanMorris\ThruPut\Request::skeleton(
					$xml->readString()
				);

				static::send($request);
			}
		}
	}

	public static function init()
	{
		static::$renderer = new \SeanMorris\Ids\ChildProcess(
			'prenderer --streaming'
		);
	}

	public static function recieve($request)
	{
		$origin    = \SeanMorris\Ids\Settings::read('origin');
		$cacheHash = \SeanMorris\ThruPut\Cache::hash($request);

		if($request['path'][0] == '/')
		{
			$request['path'] = substr($request['path'], 1);

			$url = $origin . '/'. $request['path'];
		}

		static::$renderer->write($url . PHP_EOL);

		while($signaling = static::$renderer->readError())
		{
			fwrite(STDERR, $signaling . PHP_EOL);
		}

		fwrite(STDERR, sprintf(
			'Prerendering %s...' . PHP_EOL
			, $url
		));

		$prerendered = static::$renderer->read();

		while($signaling = static::$renderer->readError())
		{
			fwrite(STDERR, $signaling . PHP_EOL);
		}

		fwrite(STDERR, $prerendered . PHP_EOL);

		\SeanMorris\ThruPut\Cache::store($cacheHash, (object)[
			'response'  => (object) [
				'headers' => []
				, 'body'  => json_decode($prerendered)
			]
			, 'request' => $request
		], -1);

		return TRUE;
	}
}