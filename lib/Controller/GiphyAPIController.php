<?php
/**
 * Nextcloud - giphy
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2022
 */

namespace OCA\Giphy\Controller;

use OCA\Giphy\Search\GiphySearchResultEntry;
use OCA\Giphy\Service\GiphySearchService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

use OCA\Giphy\Service\GiphyAPIService;

class GiphyAPIController extends OCSController {

	private GiphyAPIService $giphyAPIService;
	private GiphySearchService $giphySearchService;

	public function __construct(string          $appName,
								IRequest        $request,
								GiphyAPIService $giphyAPIService,
								GiphySearchService $giphySearchService,
								?string         $userId) {
		parent::__construct($appName, $request);
		$this->giphyAPIService = $giphyAPIService;
		$this->giphySearchService = $giphySearchService;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * Get gif content
	 * @param string $gifId
	 * @param string $preferredVersion
	 * @return DataDisplayResponse The gif image content
	 * @throws \Exception
	 */
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
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * Get gif content
	 * @param string $gifId
	 * @param string $domainPrefix
	 * @param string $fileName
	 * @param string $cid
	 * @param string $rid
	 * @param string $ct
	 * @return DataDisplayResponse The gif image content
	 */
	public function getGifFromDirectUrl(string $gifId, string $domainPrefix, string $fileName, string $cid, string $rid, string $ct): DataDisplayResponse {
		$gif = $this->giphyAPIService->getGifFromDirectUrl($gifId, $domainPrefix, $fileName, $cid, $rid, $ct);
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
	 * @NoAdminRequired
	 *
	 * @param string $rating
	 * @param int $cursor
	 * @param int $limit
	 * @return DataResponse
	 */
	public function getTrendingGifs(string $rating = 'g', int $cursor = 0, int $limit = 10): DataResponse {
		$gifs = $this->giphyAPIService->getTrendingGifs($rating, $cursor, $limit);
		if (isset($gifs['error'])) {
			return new DataResponse($gifs, Http::STATUS_BAD_REQUEST);
		}

		$formattedEntries = array_map(function (array $gif): GiphySearchResultEntry {
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
