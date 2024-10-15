<?php
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Giphy\AppInfo;

use OCA\Giphy\Listener\GiphyReferenceListener;
use OCA\Giphy\Reference\GiphyReferenceProvider;
use OCA\Giphy\Search\GiphySearchGifsProvider;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCP\IConfig;

class Application extends App implements IBootstrap {

	public const APP_ID = 'integration_giphy';
	public const DEFAULT_RATING = 'g';

	private IConfig $config;

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);

		$container = $this->getContainer();
		$this->config = $container->query(IConfig::class);
	}

	public function register(IRegistrationContext $context): void {
		$adminSearchGifsEnabled = $this->config->getAppValue(Application::APP_ID, 'search_gifs_enabled', '1') === '1';
		$adminLinkPreviewEnabled = $this->config->getAppValue(Application::APP_ID, 'link_preview_enabled', '1') === '1';
		$apiKey = $this->config->getAppValue(Application::APP_ID, 'api_key');

		if ($adminSearchGifsEnabled && $apiKey !== '') {
			$context->registerSearchProvider(GiphySearchGifsProvider::class);
		}

		if ($adminLinkPreviewEnabled && $apiKey !== '') {
			$context->registerReferenceProvider(GiphyReferenceProvider::class);
			$context->registerEventListener(RenderReferenceEvent::class, GiphyReferenceListener::class);
		}
	}

	public function boot(IBootContext $context): void {
	}
}
