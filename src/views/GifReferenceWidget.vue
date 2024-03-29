<!--
  - @copyright Copyright (c) 2022 Julien Veyssier <julien-nc@posteo.net>
  -
  - @author 2022 Julien Veyssier <julien-nc@posteo.net>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
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
				:type="gifsEnabled ? 'secondary' : 'primary'"
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
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
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
