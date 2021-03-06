<?php
namespace SeanMorris\ThruPut\Client;
class Standard extends \SeanMorris\ThruPut\Client
{
	public static function request($uri, $headers = [])
	{
		$body = NULL;
		$post = FALSE;

		if($_SERVER['REQUEST_METHOD'] == 'POST')
		{
			$post       = TRUE;
		}

		$ch = curl_init();

		\SeanMorris\Ids\Log::debug(sprintf(
			'CURLing %s...'
			, $uri
		));

		curl_setopt($ch, CURLOPT_URL, $uri);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		if($_POST)
		{
			curl_setopt($ch, CURLOPT_POST, count($_POST));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
		}

		$response    = curl_exec($ch);
		$error       = curl_error($ch);

		if($error)
		{
			\SeanMorris\Ids\Log::warn($error);

			return (object) [];
		}


		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header      = substr($response, 0, $header_size);
		$body        = substr($response, $header_size);

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
