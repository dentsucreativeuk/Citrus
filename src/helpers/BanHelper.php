<?php
namespace whitespace\citrus\helpers;

use whitespace\citrus\Citrus;

use Craft;
use GuzzleHttp\Psr7\Request;

use \njpanderson\VarnishConnect;

class BanHelper
{
	use BaseHelper;

	private $socket = array();

	const BAN_PREFIX = 'req.http.host == ${hostname} && req.url ~ ';

	public function ban(array $ban, $debug = false)
	{
		$response = array();

		foreach ($this->getVarnishHosts() as $id => $host) {
			if ($id === $ban['hostId'] || $ban['hostId'] === null) {
				if ($host['canDoAdminBans']) {
					array_push(
						$response,
						$this->sendAdmin($id, $host, $ban['query'], $ban['full'], $debug)
					);
				} else {
					array_push(
						$response,
						$this->sendHTTP($id, $host, $ban['query'], $ban['full'], $debug)
					);
				}
			}
		}

		return $response;
	}

	private function sendHTTP($id, $host, $query, $isFullQuery = false, $debug = false)
	{
		$response = new ResponseHelper(
			ResponseHelper::CODE_OK
		);

		$client = new \GuzzleHttp\Client(['headers/Accept' => '*/*']);

		$banQueryHeader = Citrus::getInstance()->settings->banQueryHeader;
		$headers = array(
			'Host' => $host['hostName']
		);

		$banQuery = $this->parseBan($host, $query, $isFullQuery);

		$headers[$banQueryHeader] = $banQuery;

		Citrus::log(
			"Sending BAN query to '{$host['url'][Craft::$app->sites->currentSite->id]}': '{$banQuery}'",
			'info',
			Citrus::getInstance()->settings->logAll,
			$debug
		);

		// Ban requests always go to / but with a header determining the ban query
		$request = new Request(
			'BAN',
			$host['url'][Craft::$app->sites->currentSite->id],
			$headers
		);

		try {
			$httpResponse = $client->send($request);
			return $this->parseGuzzleResponse($request, $httpResponse);
		} catch (\GuzzleHttp\Exception\BadResponseException $e) {
			return $this->parseGuzzleError($id, $e, $debug);
		} catch (\GuzzleHttp\Exception\CurlException $e) {
			return $this->parseGuzzleError($id, $e, $debug);
		} catch (Exception $e) {
			return $this->parseGuzzleError($id, $e, $debug);
		}
	}

	private function sendAdmin($id, $host, $query, $isFullQuery = false, $debug = false)
	{
		$response = new ResponseHelper(
			ResponseHelper::CODE_OK
		);

		try {
			$socket = $this->getSocket($host['adminIP'], $host['adminPort'], $host['adminSecret']);

			$banQuery = $this->parseBan($host, $query, $isFullQuery);

			Citrus::log(
				"Adding BAN query to '{$host['adminIP']}': {$banQuery}",
				'info',
				Citrus::getInstance()->settings->logAll,
				$debug
			);

			$result = $socket->addBan($banQuery);

			if ($result !== true) {
				if ($result !== null) {
					$response->code = $result['code'];
					$response->message = "Ban error: {$result['code']} - '" .
						join($result['message'], '" "') .
						"'";

					Citrus::log(
						$response->message,
						'error',
						true,
						$debug
					);
				} else {
					$response->code = ResponseHelper::CODE_ERROR_GENERAL;
					$response->message = "Ban error: could not send to '{$host['adminIP']}'";

					Citrus::log(
						$response->message,
						'error',
						true,
						$debug
					);
				}
			} else {
				$response->message = sprintf('BAN "%s" added successfully', $banQuery);
			}
		} catch (\Exception $e) {
			$response->code = ResponseHelper::CODE_ERROR_GENERAL;
			$response->message = 'Ban error: ' . $e->getMessage();

			Citrus::log(
				$response->message,
				'error',
				true,
				$debug
			);
		}

		return $response;
	}

	private function getSocket($ip, $port, $secret)
	{
		if (isset($this->socket[$ip])) {
			return $this->socket[$ip];
		}

		$this->socket[$ip] = new VarnishConnect\Socket(
			$ip,
			$port,
			$secret
		);

		$this->socket[$ip]->connect();

		return $this->socket[$ip];
	}

	private function parseBan($host, $query, $isFullQuery = false)
	{
		if (!$isFullQuery) {
			$query = self::BAN_PREFIX . $query;
		}

		$find = ['${hostname}'];
		$replace = [$host['hostName']];

		foreach (Craft::$app->i18n->getEditableLocales() as $locale) {
			array_push($find, '${baseUrl-' . $locale->id . '}');

			if (isset($host['url'][$locale->id])) {
				array_push($replace, $host['url'][$locale->id]);
			}
		}

		// run through parsing steps
		$query = str_replace($find, $replace, $query);
		$query = str_replace('\\', '\\\\', $query);

		return $query;
	}
}
