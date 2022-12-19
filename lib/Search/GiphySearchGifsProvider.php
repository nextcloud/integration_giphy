<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022, Julien Veyssier
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Giphy\Search;

use OCA\Giphy\Service\GiphyAPIService;
use OCA\Giphy\AppInfo\Application;
use OCP\App\IAppManager;
use OCP\IL10N;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;

class GiphySearchGifsProvider implements IProvider {

	private IAppManager $appManager;
	private IL10N $l10n;
	private IConfig $config;
	private GiphyAPIService $service;

	public function __construct(IAppManager     $appManager,
								IL10N           $l10n,
								IConfig         $config,
								GiphyAPIService $service) {
		$this->appManager = $appManager;
		$this->l10n = $l10n;
		$this->config = $config;
		$this->service = $service;
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'giphy-search-gifs';
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l10n->t('Giphy gifs');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(string $route, array $routeParameters): int {
		if (strpos($route, Application::APP_ID . '.') === 0) {
			// Active app, prefer Giphy results
			return -1;
		}

		return 20;
	}

	/**
	 * @inheritDoc
	 */
	public function search(IUser $user, ISearchQuery $query): SearchResult {
		if (!$this->appManager->isEnabledForUser(Application::APP_ID, $user)) {
			return SearchResult::complete($this->getName(), []);
		}

		$limit = $query->getLimit();
		$term = $query->getTerm();
		$offset = $query->getCursor();
		$offset = $offset ? intval($offset) : 0;

		$searchGifsEnabled = $this->config->getAppValue(Application::APP_ID, 'search_gifs_enabled', '1') === '1';
		if (!$searchGifsEnabled) {
			return SearchResult::paginated($this->getName(), [], 0);
		}

		$searchResult = $this->service->searchGifs($term, $offset, $limit);
		if (isset($searchResult['error'])) {
			$gifs = [];
		} else {
			$gifs = $searchResult;
		}

		$formattedResults = array_map(function (array $gif): GiphySearchResultEntry {
			return new GiphySearchResultEntry(
				$this->getThumbnailUrl($gif),
				$this->getMainText($gif),
				$this->getSubline($gif),
				$this->getLinkToGiphy($gif),
				'',
				false
			);
		}, $gifs);

		return SearchResult::paginated(
			$this->getName(),
			$formattedResults,
			$offset + $limit
		);
	}

	/**
	 * @param array $entry
	 * @return string
	 */
	protected function getMainText(array $entry): string {
		return $entry['title'] ?? 'Unknown title';
	}

	/**
	 * @param array $entry
	 * @return string
	 */
	protected function getSubline(array $entry): string {
		return $entry['username'] ?? $entry['slug'] ?? '';
	}

	/**
	 * @param array $entry
	 * @return string
	 */
	protected function getLinkToGiphy(array $entry): string {
		return $entry['url'] ?? '';
	}

	/**
	 * @param array $entry
	 * @return string
	 */
	protected function getThumbnailUrl(array $entry): string {
		return $this->service->getGifProxiedUrl($entry, 'preview_gif');
	}
}
