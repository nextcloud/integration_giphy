<template>
	<div class="gif-picker-content">
		<h2>
			{{ t('integration_giphy', 'Giphy GIF picker') }}
		</h2>
		<div class="input-wrapper">
			<NcTextField
				ref="giphy-search-input"
				:value.sync="searchQuery"
				:show-trailing-button="searchQuery !== ''"
				:label="inputPlaceholder"
				@trailing-button-click="onClear"
				@update:value="onInput"
				@keyup.native.esc="onCancel">
				<template #trailing-button-icon>
					<CloseIcon :size="16" />
				</template>
				<MagnifyIcon :size="16" />
			</NcTextField>
		</div>
		<div v-if="gifs.length === 0"
			class="empty-content-wrapper">
			<NcEmptyContent v-if="searching"
				:title="t('integration_giphy', 'Searching...')">
				<template #icon>
					<NcLoadingIcon />
				</template>
			</NcEmptyContent>
			<NcEmptyContent v-else
				:title="t('integration_giphy', 'No results')">
				<template #icon>
					<img class="empty-content-img"
						:src="sadGifUrl">
				</template>
			</NcEmptyContent>
		</div>
		<div v-else
			ref="results"
			class="results">
			<PickerResult v-for="gif in gifs"
				:key="gif.resourceUrl"
				:gif="gif"
				:tabindex="0"
				@click="onSubmit(gif)" />
			<InfiniteLoading v-if="gifs.length >= LIMIT"
				@infinite="infiniteHandler">
				<template #no-results>
					<div class="infinite-end">
						<img :src="sadGifUrl">
						{{ t('integration_giphy', 'No results') }}
					</div>
				</template>
				<template #no-more>
					<div class="infinite-end">
						<img :src="sadGifUrl">
						{{ t('integration_giphy', 'No more GIFs') }}
					</div>
				</template>
			</InfiniteLoading>
		</div>
		<a class="attribution"
			target="_blank"
			:title="poweredByTitle"
			href="https://giphy.com">
			<img :src="poweredByImgSrc"
				:alt="poweredByTitle">
		</a>
	</div>
</template>

<script>
import MagnifyIcon from 'vue-material-design-icons/Magnify.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'

import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

import PickerResult from '../components/PickerResult.vue'

import axios from '@nextcloud/axios'
import { generateOcsUrl, imagePath } from '@nextcloud/router'
import { delay } from '../utils.js'

import InfiniteLoading from 'vue-infinite-loading'
import Tooltip from '@nextcloud/vue/dist/Directives/Tooltip.js'
import Vue from 'vue'
Vue.directive('tooltip', Tooltip)

const searchProviderId = 'giphy-search-gifs'
const LIMIT = 20

export default {
	name: 'GifCustomPickerElement',

	components: {
		PickerResult,
		NcLoadingIcon,
		InfiniteLoading,
		NcEmptyContent,
		NcTextField,
		MagnifyIcon,
		CloseIcon,
	},

	props: {
		providerId: {
			type: String,
			required: true,
		},
		accessible: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			searchQuery: '',
			searching: false,
			gifs: [],
			inputPlaceholder: t('integration_giphy', 'Search GIFs'),
			cursor: 0,
			abortController: null,
			poweredByImgSrc: imagePath('integration_giphy', 'powered-by-giphy.gif'),
			poweredByTitle: t('integration_giphy', 'Powered by Giphy'),
			LIMIT,
			sadGifUrl: imagePath('integration_giphy', 'sad.gif'),
		}
	},

	computed: {
	},

	watch: {
	},

	mounted() {
		this.search()
		this.focusOnInput()
	},

	methods: {
		focusOnInput() {
			setTimeout(() => {
				// this.$refs['giphy-search-input']?.focus()
				this.$refs['giphy-search-input'].$el.getElementsByTagName('input')[0]?.focus()
			}, 300)
		},
		onCancel() {
			this.cancelSearchRequests()
			this.$emit('cancel')
		},
		onSubmit(gif) {
			this.cancelSearchRequests()
			this.$emit('submit', gif.resourceUrl)
		},
		onInput() {
			delay(() => {
				this.updateSearch()
			}, 500)()
		},
		onClear() {
			this.searchQuery = ''
			this.updateSearch()
		},
		updateSearch() {
			if (this.$refs.results?.scrollTop) {
				this.$refs.results.scrollTop = 0
			}
			this.cancelSearchRequests()
			this.gifs = []
			this.cursor = 0
			this.search()
		},
		cancelSearchRequests() {
			if (this.abortController) {
				this.abortController.abort()
			}
		},
		infiniteHandler($state) {
			this.search($state)
		},
		search(state = null, limit = LIMIT) {
			this.abortController = new AbortController()
			this.searching = true
			const url = this.searchQuery === ''
				? this.cursor === null
					? generateOcsUrl(
						'apps/integration_giphy/api/v1/gifs/trending?limit={limit}',
						{ limit }
					)
					: generateOcsUrl(
						'apps/integration_giphy/api/v1/gifs/trending?cursor={cursor}&limit={limit}',
						{ cursor: this.cursor, limit }
					)
				: this.cursor === null
					? generateOcsUrl(
						'search/providers/{searchProviderId}/search?term={term}&limit={limit}',
						{ searchProviderId, term: this.searchQuery, limit }
					)
					: generateOcsUrl(
						'search/providers/{searchProviderId}/search?term={term}&cursor={cursor}&limit={limit}',
						{ searchProviderId, term: this.searchQuery, cursor: this.cursor, limit }
					)
			return axios.get(url, {
				signal: this.abortController.signal,
			})
				.then((response) => {
					this.cursor = response.data.ocs.data.cursor
					this.gifs.push(...response.data.ocs.data.entries)
					if (state !== null) {
						if (response.data.ocs.data.entries.length > 0) {
							state.loaded()
						}
						if (response.data.ocs.data.entries.length < limit) {
							state.complete()
						}
					}
				})
				.catch((error) => {
					console.debug('giphy search request error', error)
					if (state !== null) {
						state.complete()
					}
				})
				.then(() => {
					this.searching = false
				})
		},
	},
}
</script>

<style scoped lang="scss">
.gif-picker-content {
	width: 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	//padding: 16px;
	overflow-y: auto;
	max-height: 800px;

	h2 {
		display: flex;
		align-items: center;
	}

	.attribution {
		height: 30px;
		align-self: start;
		margin-bottom: 8px;
		img {
			border-radius: var(--border-radius);
			height: 100%;
		}
	}

	.input-wrapper {
		display: flex;
		align-items: center;
		width: 100%;
		input {
			flex-grow: 1;
		}
		.input-loading {
			padding: 0 4px;
		}
	}

	.empty-content-wrapper {
		display: flex;
		align-items: center;
		height: 5000px;

		.empty-content-img {
			width: 100px;
		}
	}

	.results {
		width: 98%;
		// ugly but...makes it take all available space
		height: 5000px;
		//flex-grow: 1;
		display: grid;
		grid-auto-rows: 160px;
		grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
		grid-gap: 8px;
		overflow-y: scroll;
		scrollbar-width: auto;
		scrollbar-color: var(--color-primary);
		margin: 12px 0;
		padding-right: 16px;

		.result {
			&:hover {
				border: 4px solid var(--color-primary);
				border-radius: var(--border-radius);
			}
		}

		::v-deep .infinite-status-prompt {
			height: 100%;

			.infinite-end {
				height: 100%;
				display: flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;

				img {
					width: 50px;
				}
			}
		}
	}
}
</style>
