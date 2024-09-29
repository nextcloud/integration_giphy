<?php
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Giphy\Reference;

use OC\Collaboration\Reference\LinkReferenceProvider;
use OC\Collaboration\Reference\ReferenceManager;
use OCA\Giphy\AppInfo\Application;
use OCA\Giphy\Service\GiphyAPIService;
use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;
use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\ISearchableReferenceProvider;
use OCP\Collaboration\Reference\Reference;
use OCP\IConfig;
use OCP\IL10N;

use OCP\IURLGenerator;

class GiphyReferenceProvider extends ADiscoverableReferenceProvider implements ISearchableReferenceProvider {

	private const RICH_OBJECT_TYPE = Application::APP_ID . '_gif';

	public function __construct(
		private GiphyAPIService $giphyAPIService,
		private IConfig $config,
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private ReferenceManager $referenceManager,
		private LinkReferenceProvider $linkReferenceProvider,
		private ?string $userId
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
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
	public function getOrder(): int {
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
	public function getSupportedSearchProviderIds(): array {
		return ['giphy-search-gifs'];
	}

	/**
	 * @inheritDoc
	 */
	public function matchReference(string $referenceText): bool {
		return $this->getGifId($referenceText) !== null;
	}

	/**
	 * @inheritDoc
	 */
	public function resolveReference(string $referenceText): ?IReference {
		if (!$this->matchReference($referenceText)) {
			return null;
		}
		$adminLinkPreviewEnabled = $this->config->getAppValue(Application::APP_ID, 'link_preview_enabled', '1') === '1';
		if (!$adminLinkPreviewEnabled) {
			return null;
		}
		if ($this->userId !== null) {
			$userLinkPreviewEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'link_preview_enabled', '1') === '1';
			if (!$userLinkPreviewEnabled) {
				return null;
			}
		}

		$gifId = $this->getGifId($referenceText);
		if ($gifId !== null) {
			$gifInfo = $this->giphyAPIService->getGifInfo($gifId);
			$reference = new Reference($referenceText);
			if ($gifInfo !== null
				&& isset(
					$gifInfo['title'], $gifInfo['slug'], $gifInfo['images'],
					$gifInfo['images']['original'], $gifInfo['images']['original']['url']
				)
			) {
				$reference->setTitle($gifInfo['title'] ?? 'Unknown title');
				$reference->setDescription($gifInfo['username'] ?? $gifInfo['slug'] ?? $gifId);
				$imageUrl = $this->giphyAPIService->getGifProxiedUrl($gifInfo);
				$reference->setImageUrl($imageUrl);

				$gifInfo['proxied_url'] = $imageUrl;
				$gifInfo['image_gif'] = true;
				$reference->setRichObject(
					self::RICH_OBJECT_TYPE,
					$gifInfo,
				);
			} else {
				$reference->setDescription($this->l10n->t('GIF not found'));
			}
			return $reference;
		}
		// fallback to opengraph
		return $this->linkReferenceProvider->resolveReference($referenceText);
	}

	/**
	 * @param string $url
	 * @return array|null
	 */
	private function getGifId(string $url): ?string {
		// support 2 types of links:
		// https://giphy.com/gifs/seal-sappy-seals-BaDsH4FpMBnqdK8J0g
		preg_match('/^(?:https?:\/\/)?(?:www\.)?giphy\.com\/gifs\/([^\/?&]+)$/i', $url, $matches);
		if (count($matches) > 1) {
			$slug = $matches[1];
			$parts = explode('-', $slug);
			return end($parts);
		}

		// https://media.giphy.com/media/BaDsH4FpMBnqdK8J0g/giphy.gif
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
