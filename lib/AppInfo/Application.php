<?php
/**
 * Nextcloud - Giphy
 *
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2022
 */

namespace OCA\Giphy\AppInfo;

use OCA\Giphy\Listener\GiphyReferenceListener;
use OCA\Giphy\Reference\GiphyReferenceProvider;
use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCP\IConfig;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;

use OCA\Giphy\Search\GiphySearchGifsProvider;

class Application extends App implements IBootstrap {

	public const APP_ID = 'integration_giphy';
	public const DEFAULT_API_KEY = 'LebyjhpSc5GpX5xKSEtdxFIWMneLlrIF';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);

		$container = $this->getContainer();
		$this->container = $container;
		$this->config = $container->query(IConfig::class);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerSearchProvider(GiphySearchGifsProvider::class);

		$context->registerReferenceProvider(GiphyReferenceProvider::class);
//		$context->registerEventListener(RenderReferenceEvent::class, GiphyReferenceListener::class);
	}

	public function boot(IBootContext $context): void {
	}
}

