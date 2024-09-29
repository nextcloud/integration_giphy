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
use OCP\Settings\ISettings;

class Personal implements ISettings {

	public function __construct(
		private IConfig $config,
		private IInitialState $initialStateService,
		private ?string $userId
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$adminLinkPreviewEnabled = $this->config->getAppValue(Application::APP_ID, 'link_preview_enabled', '1') === '1';
		$linkPreviewEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'link_preview_enabled', '1') === '1';

		$adminSearchEnabled = $this->config->getAppValue(Application::APP_ID, 'search_gifs_enabled', '1') === '1';
		$userSearchEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'search_gifs_enabled', '0') === '1';

		$userConfig = [
			'admin_link_preview_enabled' => $adminLinkPreviewEnabled,
			'link_preview_enabled' => $linkPreviewEnabled,
			'search_gifs_enabled' => $userSearchEnabled,
			'admin_search_gifs_enabled' => $adminSearchEnabled,
		];
		$this->initialStateService->provideInitialState('user-config', $userConfig);
		return new TemplateResponse(Application::APP_ID, 'personalSettings');
	}

	public function getSection(): string {
		return 'connected-accounts';
	}

	public function getPriority(): int {
		return 10;
	}
}
