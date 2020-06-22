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
		// static::$renderer = new \SeanMorris\Ids\ChildProcess(
		// 	'prenderer --streaming --timeout=750'
		// 	, TRUE
		// 	, TRUE
		// );
	}

	public static function recieve($request)
	{
		$origin    = \SeanMorris\Ids\Settings::read('origin');
		$adapters  = \SeanMorris\Ids\Settings::read('thruput', 'adapters');
		$expiry    = \SeanMorris\Ids\Settings::read('thruput', 'expiry');
		$cacheHash = \SeanMorris\ThruPut\Cache::hash($request);

		if($request['path'][0] == '/')
		{
			$request['path'] = substr($request['path'], 1);

			$url = $origin . '/'. $request['path'];
		}
		else
		{
			return;
		}

		\SeanMorris\Ids\Log::info(sprintf(
		 	'Prerendering %s...'
		 	, $url
		));

		$client   = new \GuzzleHttp\Client();
		$response = $client->get('http://prerenderer:3000/' . $url);

		if($response->getStatusCode() == '200')
		{
			\SeanMorris\Ids\Log::info($cacheHash);

			\SeanMorris\ThruPut\Cache::store($cacheHash, (object)[
				'realUri'    => $url
				, 'request'  => $request
				, 'response' => (object) [
					'header' => ['X-THRUPUT-PRERENDERED-AT' => time()]
					, 'body' => $response->getBody()->getContents()
				]
			], $expiry);
		}


		// if($decoded)
		// {
		// 	$cached = (object)[
		// 		'response'  => (object) [
		// 			'header' => ['X-THRUPUT-PRERENDERED-AT' => time()]
		// 			, 'body' => $decoded
		// 		]
		// 		, 'request' => $request
		// 		, 'realUri' => $url
		// 	];

		// 	\SeanMorris\Ids\Log::info('CACHING', $cached);

		// 	$expiry = \SeanMorris\Ids\Settings::read('cacheTime') ?? 60;

		// 	\SeanMorris\ThruPut\Cache::store($cacheHash, $cached, $expiry);

		// 	return $cached;
		// }
	}
}
