<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Giphy\Tests\Settings;

use OCA\Giphy\AppInfo\Application;
use OCA\Giphy\Settings\Admin;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Security\ICrypto;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AdminTest extends TestCase {

	private IConfig|MockObject $config;
	private ICrypto|MockObject $crypto;
	private IInitialState|MockObject $initialState;
	private LoggerInterface|MockObject $logger;
	private Admin $admin;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->admin = new Admin($this->config, $this->crypto, $this->initialState, $this->logger);
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
		$this->config->method('getAppValue')
			->willReturnCallback(function (string $app, string $key, string $default = '') use ($apiKey): string {
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

	public function testSectionAndPriority(): void {
		$this->assertSame('connected-accounts', $this->admin->getSection());
		$this->assertSame(10, $this->admin->getPriority());
		$this->assertSame('integration_giphy', Application::APP_ID);
	}
}
