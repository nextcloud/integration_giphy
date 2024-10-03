<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Giphy\Migration;

use Closure;
use OCA\Giphy\AppInfo\Application;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use OCP\Security\ICrypto;

class Version020000Date20241002151118 extends SimpleMigrationStep {

	public function __construct(
		private ICrypto $crypto,
		private IConfig $config,
	) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
		$apiKey = $this->config->getAppValue(Application::APP_ID, 'api_key');
		if ($apiKey !== '') {
			$encryptedValue = $this->crypto->encrypt($apiKey);
			$this->config->setAppValue(Application::APP_ID, 'api_key', $encryptedValue);
		}
	}
}
