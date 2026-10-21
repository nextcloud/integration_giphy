<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Giphy\Tests;

use OCA\Giphy\Capabilities;
use OCP\AppFramework\Services\IAppConfig;
use PHPUnit\Framework\TestCase;

class CapabilitiesTest extends TestCase {

	/** @var array<string, bool> */
	private array $reads = [];

	/**
	 * @param array<string, string> $stored the values in the app config, missing keys get the default
	 */
	private function capabilities(array $stored): array {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->expects($this->never())->method('getAppValue');
		$appConfig->method('getAppValueString')
			->willReturnCallback(function (string $key, string $default = '', bool $lazy = false) use ($stored): string {
				$this->reads[$key] = $lazy;
				return $stored[$key] ?? $default;
			});

		return (new Capabilities($appConfig))->getCapabilities();
	}

	/**
	 * The capabilities are part of every page, so the API key they check stays
	 * non-lazy.
	 */
	public function testApiKeyIsReadNonLazy(): void {
		$this->assertSame(
			['integration_giphy' => ['enabled' => true, 'configured' => true]],
			$this->capabilities(['api_key' => 'ciphertext']),
		);
		$this->assertSame(['api_key' => false], $this->reads);
	}

	public function testNotConfiguredWithoutApiKey(): void {
		$this->assertFalse($this->capabilities([])['integration_giphy']['configured']);
	}
}
