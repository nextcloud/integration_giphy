<?php
/**
 * @copyright Copyright (c) 2022 Julien Veyssier <eneiluj@posteo.net>
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Giphy\Reference;

use OC\Collaboration\Reference\LinkReferenceProvider;
use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;
use OCP\Collaboration\Reference\Reference;
use OC\Collaboration\Reference\ReferenceManager;
use OCA\Giphy\AppInfo\Application;
use OCA\Giphy\Service\GiphyAPIService;
use OCP\Collaboration\Reference\IReference;
use OCP\IConfig;
use OCP\IL10N;

use OCP\IURLGenerator;

class GiphyReferenceProvider extends ADiscoverableReferenceProvider {

	private const RICH_OBJECT_TYPE = Application::APP_ID . '_gif';

	private GiphyAPIService $giphyAPIService;
	private ?string $userId;
	private IConfig $config;
	private ReferenceManager $referenceManager;
	private IL10N $l10n;
	private IURLGenerator $urlGenerator;
	private LinkReferenceProvider $linkReferenceProvider;

	public function __construct(GiphyAPIService $giphyAPIService,
								IConfig $config,
								IL10N $l10n,
								IURLGenerator $urlGenerator,
								ReferenceManager $referenceManager,
								LinkReferenceProvider $linkReferenceProvider,
								?string $userId) {
		$this->giphyAPIService = $giphyAPIService;
		$this->userId = $userId;
		$this->config = $config;
		$this->referenceManager = $referenceManager;
		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;
		$this->linkReferenceProvider = $linkReferenceProvider;
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string	{
		return 'giphy-gif';
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): string {
		return $this->l10n->t('GIF picker (by Giphy)');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(): int	{
		return 10;
	}

	/**
	 * @inheritDoc
	 */
	public function getIconUrl(): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg')
		);
	}

	/**
	 * @inheritDoc
	 */
	public function matchReference(string $referenceText): bool {
		$adminLinkPreviewEnabled = $this->config->getAppValue(Application::APP_ID, 'link_preview_enabled', '1') === '1';
		if (!$adminLinkPreviewEnabled) {
			return false;
		}
		// 2 types of supported links:
		// https://giphy.com/gifs/seal-sappy-seals-BaDsH4FpMBnqdK8J0g
		// https://media.giphy.com/media/BaDsH4FpMBnqdK8J0g/giphy.gif
		return preg_match('/^(?:https?:\/\/)?(?:www\.)?giphy\.com\/gifs\/[^\/?&]+$/i', $referenceText) === 1
			|| preg_match('/^(?:https?:\/\/)?(?:www\.)?media\.giphy\.com\/media\/[^\/?&]+\/giphy\.gif$/i', $referenceText) === 1;
	}

	/**
	 * @inheritDoc
	 */
	public function resolveReference(string $referenceText): ?IReference {
		if ($this->matchReference($referenceText)) {
			$gifId = $this->getGifId($referenceText);
			if ($gifId !== null) {
				$gifInfo = $this->giphyAPIService->getGifInfo($gifId);
				if ($gifInfo !== null
					&& isset(
						$gifInfo['title'], $gifInfo['slug'], $gifInfo['images'],
						$gifInfo['images']['original'], $gifInfo['images']['original']['url']
					)
				) {
					$reference = new Reference($referenceText);
					$reference->setTitle($gifInfo['title'] ?? 'Unknown title');
					$reference->setDescription($gifInfo['username'] ?? $gifInfo['slug'] ?? $gifId);
					$imageUrl = $this->giphyAPIService->getGifProxiedUrl($gifInfo);
					$reference->setImageUrl($imageUrl);

					$gifInfo['proxied_url'] = $imageUrl;
					$reference->setRichObject(
						self::RICH_OBJECT_TYPE,
						$gifInfo,
					);
					return $reference;
				}
			}
			// fallback to opengraph
			return $this->linkReferenceProvider->resolveReference($referenceText);
		}

		return null;
	}

	/**
	 * @param string $url
	 * @return array|null
	 */
	private function getGifId(string $url): ?string {
		preg_match('/^(?:https?:\/\/)?(?:www\.)?giphy\.com\/gifs\/([^\/?&]+)$/i', $url, $matches);
		if (count($matches) > 1) {
			$slug = $matches[1];
			$parts = explode('-', $slug);
			return end($parts);
		}

		preg_match('/^(?:https?:\/\/)?(?:www\.)?media\.giphy\.com\/media\/([^\/?&]+)\/giphy\.gif$/i', $url, $matches);
		if (count($matches) > 1) {
			return $matches[1];
		}

		return null;
	}

	/**
	 * We use the userId here because when connecting/disconnecting from the GitHub account,
	 * we want to invalidate all the user cache and this is only possible with the cache prefix
	 * @inheritDoc
	 */
	public function getCachePrefix(string $referenceId): string {
		return $this->userId ?? '';
	}

	/**
	 * We don't use the userId here but rather a reference unique id
	 * @inheritDoc
	 */
	public function getCacheKey(string $referenceId): ?string {
		$gifId = $this->getGifId($referenceId);
		if ($gifId !== null) {
			return $gifId;
		}

		return $referenceId;
	}

	/**
	 * @param string $userId
	 * @return void
	 */
	public function invalidateUserCache(string $userId): void {
		$this->referenceManager->invalidateCache($userId);
	}
}
