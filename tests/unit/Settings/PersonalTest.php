<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Giphy\Tests\Settings;

use OCA\Giphy\Settings\Personal;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use PHPUnit\Framework\TestCase;

class PersonalTest extends TestCase {

	/** @var array<string, bool> */
	private array $reads = [];

	/**
	 * Returns the 'user-config' array handed to the frontend.
	 *
	 * @param array<string, string> $stored the admin values in the app config, missing keys get the default
	 */
	private function userConfig(array $stored): array {
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

		$captured = [];
		$initialState = $this->createMock(IInitialState::class);
		$initialState->method('provideInitialState')
			->willReturnCallback(function (string $key, $value) use (&$captured): void {
				if ($key === 'user-config') {
					$captured = $value;
				}
			});

		(new Personal($appConfig, $config, $initialState, 'alice'))->getForm();
		return $captured;
	}

	/**
	 * Application::register() reads both admin switches on every request, so
	 * they are non-lazy and must be read that way here as well.
	 */
	public function testAdminSwitchesAreReadNonLazy(): void {
		$this->userConfig([]);

		$this->assertSame([
			'link_preview_enabled' => false,
			'search_gifs_enabled' => false,
		], $this->reads);
	}

	public function testDefaultsWhenNothingIsStored(): void {
		$this->assertSame([
			'admin_link_preview_enabled' => true,
			'link_preview_enabled' => true,
			'search_gifs_enabled' => false,
			'admin_search_gifs_enabled' => true,
		], $this->userConfig([]));
	}

	public function testDisabledAdminSwitchesAreShown(): void {
		$userConfig = $this->userConfig(['link_preview_enabled' => '0', 'search_gifs_enabled' => '0']);

		$this->assertFalse($userConfig['admin_link_preview_enabled']);
		$this->assertFalse($userConfig['admin_search_gifs_enabled']);
	}
}
