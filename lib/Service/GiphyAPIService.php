<?php
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Giphy\Service;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use OCA\Giphy\AppInfo\Application;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Service to make requests to the Giphy REST API
 */
class GiphyAPIService {
	private IClient $client;

	public function __construct(
		private LoggerInterface $logger,
		private IL10N $l10n,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
		private ICrypto $crypto,
		IClientService $clientService,
	) {
		$this->client = $clientService->newClient();
	}

	/**
	 * @param array $gifInfo
	 * @param string $preferredVersion
	 * @param bool $private
	 * @return string
	 */
	public function getGifProxiedUrl(array $gifInfo, string $preferredVersion = 'original', bool $private = false): string {
		if (!isset($gifInfo['images'][$preferredVersion])) {
			if (!isset($gifInfo['images']['original'])) {
				return '';
			}
			$preferredVersion = 'original';
		}
		$gifUrlInfo = self::getGifUrlInfo($gifInfo['images'][$preferredVersion]['url']);
		$route = $private
			? Application::APP_ID . '.giphyAPI.privateGetGifFromDirectUrl'
			: Application::APP_ID . '.giphyAPI.getGifFromDirectUrl';
		return $this->urlGenerator->linkToRouteAbsolute(
			$route,
			[
				'gifId' => $gifInfo['id'],
				'domainPrefix' => $gifUrlInfo['domainPrefix'],
				'fileName' => $gifUrlInfo['fileName'],
				'cid' => $gifUrlInfo['cid'],
				'rid' => $gifUrlInfo['rid'],
				'ct' => $gifUrlInfo['ct'],
			]
		);
	}

	/**
	 * @param string $mediaUrl
	 * @return array
	 */
	public static function getGifUrlInfo(string $mediaUrl): array {
		// examples:
		// https://media4.giphy.com/media/BaDsH4FpMBnqdK8J0g/giphy.gif?cid=ae23904804a21bf61bc9d904e66605c31a584d73c05db5ad&rid=giphy.gif&ct=g
		// https://media1.giphy.com/media/HCTfYH2Xk5yw/200w.gif?cid=ae239048qk2ahzc7vpjuagzbyava4073ygy3gj2owzyx3jtl&ep=v1_gifs_trending&rid=200w.gif&ct=g
		$parsedUrl = parse_url($mediaUrl);
		preg_match('/^(?:www\.)?([A-Za-z0-9]+)\.giphy\.com$/i', $parsedUrl['host'], $domainPrefixMatches);
		if (count($domainPrefixMatches) > 1) {
			$domainPrefix = $domainPrefixMatches[1];
			preg_match('/^\/media\/[^\/?&]+\/([^\/&?]+)$/i', $parsedUrl['path'], $pathMatches);
			if (count($pathMatches) > 1) {
				$fileName = $pathMatches[1];
				$query = $parsedUrl['query'];
				parse_str($query, $parsedQuery);
				if (isset($parsedQuery['cid'], $parsedQuery['rid'], $parsedQuery['ct'])) {
					return [
						'domainPrefix' => $domainPrefix,
						'fileName' => $fileName,
						'cid' => $parsedQuery['cid'],
						'rid' => $parsedQuery['rid'],
						'ct' => $parsedQuery['ct'],
					];
				}
			}
		}

		return [];
	}

	/**
	 * @param string $gifId
	 * @return array
	 */
	public function getGifInfo(string $gifId): array {
		$response = $this->request($gifId);
		if (!isset($response['error']) && isset($response['data'])) {
			return $response['data'];
		}
		return $response;
	}

	/**
	 * Request a gif image
	 *
	 * @param string $gifId
	 * @param string $preferredVersion
	 * @return array|null image data
	 * @throws Exception
	 */
	public function getGifFromId(string $gifId, string $preferredVersion = 'original'): ?array {
		$gifInfo = $this->getGifInfo($gifId);
		if (!isset($gifInfo['error']) && isset($gifInfo['id']) && $gifInfo['id'] === $gifId) {
			if (!isset($gifInfo['images'][$preferredVersion])) {
				if (!isset($gifInfo['images']['original'])) {
					return null;
				}
				$preferredVersion = 'original';
			}
			$gifResponse = $this->client->get($gifInfo['images'][$preferredVersion]['url']);
			return [
				'body' => $gifResponse->getBody(),
				'headers' => $gifResponse->getHeaders(),
			];
		}
		return null;
	}

