<?php
namespace SeanMorris\ThruPut\Queue;
class CacheWarmer extends \SeanMorris\Ids\Queue
{
	const CHANNEL_NO_ACK = FALSE, ASYNC = TRUE, RPC = TRUE;

	protected static $renderer;

	public static function produce()
	{
		$origin  = \SeanMorris\Ids\Settings::read('origin');
		$realUri = sprintf(
			'%s/sitemap.xml'
			, $origin
		);

		$response = \SeanMorris\ThruPut\Client\Standard::request($realUri);
		$xml      = new \XMLReader();

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
			, TRUE
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

		fwrite(STDERR, sprintf(
			'Origin %s...' . PHP_EOL
			, $origin
		));

		fwrite(STDERR, sprintf(
			'Prerendering %s...' . PHP_EOL
			, $url
		));

		\SeanMorris\Ids\Log::debug(sprintf(
		 	'Prerendering %s...'
		 	, $url
		));

		static::$renderer->write($url . PHP_EOL);

		$prerendered = NULL;
		$signaling   = NULL;

		do
		{
			while($p = static::$renderer->read())
			{
				$prerendered .= $p;
				usleep(1000);
			}

			if($prerendered)
			{
				$decoded = json_decode($prerendered);

				\SeanMorris\Ids\Log::debug($decoded);

				if($error = json_last_error())
				{
					\SeanMorris\Ids\Log::debug(
						$error
						, json_last_error_msg()
						, $prerendered
					);
				}
			}

			while($signaling = static::$renderer->readError())
			{
				\SeanMorris\Ids\Log::debug($signaling);
			}

		} while(!$prerendered);

		if($decoded)
		{
			$cached = (object)[
				'response'  => (object) [
					'header' => ['X-THRUPUT-PRERENDERED-AT' => time()]
					, 'body' => $decoded
				]
				, 'request' => $request
				, 'realUri' => $url
			];

			\SeanMorris\Ids\Log::debug($cached);

			$expiry = \SeanMorris\Ids\Settings::read('cacheTime') ?? 60;

			\SeanMorris\ThruPut\Cache::store($cacheHash, $cached, $expiry);

			return $cached;
		}
	}
}
