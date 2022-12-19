<?php
/**
 * Nextcloud - giphy
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2020
 */

namespace OCA\Giphy\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\IRequest;
use OCP\AppFramework\Controller;

use OCA\Giphy\Service\GiphyAPIService;

class GiphyAPIController extends Controller {

	private GiphyAPIService $giphyAPIService;

	public function __construct(string          $appName,
								IRequest        $request,
								GiphyAPIService $giphyAPIService,
								?string         $userId) {
		parent::__construct($appName, $request);
		$this->giphyAPIService = $giphyAPIService;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * Get gif content
	 * @param string $gifId
	 * @return DataDisplayResponse The gif image content
	 */
	public function getGifFromId(string $gifId): DataDisplayResponse {
		$gif = $this->giphyAPIService->getGifFromId($gifId);
		if ($gif !== null && isset($gif['body'], $gif['headers'])) {
			$response = new DataDisplayResponse(
				$gif['body'],
				Http::STATUS_OK,
				['Content-Type' => $gif['headers']['Content-Type'][0] ?? 'image/gif']
			);
			$response->cacheFor(60 * 60 * 24, false, true);
			return $response;
		}
		return new DataDisplayResponse('', 400);
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
		return new DataDisplayResponse('', 400);
	}
}
