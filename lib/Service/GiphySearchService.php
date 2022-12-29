<?php
/**
 * Nextcloud - Giphy
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier
 * @copyright Julien Veyssier 2022
 */

namespace OCA\Giphy\Service;

use OCA\Giphy\Search\GiphySearchResultEntry;

class GiphySearchService {
	private GiphyAPIService $giphyAPIService;

	/**
	 * Service to make requests to Giphy REST API
	 */
	public function __construct (string $appName,
								GiphyAPIService $giphyAPIService) {
		$this->giphyAPIService = $giphyAPIService;
	}

	/**
	 * @param array $entry
	 * @return GiphySearchResultEntry
	 */
	public function getSearchResultFromAPIEntry(array $entry): GiphySearchResultEntry {
		return new GiphySearchResultEntry(
			$this->getThumbnailUrl($entry),
			$this->getMainText($entry),
			$this->getSubline($entry),
			$this->getLinkToGiphy($entry),
			$this->getIconUrl($entry),
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
	private function getIconUrl(array $entry): string {
		return $this->giphyAPIService->getGifProxiedUrl($entry);
	}

	/**
	 * @param array $entry
	 * @return string
	 */
	private function getThumbnailUrl(array $entry): string {
		return $this->giphyAPIService->getGifProxiedUrl($entry, 'fixed_width');
	}
}