	/**
	 * @param string $gifId
	 * @param string $domainPrefix
	 * @param string $fileName
	 * @param string $cid
	 * @param string $rid
	 * @param string $ct
	 * @return array
	 * @throws Exception
	 */
	public function getGifFromDirectUrl(string $gifId, string $domainPrefix, string $fileName, string $cid, string $rid, string $ct): array {
		// example: https://media4.giphy.com/media/BaDsH4FpMBnqdK8J0g/giphy.gif?cid=ae23904804a21bf61bc9d904e66605c31a584d73c05db5ad&rid=giphy.gif&ct=g
		$gifUrl = 'https://' . urlencode($domainPrefix) . '.giphy.com/media/' . urlencode($gifId) . '/' . urlencode($fileName)
			. '?cid=' . urlencode($cid)
			. '&rid=' . urlencode($rid)
			. '&ct=' . urlencode($ct);
		$gifResponse = $this->client->get($gifUrl);
		return [
			'body' => $gifResponse->getBody(),
			'headers' => $gifResponse->getHeaders(),
		];
	}

	/**
	 * Search gifs
	 *
	 * @param string $query
	 * @param int $offset
	 * @param int $limit
	 * @return array request result
	 */
	public function searchGifs(string $query, int $offset = 0, int $limit = 5): array {
		$rating = $this->config->getAppValue(Application::APP_ID, 'rating', Application::DEFAULT_RATING) ?: Application::DEFAULT_RATING;
		$params = [
			'q' => $query,
			'rating' => $rating,
			'limit' => $limit,
			'offset' => $offset,
		];
		$result = $this->request('search', $params);
		if (!isset($result['error']) && isset($result['data']) && is_array($result['data'])) {
			return $result['data'];
		}
		return $result;
	}

	/**
	 * Get trending gifs
	 *
	 * @param int $offset
	 * @param int $limit
	 * @return array request result
	 */
	public function getTrendingGifs(int $offset = 0, int $limit = 10): array {
		$rating = $this->config->getAppValue(Application::APP_ID, 'rating', Application::DEFAULT_RATING) ?: Application::DEFAULT_RATING;
		$params = [
			'rating' => $rating,
			'limit' => $limit,
			'offset' => $offset,
		];
		$result = $this->request('trending', $params);
		if (!isset($result['error']) && isset($result['data']) && is_array($result['data'])) {
			return $result['data'];
		}
		return $result;
	}

	/**
	 * Make an HTTP request to the Giphy API
	 * @param string $endPoint The path to reach in api.github.com
	 * @param array $params Query parameters (key/val pairs)
	 * @param string $method HTTP query method
	 * @return array decoded request result or error
	 */
	public function request(string $endPoint, array $params = [], string $method = 'GET'): array {
		try {
			$url = 'https://api.giphy.com/v1/gifs/' . $endPoint;
			$options = [
				'headers' => [
					'User-Agent' => 'Nextcloud Giphy integration',
				],
			];

			$apiKey = $this->config->getAppValue(Application::APP_ID, 'api_key');
			$apiKey = $apiKey === '' ? '' : $this->crypto->decrypt($apiKey);
			$params['api_key'] = $apiKey;

			if (count($params) > 0) {
				if ($method === 'GET') {
					$paramsContent = http_build_query($params);
					$url .= '?' . $paramsContent;
				} else {
					$options['body'] = json_encode($params);
				}
			}

			if ($method === 'GET') {
				$response = $this->client->get($url, $options);
			} elseif ($method === 'POST') {
				$response = $this->client->post($url, $options);
			} elseif ($method === 'PUT') {
				$response = $this->client->put($url, $options);
			} elseif ($method === 'DELETE') {
				$response = $this->client->delete($url, $options);
			} else {
				return ['error' => $this->l10n->t('Bad HTTP method')];
			}
			$body = $response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('Bad credentials')];
			} else {
				return json_decode($body, true) ?: [];
			}
		} catch (ClientException|ServerException $e) {
			$responseBody = $e->getResponse()->getBody();
			$parsedResponseBody = json_decode($responseBody, true);
			if ($e->getResponse()->getStatusCode() === 404) {
				// Only log inaccessible github links as debug
				$this->logger->debug('Giphy API error : ' . $e->getMessage(), ['response_body' => $responseBody, 'app' => Application::APP_ID]);
			} else {
				$this->logger->warning('Giphy API error : ' . $e->getMessage(), ['response_body' => $responseBody, 'app' => Application::APP_ID]);
			}
			return [
				'error' => $e->getMessage(),
				'body' => $parsedResponseBody,
			];
		} catch (Exception|Throwable $e) {
			$this->logger->warning('Giphy API error : ' . $e->getMessage(), ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}
}
