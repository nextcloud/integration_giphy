<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Giphy\Controller;

use OCA\Giphy\Service\GiphyAPIService;
use OCA\Giphy\Service\GiphySearchService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;

use OCP\IRequest;
use OCP\Search\SearchResultEntry;

class GiphyAPIController extends OCSController {

	public function __construct(
		string $appName,
		IRequest $request,
		private GiphyAPIService $giphyAPIService,
		private GiphySearchService $giphySearchService,
		?string $userId,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get gif content
	 * @param string $gifId
	 * @param string $preferredVersion
	 * @return DataDisplayResponse The gif image content
	 * @throws \Exception
	 */
	#[PublicPage]
	public function getGifFromId(string $gifId, string $preferredVersion = 'original'): DataDisplayResponse {
		$gif = $this->giphyAPIService->getGifFromId($gifId, $preferredVersion);
		if ($gif !== null && isset($gif['body'], $gif['headers'])) {
			$response = new DataDisplayResponse(
				$gif['body'],
				Http::STATUS_OK,
				['Content-Type' => $gif['headers']['Content-Type'][0] ?? 'image/gif']
			);
			$response->cacheFor(60 * 60 * 24, false, true);
			return $response;
		}
		return new DataDisplayResponse('', Http::STATUS_NOT_FOUND);
	}

	/**
	 * Get gif content
	 * Used by the top-right unified search because we can't inject the request token to pass the CSRF check
	 *
	 * @param string $gifId
	 * @param string $domainPrefix
	 * @param string $fileName
	 * @param string $cid
	 * @param string $rid
	 * @param string $ct
	 * @return DataDisplayResponse The gif image content
	 * @throws \Exception
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function privateGetGifFromDirectUrl(string $gifId, string $domainPrefix, string $fileName, string $cid, string $rid, string $ct): DataDisplayResponse {
		return $this->getGifFromDirectUrl($gifId, $domainPrefix, $fileName, $cid, $rid, $ct);
	}

	/**
	 * Get gif content
	 * Used by the reference widget and the picker component
	 * Public because the picker can be used in public pages
	 *
	 * @param string $gifId
	 * @param string $domainPrefix
	 * @param string $fileName
	 * @param string $cid
	 * @param string $rid
	 * @param string $ct
	 * @return DataDisplayResponse The gif image content
	 * @throws \Exception
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function getGifFromDirectUrl(string $gifId, string $domainPrefix, string $fileName, string $cid, string $rid, string $ct): DataDisplayResponse {
		$gif = $this->giphyAPIService->getGifFromDirectUrl($gifId, $domainPrefix, $fileName, $cid, $rid, $ct);
		if (isset($gif['body'], $gif['headers'])) {
			$response = new DataDisplayResponse(
				$gif['body'],
				Http::STATUS_OK,
				['Content-Type' => $gif['headers']['Content-Type'][0] ?? 'image/gif']
			);
			$response->cacheFor(60 * 60 * 24, false, true);
			return $response;
		}
		return new DataDisplayResponse('', Http::STATUS_NOT_FOUND);
	}

	/**
	 * @param int $cursor
	 * @param int $limit
	 * @return DataResponse
	 */
	#[PublicPage]
	public function getTrendingGifs(int $cursor = 0, int $limit = 10): DataResponse {
		$gifs = $this->giphyAPIService->getTrendingGifs($cursor, $limit);
		if (isset($gifs['error'])) {
			return new DataResponse($gifs, Http::STATUS_BAD_REQUEST);
		}

		$formattedEntries = array_map(function (array $gif): SearchResultEntry {
			return $this->giphySearchService->getSearchResultFromAPIEntry($gif);
		}, $gifs);
		$responseData = [
			'entries' => $formattedEntries,
			'cursor' => $cursor + count($formattedEntries),
		];
		$response = new DataResponse($responseData);
		$response->cacheFor(60 * 60 * 24, false, true);
		return $response;
	}

	/**
	 * @param string $term
	 * @param int $cursor
	 * @param int $limit
	 * @return DataResponse
	 */
	#[PublicPage]
	public function searchGifs(string $term, int $cursor = 0, int $limit = 10): DataResponse {
		$gifs = $this->giphyAPIService->searchGifs($term, $cursor, $limit);
		if (isset($gifs['error'])) {
			return new DataResponse($gifs, Http::STATUS_BAD_REQUEST);
		}

		$formattedEntries = array_map(function (array $gif): SearchResultEntry {
			return $this->giphySearchService->getSearchResultFromAPIEntry($gif);
		}, $gifs);
		$responseData = [
			'entries' => $formattedEntries,
			'cursor' => $cursor + count($formattedEntries),
		];
		$response = new DataResponse($responseData);
		$response->cacheFor(60 * 60 * 24, false, true);
		return $response;
	}
}
