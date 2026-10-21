<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Giphy\Tests\Search;

use OCA\Giphy\Search\GiphySearchGifsProvider;
use OCA\Giphy\Service\GiphyAPIService;
use OCA\Giphy\Service\GiphySearchService;
use OCP\App\IAppManager;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUser;
use OCP\Search\ISearchQuery;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GiphySearchGifsProviderTest extends TestCase {

	/** @var array<string, bool> */
	private array $reads = [];
	private GiphyAPIService|MockObject $giphyAPIService;

	/**
	 * Runs a unified search outside the smart picker, where the switches apply.
	 *
	 * @param array<string, string> $stored the admin values in the app config, missing keys get the default
	 */
	private function search(array $stored): void {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->expects($this->never())->method('getAppValue');
		$appConfig->method('getAppValueString')
			->willReturnCallback(function (string $key, string $default = '', bool $lazy = false) use ($stored): string {
				$this->reads[$key] = $lazy;
				return $stored[$key] ?? $default;
			});

		// no user preference stored
		$config = $this->createMock(IConfig::class);
		$config->expects($this->never())->method('getAppValue');
		$config->method('getUserValue')
			->willReturnCallback(fn (string $userId, string $appId, string $key, $default = '') => $default);

		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('isEnabledForUser')->willReturn(true);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$query = $this->createMock(ISearchQuery::class);
		$query->method('getTerm')->willReturn('cats');
		$query->method('getLimit')->willReturn(5);
		$query->method('getRoute')->willReturn('files.view.index');

		$provider = new GiphySearchGifsProvider(
			$appManager,
			$this->createMock(IL10N::class),
			$appConfig,
			$config,
			$this->giphyAPIService,
			$this->createMock(GiphySearchService::class),
		);
		$provider->search($user, $query);
	}

	protected function setUp(): void {
		parent::setUp();
		$this->giphyAPIService = $this->createMock(GiphyAPIService::class);
	}

	/**
	 * The admin switch is read on every request by Application::register(), so it
	 * is non-lazy and must be read that way here as well. Search is on when
	 * nothing is stored.
	 */
	public function testSearchSwitchIsReadNonLazyAndDefaultsToOn(): void {
		$this->giphyAPIService->expects($this->once())
			->method('searchGifs')
			->with('cats', 0, 5)
			->willReturn([]);

		$this->search([]);

		$this->assertSame(['search_gifs_enabled' => false], $this->reads);
	}

	public function testDisabledSearchDoesNotAskGiphy(): void {
		$this->giphyAPIService->expects($this->never())->method('searchGifs');

		$this->search(['search_gifs_enabled' => '0']);
	}
}
