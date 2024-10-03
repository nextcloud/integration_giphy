<?php
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Giphy\Service;

use OCP\Search\SearchResultEntry;

/**
 * Service to make requests to Giphy REST API
 */
class GiphySearchService {

	public function __construct(string $appName,
		private GiphyAPIService $giphyAPIService) {
	}

	/**
	 * @param array $entry
	 * @param bool $private
	 * @return SearchResultEntry
	 */
	public function getSearchResultFromAPIEntry(array $entry, bool $private = false): SearchResultEntry {
		return new SearchResultEntry(
			$this->getThumbnailUrl($entry, $private),
			$this->getMainText($entry),
			$this->getSubline($entry),
			$this->getLinkToGiphy($entry),
			$this->getIcon($entry),
			false
		);
	}

	/**
	 * @param array $entry
	 * @return string
	 */
	private function getMainText(array $entry): string {
		return $entry['title'] ?? 'Unknown title';
	}

	/**
	 * @param array $entry
	 * @return string
	 */
	private function getSubline(array $entry): string {
		return $entry['username'] ?? $entry['slug'] ?? '';
	}

	/**
	 * @param array $entry
	 * @return string
	 */
	private function getLinkToGiphy(array $entry): string {
		return $entry['url'] ?? '';
	}

	/**
	 * @param array $entry
	 * @return string
	 */
	private function getIcon(array $entry): string {
		return '';
	}

	/**
	 * @param array $entry
	 * @param bool $private
	 * @return string
	 */
	private function getThumbnailUrl(array $entry, bool $private): string {
		return $this->giphyAPIService->getGifProxiedUrl($entry, 'fixed_width', $private);
	}
}
