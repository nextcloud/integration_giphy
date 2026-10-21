<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Giphy\Tests\Settings;

use OCA\Giphy\AppInfo\Application;
use OCA\Giphy\Settings\Admin;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Services\IInitialState;
use OCP\Security\ICrypto;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AdminTest extends TestCase {

	private IAppConfig|MockObject $appConfig;
	private ICrypto|MockObject $crypto;
	private IInitialState|MockObject $initialState;
	private LoggerInterface|MockObject $logger;
	private Admin $admin;

	protected function setUp(): void {
		parent::setUp();
		$this->appConfig = $this->createMock(IAppConfig::class);
		// the untyped method ignores lazy values
		$this->appConfig->expects($this->never())->method('getAppValue');
		$this->crypto = $this->createMock(ICrypto::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->admin = new Admin($this->appConfig, $this->crypto, $this->initialState, $this->logger);
	}

	/**
	 * Returns the 'admin-config' array handed to the frontend.
	 */
	private function captureAdminConfig(): array {
		$captured = [];
		$this->initialState->method('provideInitialState')
			->willReturnCallback(function (string $key, $value) use (&$captured): void {
				if ($key === 'admin-config') {
					$captured = $value;
				}
			});
		$this->admin->getForm();
		return $captured;
	}

	private function configReturns(string $apiKey): void {
		$this->appConfig->method('getAppValueString')
			->willReturnCallback(function (string $key, string $default = '', bool $lazy = false) use ($apiKey): string {
				return $key === 'api_key' ? $apiKey : $default;
			});
	}

	public function testApiKeyIsMaskedWhenItDecrypts(): void {
		$this->configReturns('some-ciphertext');
		$this->crypto->expects($this->once())
			->method('decrypt')
			->with('some-ciphertext')
			->willReturn('the-real-key');

		$this->assertSame('dummyApiKey', $this->captureAdminConfig()['api_key']);
	}

	public function testNoApiKeyIsReportedWhenNoneIsStored(): void {
		$this->configReturns('');
		$this->crypto->expects($this->never())->method('decrypt');

		$this->assertSame('', $this->captureAdminConfig()['api_key']);
	}

	/**
	 * A stored value that is not valid ciphertext must not escape getForm():
	 * the settings controller renders every app's section, so an exception here
	 * takes down the entire "Connected accounts" page rather than just ours.
	 */
	public function testUndecryptableApiKeyDoesNotBreakTheSettingsPage(): void {
		$this->configReturns('not-actually-ciphertext');
		$this->crypto->method('decrypt')
			->willThrowException(new \Exception('Authenticated ciphertext could not be decoded.'));
		$this->logger->expects($this->once())->method('warning');

		$this->assertSame('', $this->captureAdminConfig()['api_key']);
	}

	/**
	 * The server only loads a lazy value for a lazy read, so the rating has to be
	 * read with lazy: true. The other keys are read on every request by
	 * Application::register() and must stay non-lazy.
	 */
	public function testOnlyTheRatingIsReadLazily(): void {
		$reads = [];
		$this->appConfig->method('getAppValueString')
			->willReturnCallback(function (string $key, string $default = '', bool $lazy = false) use (&$reads): string {
				$reads[$key] = $lazy;
				return ($key === 'rating' && $lazy) ? 'pg-13' : $default;
			});

		$this->assertSame('pg-13', $this->captureAdminConfig()['rating']);
		$this->assertSame([
			'api_key' => false,
			'link_preview_enabled' => false,
			'search_gifs_enabled' => false,
			'rating' => true,
		], $reads);
	}

	public function testDefaultsWhenNothingIsStored(): void {
		$this->configReturns('');

		$this->assertSame([
			'api_key' => '',
			'link_preview_enabled' => true,
			'search_gifs_enabled' => true,
			'rating' => 'g',
		], $this->captureAdminConfig());
	}

	public function testSectionAndPriority(): void {
		$this->assertSame('connected-accounts', $this->admin->getSection());
		$this->assertSame(10, $this->admin->getPriority());
		$this->assertSame('integration_giphy', Application::APP_ID);
	}
}
