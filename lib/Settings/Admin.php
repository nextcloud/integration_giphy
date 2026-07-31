<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Giphy\Settings;

use OCA\Giphy\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Security\ICrypto;
use OCP\Settings\ISettings;
use Psr\Log\LoggerInterface;

class Admin implements ISettings {

	public function __construct(
		private IConfig $config,
		private ICrypto $crypto,
		private IInitialState $initialStateService,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$apiKey = $this->config->getAppValue(Application::APP_ID, 'api_key');
		if ($apiKey !== '') {
			try {
				$apiKey = $this->crypto->decrypt($apiKey);
			} catch (\Exception $e) {
				// Every app's admin section is rendered by the same controller, so
				// throwing here does not just break ours: it makes the whole
				// "Connected accounts" page fail. Treat an undecryptable key as unset.
				$this->logger->warning('Could not decrypt the Giphy API key, treating it as unset', [
					'exception' => $e,
					'app' => Application::APP_ID,
				]);
				$apiKey = '';
			}
		}

		$linkPreviewEnabled = $this->config->getAppValue(Application::APP_ID, 'link_preview_enabled', '1') === '1';
		$searchEnabled = $this->config->getAppValue(Application::APP_ID, 'search_gifs_enabled', '1') === '1';
		$rating = $this->config->getAppValue(Application::APP_ID, 'rating', Application::DEFAULT_RATING) ?: Application::DEFAULT_RATING;

		$adminConfig = [
			'api_key' => $apiKey === '' ? '' : 'dummyApiKey',
			'link_preview_enabled' => $linkPreviewEnabled,
			'search_gifs_enabled' => $searchEnabled,
			'rating' => $rating,
		];
		$this->initialStateService->provideInitialState('admin-config', $adminConfig);

		return new TemplateResponse(Application::APP_ID, 'adminSettings');
	}

	public function getSection(): string {
		return 'connected-accounts';
	}

	public function getPriority(): int {
		return 10;
	}
}
