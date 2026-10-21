<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Giphy\AppInfo;

use OCA\Giphy\Capabilities;
use OCA\Giphy\Listener\GiphyReferenceListener;
use OCA\Giphy\Reference\GiphyReferenceProvider;
use OCA\Giphy\Search\GiphySearchGifsProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Collaboration\Reference\RenderReferenceEvent;

class Application extends App implements IBootstrap {

	public const APP_ID = 'integration_giphy';
	public const DEFAULT_RATING = 'g';

	private IAppConfig $appConfig;

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);

		$container = $this->getContainer();
		$this->appConfig = $container->get(IAppConfig::class);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerCapability(Capabilities::class);

		$adminSearchGifsEnabled = $this->appConfig->getAppValueString('search_gifs_enabled', '1') === '1';
		$adminLinkPreviewEnabled = $this->appConfig->getAppValueString('link_preview_enabled', '1') === '1';
		$apiKey = $this->appConfig->getAppValueString('api_key');

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
