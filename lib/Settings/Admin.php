<?php
namespace OCA\Giphy\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;
use OCA\Giphy\AppInfo\Application;

class Admin implements ISettings {

	public function __construct(private IConfig $config,
								private IInitialState $initialStateService) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$apiKey = $this->config->getAppValue(Application::APP_ID, 'api_key');
		$linkPreviewEnabled = $this->config->getAppValue(Application::APP_ID, 'link_preview_enabled', '1') === '1';
		$searchEnabled = $this->config->getAppValue(Application::APP_ID, 'search_gifs_enabled', '1') === '1';
		$rating = $this->config->getAppValue(Application::APP_ID, 'rating', Application::DEFAULT_RATING) ?: Application::DEFAULT_RATING;

		$adminConfig = [
			'api_key' => $apiKey,
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
