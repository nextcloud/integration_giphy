<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Giphy\Tests\Migration;

use OCA\Giphy\AppInfo\Application;
use OCA\Giphy\Migration\Version020301Date20261021102326;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use PHPUnit\Framework\TestCase;

class Version020301Date20261021102326Test extends TestCase {

	public function testOnlyTheRatingIsMarkedLazy(): void {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->expects($this->once())
			->method('updateLazy')
			->with(Application::APP_ID, 'rating', true)
			->willReturn(true);

		$migration = new Version020301Date20261021102326($appConfig);
		$migration->postSchemaChange($this->createMock(IOutput::class), static fn () => null, []);
	}
}
