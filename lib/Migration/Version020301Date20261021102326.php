<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Giphy\Migration;

use Closure;
use OCA\Giphy\AppInfo\Application;
use OCP\DB\ISchemaWrapper;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version020301Date20261021102326 extends SimpleMigrationStep {

	public function __construct(
		private IAppConfig $appConfig,
	) {
	}

	/**
	 * Mark the app config values that were stored as non-lazy, but are only
	 * read by the app's own code paths, as lazy
	 *
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$this->appConfig->updateLazy(Application::APP_ID, 'rating', true);
	}
}
