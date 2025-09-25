<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="gif-reference">
		<div class="gif-wrapper">
			<div v-if="!isLoaded && gifsEnabled" class="loading-icon">
				<NcLoadingIcon
					:size="44"
					:title="t('integration_giphy', 'Loading GIF')" />
			</div>
			<NcButton
				class="toggle-gifs-button"
				:variant="gifsEnabled ? 'secondary' : 'primary'"
				:title="hideButtonTitle"
				@click="handleGifsBtn">
				<template #icon>
					<EyeOffIcon :size="24" />
				</template>
			</NcButton>
			<p v-show="!gifsEnabled" class="gifs-disabled">
				{{ t('integration_giphy', 'GIFs are disabled') }}
			</p>
			<div v-show="gifsEnabled">
				<img v-show="isLoaded"
					class="image"
					:src="proxiedUrl"
					@load="isLoaded = true">
				<a v-show="isLoaded"
					class="attribution"
					target="_blank"
					:title="poweredByTitle"
					href="https://giphy.com">
					<img :src="poweredByImgSrc" :alt="poweredByTitle">
				</a>
			</div>
		</div>
	</div>
</template>

<script>
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcButton from '@nextcloud/vue/components/NcButton'
import EyeOffIcon from 'vue-material-design-icons/EyeOff.vue'

import { imagePath } from '@nextcloud/router'
import { getRequestToken } from '@nextcloud/auth'
import { emit, subscribe } from '@nextcloud/event-bus'

export default {
	name: 'GifReferenceWidget',

	components: {
		NcLoadingIcon,
		NcButton,
		EyeOffIcon,
	},

	props: {
		richObjectType: {
			type: String,
			default: '',
		},
		richObject: {
			type: Object,
			default: null,
		},
		accessible: {
			type: Boolean,
			default: true,
		},
	},

	data() {
		return {
			gifsEnabled: true,
			isLoaded: false,
			poweredByImgSrc: imagePath('integration_giphy', 'powered-by-giphy-badge.gif'),
			poweredByTitle: t('integration_giphy', 'Powered by Giphy'),
		}
	},

	computed: {
		proxiedUrl() {
			return this.richObject.proxied_url
				? this.richObject.proxied_url + '?requesttoken=' + encodeURIComponent(getRequestToken())
				: ''
		},
		hideButtonTitle() {
			return this.gifsEnabled
				? t('integration_giphy', 'Fold all Giphy GIFs')
				: t('integration_giphy', 'Unfold all Giphy GIFs')
		},
	},

	mounted() {
		subscribe('integration_giphy:gifs:enabled', (state) => {
			this.gifsEnabled = !!state
		})
	},

	methods: {
		handleGifsBtn() {
			this.gifsEnabled = !this.gifsEnabled
			emit('integration_giphy:gifs:enabled', this.gifsEnabled)
		},
	},
}
</script>

<style scoped lang="scss">
.gif-reference {
	width: 100%;
	padding: 12px;

	.gif-wrapper {
		width: 100%;
		display: flex;
		align-items: center;
		justify-content: center;
		position: relative;

		.toggle-gifs-button {
			position: absolute;
			top: 0;
			right: 0;
			padding: 0;
			border-radius: 50%;
		}

		.gifs-disabled {
			margin: 12px !important;
			white-space: initial;
		}

		.image {
			max-height: 300px;
			max-width: 100%;
			border-radius: var(--border-radius);
		}

		.attribution {
			position: absolute;
			left: 0;
			bottom: 0;
			height: 70px;
			img {
				border-radius: var(--border-radius);
				height: 100%;
			}
		}
	}
}
</style>
