<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Giphy\Tests\AppInfo;

use OCA\Giphy\AppInfo\Application;
use OCA\Giphy\Listener\GiphyReferenceListener;
use OCA\Giphy\Reference\GiphyReferenceProvider;
use OCA\Giphy\Search\GiphySearchGifsProvider;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Collaboration\Reference\RenderReferenceEvent;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase {

	/**
	 * register() runs on every request, so everything it reads has to come
	 * from the non-lazy app config that the server loads anyway. Search and
	 * link previews are on when nothing but an API key is stored.
	 */
	public function testRegisterReadsOnlyNonLazyKeys(): void {
		$reads = [];
		$appConfig = $this->createMock(IAppConfig::class);
		// the untyped method ignores lazy values
		$appConfig->expects($this->never())->method('getAppValue');
		$appConfig->method('getAppValueString')
			->willReturnCallback(function (string $key, string $default = '', bool $lazy = false) use (&$reads): string {
				$reads[$key] = $lazy;
				return $key === 'api_key' ? 'ciphertext' : $default;
			});

		$app = (new \ReflectionClass(Application::class))->newInstanceWithoutConstructor();
		(new \ReflectionProperty(Application::class, 'appConfig'))->setValue($app, $appConfig);

		$context = $this->createMock(IRegistrationContext::class);
		$context->expects($this->once())
			->method('registerSearchProvider')
			->with(GiphySearchGifsProvider::class);
		$context->expects($this->once())
			->method('registerReferenceProvider')
			->with(GiphyReferenceProvider::class);
		$context->expects($this->once())
			->method('registerEventListener')
			->with(RenderReferenceEvent::class, GiphyReferenceListener::class);

		$app->register($context);

		$this->assertSame([
			'search_gifs_enabled' => false,
			'link_preview_enabled' => false,
			'api_key' => false,
		], $reads);
	}
}
