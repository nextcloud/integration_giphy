<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Giphy\Search;

use OCA\Giphy\AppInfo\Application;
use OCA\Giphy\Service\GiphyAPIService;
use OCA\Giphy\Service\GiphySearchService;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;

class GiphySearchGifsProvider implements IProvider {

	public function __construct(private IAppManager        $appManager,
		private IL10N              $l10n,
		private IConfig            $config,
		private GiphyAPIService    $giphyAPIService,
		private GiphySearchService $giphySearchService) {
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
		return $this->l10n->t('Giphy GIFs');
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

		$routeFrom = $query->getRoute();
		$requestedFromSmartPicker = $routeFrom === '' || $routeFrom === 'smart-picker';

		if (!$requestedFromSmartPicker) {
			$adminSearchGifsEnabled = $this->config->getAppValue(Application::APP_ID, 'search_gifs_enabled', '1') === '1';
			$userSearchGifsEnabled = $this->config->getUserValue($user->getUID(), Application::APP_ID, 'search_gifs_enabled', '1') === '1';
			if (!$adminSearchGifsEnabled || !$userSearchGifsEnabled) {
				return SearchResult::paginated($this->getName(), [], 0);
			}
		}

		$searchResult = $this->giphyAPIService->searchGifs($term, $offset, $limit);
		if (isset($searchResult['error'])) {
			$gifs = [];
		} else {
			$gifs = $searchResult;
		}

		$formattedResults = array_map(function (array $gif): SearchResultEntry {
			return $this->giphySearchService->getSearchResultFromAPIEntry($gif, true);
		}, $gifs);

		return SearchResult::paginated(
			$this->getName(),
			$formattedResults,
			$offset + $limit
		);
	}

}
