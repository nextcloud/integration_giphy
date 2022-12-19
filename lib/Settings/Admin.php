<?php
namespace OCA\Giphy\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;
use OCA\Giphy\AppInfo\Application;

class Admin implements ISettings {

	private IConfig $config;
	private IInitialState $initialStateService;

	public function __construct(IConfig $config,
								IInitialState $initialStateService) {
		$this->config = $config;
		$this->initialStateService = $initialStateService;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$apiKey = $this->config->getAppValue(Application::APP_ID, 'api_key');
		$linkPreviewEnabled = $this->config->getAppValue(Application::APP_ID, 'link_preview_enabled', '1') === '1';
		$searchEnabled = $this->config->getAppValue(Application::APP_ID, 'search_gifs_enabled', '1') === '1';

		$adminConfig = [
			'api_key' => $apiKey,
			'link_preview_enabled' => $linkPreviewEnabled,
			'search_gifs_enabled' => $searchEnabled,
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
