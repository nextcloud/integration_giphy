<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Giphy\Tests\Migration;

use OCA\Giphy\AppInfo\Application;
use OCA\Giphy\Migration\Version020000Date20241002151118;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Security\ICrypto;
use PHPUnit\Framework\TestCase;

class Version020000Date20241002151118Test extends TestCase {

	public function testApiKeyIsEncryptedAndStaysNonLazy(): void {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->expects($this->once())
			->method('getValueString')
			->with(Application::APP_ID, 'api_key', '', false)
			->willReturn('plain-key');
		$appConfig->expects($this->once())
			->method('setValueString')
			->with(Application::APP_ID, 'api_key', 'ciphertext', false)
			->willReturn(true);

		$crypto = $this->createMock(ICrypto::class);
		$crypto->expects($this->once())
			->method('encrypt')
			->with('plain-key')
			->willReturn('ciphertext');

		$migration = new Version020000Date20241002151118($crypto, $appConfig);
		$migration->postSchemaChange($this->createMock(IOutput::class), static fn () => null, []);
	}
}
