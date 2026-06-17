<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Giphy;

use OCP\AppFramework\Services\IAppConfig;
use OCP\Capabilities\ICapability;

/**
 * Class Capabilities
 *
 * @package OCA\Giphy
 */
class Capabilities implements ICapability {

	public function __construct(
		private readonly IAppConfig $appConfig,
	) {
	}

	/**
	 * @inheritDoc
	 * @return array<string, array<string, bool>>
	 */
	#[\Override]
	public function getCapabilities(): array {
		$apiKey = $this->appConfig->getAppValueString('api_key');

		return [
			'integration_giphy' => [
				'enabled' => true,
				'configured' => $apiKey !== '',
			],
		];
	}
}
