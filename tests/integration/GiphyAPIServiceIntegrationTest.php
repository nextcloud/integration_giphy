<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Giphy\Tests\Integration;

use OCA\Giphy\AppInfo\Application;
use OCA\Giphy\Service\GiphyAPIService;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Security\ICrypto;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group('DB')]
class GiphyAPIServiceIntegrationTest extends TestCase {

	private GiphyAPIService $service;
	private IAppConfig $appConfig;
	private ICrypto $crypto;
	private ?string $apiKey;

	protected function setUp(): void {
		parent::setUp();

		$this->apiKey = getenv('GIPHY_API_KEY') ?: null;

		$app = new Application();
		$this->appConfig = $app->getContainer()->get(IAppConfig::class);
		$this->crypto = Server::get(ICrypto::class);

		if ($this->apiKey !== null) {
			$encryptedKey = $this->crypto->encrypt($this->apiKey);
			$this->appConfig->setAppValueString('api_key', $encryptedKey);
		}

		$this->service = Server::get(GiphyAPIService::class);
	}

	protected function tearDown(): void {
		$this->appConfig->deleteAppValue('api_key');
		parent::tearDown();
	}

	private function requireApiKey(): void {
		if ($this->apiKey === null) {
			$this->markTestSkipped('GIPHY_API_KEY not set');
		}
	}

	public function testSearchGifsPagination(): void {
		$this->requireApiKey();

		$page1 = $this->service->searchGifs('funny', 0, 5);
		$page2 = $this->service->searchGifs('funny', 5, 5);

		$this->assertIsArray($page1);
		$this->assertIsArray($page2);
		$this->assertCount(5, $page1);
		$this->assertNotEmpty($page2);

		// Verify response structure
		$gif = $page1[0];
		$this->assertArrayHasKey('id', $gif);
		$this->assertArrayHasKey('title', $gif);
		$this->assertArrayHasKey('slug', $gif);
		$this->assertArrayHasKey('url', $gif);
		$this->assertArrayHasKey('images', $gif);
		$this->assertArrayHasKey('original', $gif['images']);
		$this->assertArrayHasKey('url', $gif['images']['original']);

		$ids1 = array_column($page1, 'id');
		$ids2 = array_column($page2, 'id');
		$this->assertNotEquals($ids1, $ids2, 'Paginated results should return different GIFs');
	}

	public function testTrendingGifsPagination(): void {
		$this->requireApiKey();

		$page1 = $this->service->getTrendingGifs(0, 5);
		$page2 = $this->service->getTrendingGifs(5, 5);

		$this->assertIsArray($page1);
		$this->assertIsArray($page2);
		$this->assertCount(5, $page1);
		$this->assertNotEmpty($page2);

		// Verify response structure
		$gif = $page1[0];
		$this->assertArrayHasKey('id', $gif);
		$this->assertArrayHasKey('title', $gif);
		$this->assertArrayHasKey('images', $gif);
		$this->assertArrayHasKey('original', $gif['images']);

		$ids1 = array_column($page1, 'id');
		$ids2 = array_column($page2, 'id');
		$this->assertNotEquals($ids1, $ids2, 'Paginated trending results should return different GIFs');
	}

	public function testGetGifFromId(): void {
		$this->requireApiKey();

		$searchResults = $this->service->searchGifs('hello', 0, 1);
		$this->assertNotEmpty($searchResults);
		$gifId = $searchResults[0]['id'];

		$result = $this->service->getGifFromId($gifId);
		$this->assertNotNull($result);
		$this->assertArrayHasKey('body', $result);
		$this->assertArrayHasKey('headers', $result);
		$this->assertNotEmpty($result['body']);

		$headers = $result['headers'];
		$contentType = $headers['Content-Type'] ?? $headers['content-type'] ?? null;
		$this->assertNotNull($contentType, 'Response should have a Content-Type header');
	}

	public function testGetGifFromDirectUrl(): void {
		$this->requireApiKey();

		$searchResults = $this->service->searchGifs('wave', 0, 1);
		$this->assertNotEmpty($searchResults);
		$gif = $searchResults[0];
		$gifId = $gif['id'];
		$originalUrl = $gif['images']['original']['url'];

		$urlInfo = GiphyAPIService::getGifUrlInfo($originalUrl);
		$this->assertNotNull($urlInfo, 'Should be able to parse the GIF URL');

		$result = $this->service->getGifFromDirectUrl(
			$gifId,
			$urlInfo['domainPrefix'],
			$urlInfo['fileName'],
			$urlInfo['cid'],
			$urlInfo['rid'],
			$urlInfo['ct'],
		);
		$this->assertArrayHasKey('body', $result);
		$this->assertArrayHasKey('headers', $result);
		$this->assertNotEmpty($result['body']);
	}

	public function testInvalidApiKey(): void {
		$encryptedKey = $this->crypto->encrypt('invalid_api_key_12345');
		$this->appConfig->setAppValueString('api_key', $encryptedKey);

		$service = Server::get(GiphyAPIService::class);
		$result = $service->searchGifs('cat', 0, 5);

		$this->assertIsArray($result);
		$this->assertArrayHasKey('error', $result);
	}

	public function testEmptyApiKey(): void {
		$this->appConfig->setAppValueString('api_key', '');

		$service = Server::get(GiphyAPIService::class);
		$result = $service->searchGifs('cat', 0, 5);

		$this->assertIsArray($result);
		$this->assertArrayHasKey('error', $result);
	}
}
