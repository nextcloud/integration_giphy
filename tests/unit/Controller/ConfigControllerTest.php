<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Giphy\Tests\Controller;

use OCA\Giphy\AppInfo\Application;
use OCA\Giphy\Controller\ConfigController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Security\ICrypto;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigControllerTest extends TestCase {

	private IAppConfig|MockObject $appConfig;
	private ICrypto|MockObject $crypto;
	private ConfigController $controller;
	/** @var list<array{string, string, bool}> */
	private array $writes = [];

	protected function setUp(): void {
		parent::setUp();
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->crypto = $this->createMock(ICrypto::class);

		// the legacy app config methods ignore lazy values and write non-lazy ones
		$config = $this->createMock(IConfig::class);
		foreach (['getAppValue', 'setAppValue', 'deleteAppValue'] as $method) {
			$config->expects($this->never())->method($method);
		}
		foreach (['getAppValue', 'setAppValue'] as $method) {
			$this->appConfig->expects($this->never())->method($method);
		}

		$this->controller = new ConfigController(
			Application::APP_ID,
			$this->createMock(IRequest::class),
			$this->appConfig,
			$config,
			$this->crypto,
			'admin',
		);

		$this->appConfig->method('setAppValueString')
			->willReturnCallback(function (string $key, string $value, bool $lazy = false): bool {
				$this->writes[] = [$key, $value, $lazy];
				return true;
			});
	}

	public function testRatingIsStoredLazily(): void {
		$response = $this->controller->setAdminConfig(['rating' => 'pg-13']);

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame([['rating', 'pg-13', true]], $this->writes);
	}

	/**
	 * Application::register() reads these on every request, so they must not
	 * become lazy when an admin saves them. The settings page sends one per request.
	 */
	public function testSwitchesStayNonLazy(): void {
		$this->assertSame(Http::STATUS_OK, $this->controller->setAdminConfig(['link_preview_enabled' => '0'])->getStatus());
		$this->assertSame(Http::STATUS_OK, $this->controller->setAdminConfig(['search_gifs_enabled' => '1'])->getStatus());

		$this->assertSame([
			['link_preview_enabled', '0', false],
			['search_gifs_enabled', '1', false],
		], $this->writes);
	}

	public function testApiKeyIsStoredEncryptedAndNonLazy(): void {
		$this->crypto->expects($this->once())
			->method('encrypt')
			->with('the-real-key')
			->willReturn('ciphertext');

		$response = $this->controller->setSensitiveAdminConfig(['api_key' => 'the-real-key']);

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame([['api_key', 'ciphertext', false]], $this->writes);
	}

	public function testClearedApiKeyIsStoredEmpty(): void {
		$this->crypto->expects($this->never())->method('encrypt');

		$response = $this->controller->setSensitiveAdminConfig(['api_key' => '']);

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame([['api_key', '', false]], $this->writes);
	}

	public static function rejectedRequests(): array {
		return [
			'the server\'s own enabled key' => ['setAdminConfig', ['enabled' => 'yes']],
			'enabled next to an allowed key' => ['setAdminConfig', ['rating' => 'pg', 'enabled' => 'no']],
			'the API key on the plain route' => ['setAdminConfig', ['api_key' => 'the-real-key']],
			'a numeric key' => ['setAdminConfig', [0 => '1']],
			'a boolean value' => ['setAdminConfig', ['link_preview_enabled' => true]],
			'a null value' => ['setAdminConfig', ['search_gifs_enabled' => null]],
			'an array value' => ['setAdminConfig', ['rating' => ['pg']]],
			'enabled on the sensitive route' => ['setSensitiveAdminConfig', ['enabled' => 'yes']],
			'the rating on the sensitive route' => ['setSensitiveAdminConfig', ['api_key' => 'the-real-key', 'rating' => 'pg']],
			'a numeric API key' => ['setSensitiveAdminConfig', ['api_key' => 123]],
		];
	}

	/**
	 * Every key is checked before anything is stored, so a rejected request
	 * leaves the app config as it was.
	 */
	#[DataProvider('rejectedRequests')]
	public function testRequestsWithOtherKeysOrValuesAreRejectedWithoutWriting(string $route, array $values): void {
		$this->crypto->expects($this->never())->method('encrypt');

		$response = $this->controller->$route($values);

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame([], $this->writes);
	}
}
