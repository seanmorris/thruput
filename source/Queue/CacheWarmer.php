<?php
namespace SeanMorris\ThruPut\Queue;
class CacheWarmer extends \SeanMorris\Ids\Queue
{
	const CHANNEL_NO_ACK = FALSE, ASYNC = FALSE, RPC = FALSE;

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
			'prenderer --streaming --timeout=750'
			, TRUE
			, TRUE
		);
	}

	public static function recieve($request)
	{
		\SeanMorris\Ids\Log::debug($request);

		$origin    = \SeanMorris\Ids\Settings::read('origin');
		$adapters  = \SeanMorris\Ids\Settings::read('thruput', 'adapters');
		$expiry    = \SeanMorris\Ids\Settings::read('thruput', 'expiry');

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

		$prerendered = NULL;
		$signaling   = NULL;
		$decoded     = NULL;

		static::$renderer->write($url . PHP_EOL);

		do
		{
			sleep(1);

			while($signaling = static::$renderer->readError())
			{
				\SeanMorris\Ids\Log::debug($signaling);

				fwrite(STDERR, $signaling);
			}

			while($p = static::$renderer->read())
			{
				$prerendered .= $p;
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

		} while(!$prerendered);

		\SeanMorris\ThruPut\Cache::store($cacheHash, (object)[
			'response'  => (object) [
				'header' => ['X-THRUPUT-PRERENDERED-AT' => time()]
				, 'body' => json_decode($prerendered)
			]
			, 'request' => $request
			, 'realUri' => $url
		], $expiry);

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

			\SeanMorris\Ids\Log::info('CACHING', $cached);

			$expiry = \SeanMorris\Ids\Settings::read('cacheTime') ?? 60;

			\SeanMorris\ThruPut\Cache::store($cacheHash, $cached, $expiry);

			return $cached;
		}
	}
}
