<?php
namespace SeanMorris\ThruPut\Client;
class Tor extends \SeanMorris\ThruPut\Client
{
	public static function request($uri, $headers = [])
	{
		$stack = new \GuzzleHttp\HandlerStack();
		$stack->setHandler(new \GuzzleHttp\Handler\CurlHandler());
		$stack->push(\GuzzleTor\Middleware::tor());
		$client = new \GuzzleHttp\Client(['handler' => $stack]);

		$response = $client->get($uri);
		
		return (object)[
			'header' => $response->getHeaders()
			, 'body' => $response->getBody()->getContents()
		];

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $uri);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$response = curl_exec($ch);

		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$body = substr($response, $header_size);

		$_headers = array_filter(
			array_map('trim', explode(PHP_EOL, $header))
		);

		$_headers = array_map(
			function($header) {
				return explode(': ', $header, 2);
			}
			, $_headers
		);

		foreach($_headers as $header)
		{
			$headers[$header[0]] = $header[1] ?? NULL;
		}

		curl_close($ch);

		$headers = (object) $headers;

		return (object)[
			'header' => $headers
			, 'body' => $body
		];
	}
}
