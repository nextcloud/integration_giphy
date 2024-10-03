<?php
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Giphy\Controller;

use OCA\Giphy\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;

use OCP\IRequest;
use OCP\PreConditionNotMetException;
use OCP\Security\ICrypto;

class ConfigController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		private IConfig $config,
		private ICrypto $crypto,
		private ?string $userId,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Set admin config values
	 *
	 * @param array $values key/value pairs to store in app config
	 * @return DataResponse
	 */
	public function setAdminConfig(array $values): DataResponse {
		if (isset($values['api_key'])) {
			return new DataResponse('', Http::STATUS_BAD_REQUEST);
		}
		foreach ($values as $key => $value) {
			$this->config->setAppValue(Application::APP_ID, $key, $value);
		}
		return new DataResponse(1);
	}

	/**
	 * Set sensitive admin config values
	 *
	 * @param array $values key/value pairs to store in app config
	 * @return DataResponse
	 */
	#[PasswordConfirmationRequired]
	public function setSensitiveAdminConfig(array $values): DataResponse {
		foreach ($values as $key => $value) {
			if ($key === 'api_key' && $value !== '') {
				$encryptedValue = $this->crypto->encrypt($value);
				$this->config->setAppValue(Application::APP_ID, $key, $encryptedValue);
			} else {
				$this->config->setAppValue(Application::APP_ID, $key, $value);
			}
		}
		return new DataResponse(1);
	}

	/**
	 * Set user config values
	 *
	 * @param array $values key/value pairs to store in user settings
	 * @return DataResponse
	 * @throws PreConditionNotMetException
	 */
	#[NoAdminRequired]
	public function setUserConfig(array $values): DataResponse {
		foreach ($values as $key => $value) {
			$this->config->setUserValue($this->userId, Application::APP_ID, $key, $value);
		}
		return new DataResponse([]);
	}
}
