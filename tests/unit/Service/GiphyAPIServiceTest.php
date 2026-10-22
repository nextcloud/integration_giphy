<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Giphy\Tests;

use OCA\Giphy\AppInfo\Application;
use OCA\Giphy\Service\GiphyAPIService;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;

class GiphyAPIServiceTest extends \PHPUnit\Framework\TestCase {

	public function testDummy() {
		$app = new Application();
		$this->assertEquals('integration_giphy', $app::APP_ID);
	}

	public function testPaginationConversion() {
		$expecteds = [
			// mediaUrl => domainPrefix, fileName, cid, rid, ct
			[
				'https://media4.giphy.com/media/BaDsH4FpMBnqdK8J0g/giphy.gif?cid=ae23904804a21bf61bc9d904e66605c31a584d73c05db5ad&rid=giphy.gif&ct=g',
				['domainPrefix' => 'media4', 'fileName' => 'giphy.gif', 'cid' => 'ae23904804a21bf61bc9d904e66605c31a584d73c05db5ad', 'rid' => 'giphy.gif', 'ct' => 'g'],
			],
		];

		foreach ($expecteds as $expected) {
			$mediaUrl = $expected[0];
			$expected = $expected[1];
			$result = GiphyAPIService::getGifUrlInfo($mediaUrl);
			$this->assertEquals($expected, $result);
		}
	}

	/**
	 * The rating is stored lazily, so it has to be read lazily: the server only
	 * loads lazy values for a lazy read. The API key is read in
	 * Application::register() on every request and stays non-lazy.
	 */
	public function testRatingIsReadLazilyAndTheApiKeyIsNot(): void {
		$stored = [
			'rating' => ['value' => 'pg-13', 'lazy' => true],
			'api_key' => ['value' => 'ciphertext', 'lazy' => false],
		];
		$reads = [];
		$appConfig = $this->createMock(IAppConfig::class);
		// the untyped method ignores lazy values
		$appConfig->expects($this->never())->method('getAppValue');
		$appConfig->method('getAppValueString')
			->willReturnCallback(function (string $key, string $default = '', bool $lazy = false) use ($stored, &$reads): string {
				$reads[] = [$key, $lazy];
				if (!isset($stored[$key]) || ($stored[$key]['lazy'] && !$lazy)) {
					return $default;
				}
				return $stored[$key]['value'];
			});

		$crypto = $this->createMock(ICrypto::class);
		$crypto->method('decrypt')->willReturnMap([['ciphertext', '', 'the-real-key']]);

		$urls = [];
		$response = $this->createMock(IResponse::class);
		$response->method('getStatusCode')->willReturn(200);
		$response->method('getBody')->willReturn('{"data":[]}');
		$client = $this->createMock(IClient::class);
		$client->method('get')
			->willReturnCallback(function (string $url) use (&$urls, $response): IResponse {
				$urls[] = $url;
				return $response;
			});
		$clientService = $this->createMock(IClientService::class);
		$clientService->method('newClient')->willReturn($client);

		$service = new GiphyAPIService(
			$this->createMock(LoggerInterface::class),
			$this->createMock(IL10N::class),
			$appConfig,
			$this->createMock(IURLGenerator::class),
			$crypto,
			$clientService,
		);

		$this->assertSame([], $service->searchGifs('cats'));
		$this->assertSame([], $service->getTrendingGifs());

		$this->assertCount(2, $urls);
		foreach ($urls as $url) {
			parse_str((string)parse_url($url, PHP_URL_QUERY), $query);
			$this->assertSame('pg-13', $query['rating']);
			$this->assertSame('the-real-key', $query['api_key']);
		}
		$this->assertSame([
			['rating', true],
			['api_key', false],
			['rating', true],
			['api_key', false],
		], $reads);
	}
}
