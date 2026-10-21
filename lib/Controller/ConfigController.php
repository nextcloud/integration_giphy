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
use OCP\AppFramework\Services\IAppConfig;
use OCP\IConfig;
use OCP\IRequest;
use OCP\PreConditionNotMetException;
use OCP\Security\ICrypto;

class ConfigController extends Controller {

	/**
	 * Keys the admin settings send to setAdminConfig()
	 */
	private const ADMIN_KEYS = ['link_preview_enabled', 'search_gifs_enabled', 'rating'];

	/**
	 * Keys the admin settings send to setSensitiveAdminConfig()
	 */
	private const SENSITIVE_ADMIN_KEYS = ['api_key'];

	/**
	 * Admin keys that only the app's own code paths read, stored as lazy app config
	 */
	private const LAZY_KEYS = ['rating'];

	public function __construct(
		string $appName,
		IRequest $request,
		private IAppConfig $appConfig,
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
		if (!$this->onlyAllowedStringValues($values, self::ADMIN_KEYS)) {
			return new DataResponse('', Http::STATUS_BAD_REQUEST);
		}
		foreach ($values as $key => $value) {
			$this->appConfig->setAppValueString($key, $value, lazy: in_array($key, self::LAZY_KEYS, true));
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
		if (!$this->onlyAllowedStringValues($values, self::SENSITIVE_ADMIN_KEYS)) {
			return new DataResponse('', Http::STATUS_BAD_REQUEST);
		}
		foreach ($values as $key => $value) {
			if ($key === 'api_key' && $value !== '') {
				$encryptedValue = $this->crypto->encrypt($value);
				$this->appConfig->setAppValueString($key, $encryptedValue);
			} else {
				$this->appConfig->setAppValueString($key, $value);
			}
		}
		return new DataResponse(1);
	}

	/**
	 * Whether every key is one the settings page sends and every value is a string
	 *
	 * Checked before anything is stored, so a rejected request changes nothing.
	 *
	 * @param array $values key/value pairs of the request
	 * @param list<string> $allowedKeys
	 */
	private function onlyAllowedStringValues(array $values, array $allowedKeys): bool {
		foreach ($values as $key => $value) {
			if (!in_array($key, $allowedKeys, true) || !is_string($value)) {
				return false;
			}
		}
		return true;
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
