<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Giphy\Tests\Reference;

use OC\Collaboration\Reference\LinkReferenceProvider;
use OCA\Giphy\Reference\GiphyReferenceProvider;
use OCA\Giphy\Service\GiphyAPIService;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Collaboration\Reference\IReferenceManager;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use PHPUnit\Framework\TestCase;

class GiphyReferenceProviderTest extends TestCase {

	private const GIF_URL = 'https://giphy.com/gifs/seal-sappy-seals-BaDsH4FpMBnqdK8J0g';

	/** @var array<string, bool> */
	private array $reads = [];

	/**
	 * @param array<string, string> $stored the admin values in the app config, missing keys get the default
	 */
	private function provider(array $stored): GiphyReferenceProvider {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->expects($this->never())->method('getAppValue');
		$appConfig->method('getAppValueString')
			->willReturnCallback(function (string $key, string $default = '', bool $lazy = false) use ($stored): string {
				$this->reads[$key] = $lazy;
				return $stored[$key] ?? $default;
			});

		// no user preference stored
		$config = $this->createMock(IConfig::class);
		$config->expects($this->never())->method('getAppValue');
		$config->method('getUserValue')
			->willReturnCallback(fn (string $userId, string $appId, string $key, $default = '') => $default);

		return new GiphyReferenceProvider(
			$this->createMock(GiphyAPIService::class),
			$appConfig,
			$config,
			$this->createMock(IL10N::class),
			$this->createMock(IURLGenerator::class),
			$this->createMock(IReferenceManager::class),
			$this->createMock(LinkReferenceProvider::class),
			'alice',
		);
	}

	/**
	 * The admin switch is read on every request by Application::register(), so it
	 * is non-lazy and must be read that way here as well. Link previews are on
	 * when nothing is stored.
	 */
	public function testLinkPreviewSwitchIsReadNonLazyAndDefaultsToOn(): void {
		$this->assertTrue($this->provider([])->matchReference(self::GIF_URL));
		$this->assertSame(['link_preview_enabled' => false], $this->reads);
	}

	public function testDisabledLinkPreviewsMatchNothing(): void {
		$this->assertFalse($this->provider(['link_preview_enabled' => '0'])->matchReference(self::GIF_URL));
	}
}
