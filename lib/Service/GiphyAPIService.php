<?php
/**
 * Nextcloud - Giphy
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier
 * @copyright Julien Veyssier 2022
 */

namespace OCA\Giphy\Service;

use DateInterval;
use DateTime;
use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use OCA\Giphy\AppInfo\Application;
use OCP\Dashboard\Model\WidgetItem;
use OCP\Http\Client\IClient;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use OCP\Http\Client\IClientService;
use Throwable;

class GiphyAPIService {
	private LoggerInterface $logger;
	private IL10N $l10n;
	private IConfig $config;
	private IURLGenerator $urlGenerator;
	private IClient $client;

	/**
	 * Service to make requests to Giphy REST API
	 */
	public function __construct (string $appName,
								LoggerInterface $logger,
								IL10N $l10n,
								IConfig $config,
								IURLGenerator $urlGenerator,
								IClientService $clientService) {
		$this->client = $clientService->newClient();
		$this->logger = $logger;
		$this->l10n = $l10n;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @param array $gifInfo
	 * @param string $preferredVersion
	 * @return string
	 */
	public function getGifProxiedUrl(array $gifInfo, string $preferredVersion = 'original'): string {
		if (!isset($gifInfo['images'][$preferredVersion])) {
			if (!isset($gifInfo['images']['original'])) {
				return '';
			}
			$preferredVersion = 'original';
		}
		[$domainPrefix, $fileName, $cid, $rid, $ct] = self::getGifUrlInfo($gifInfo['images'][$preferredVersion]['url']);
		return $this->urlGenerator->linkToRoute(
			Application::APP_ID . '.giphyAPI.getGifFromDirectUrl',
			[
				'gifId' => $gifInfo['id'],
				'domainPrefix' => $domainPrefix,
				'fileName' => $fileName,
				'cid' => $cid,
				'rid' => $rid,
				'ct' => $ct,
			]
		);
	}

	/**
	 * @param string $mediaUrl
	 * @return array
	 */
	public static function getGifUrlInfo(string $mediaUrl): array {
		// example: https://media4.giphy.com/media/BaDsH4FpMBnqdK8J0g/giphy.gif?cid=ae23904804a21bf61bc9d904e66605c31a584d73c05db5ad&rid=giphy.gif&ct=g
		preg_match(
			'/^(?:https?:\/\/)?(?:www\.)?([A-Za-z0-9]+)\.giphy\.com\/media\/[^\/?&]+\/([^\/&?]+)\?cid=([a-z0-9]+)&rid=([^\/?&]+)&ct=([^\/?&]+)$/i',
			$mediaUrl,
			$matches
		);
		if ($matches !== null && count($matches) > 5) {
			return [$matches[1], $matches[2], $matches[3], $matches[4], $matches[5]];
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
	 * @param string $gifId
	 * @param string $preferredVersion
	 * @return array|null Avatar image data
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
		$params = [
			'q' => $query,
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

			$apiKey = $this->config->getAppValue(Application::APP_ID, 'api_key', Application::DEFAULT_API_KEY) ?: Application::DEFAULT_API_KEY;
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
			} else if ($method === 'POST') {
				$response = $this->client->post($url, $options);
			} else if ($method === 'PUT') {
				$response = $this->client->put($url, $options);
			} else if ($method === 'DELETE') {
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
		} catch (ClientException | ServerException $e) {
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
		} catch (Exception | Throwable $e) {
			$this->logger->warning('Giphy API error : ' . $e->getMessage(), ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}
}
