<template>
	<div v-if="show" id="gif-picker-modal-wrapper">
		<NcModal
			size="large"
			:container="'#gif-picker-modal-wrapper'"
			@close="onCancel">
			<div class="gif-picker-modal-content">
				<h2>
					{{ t('integration_giphy', 'Gif picker') }}
					<a class="attribution"
						target="_blank"
						:title="poweredByTitle"
						href="https://giphy.com">
						<img :src="poweredByImgSrc"
							:alt="poweredByTitle">
					</a>
				</h2>
				<div class="input-wrapper">
					<input ref="search-input"
						v-model="searchQuery"
						type="text"
						:placeholder="inputPlaceholder"
						@input="onInput"
						@keyup.esc="onCancel">
					<NcLoadingIcon v-if="searching"
						:size="20"
						:title="t('integration_giphy', 'Loading gifs')" />
				</div>
				<div class="results">
					<PickerResult v-for="gif in gifs"
						:key="gif.resourceUrl"
						:gif="gif"
						@click="onSubmit(gif)" />
					<div v-if="searching || gifs.length > 0"
						class="last-element-wrapper"
						:title="t('integration_giphy', 'Load more results')"
						@click="search()">
						<NcLoadingIcon v-if="searching"
							:size="44"
							:title="t('integration_giphy', 'Loading gifs')" />
						<PlusIcon v-else-if="gifs.length > 0"
							:size="20" />
					</div>
				</div>
				<div class="footer">
					<NcButton @click="onCancel">
						{{ t('integration_giphy', 'Cancel') }}
					</NcButton>
				</div>
			</div>
		</NcModal>
	</div>
</template>

<script>
import PlusIcon from 'vue-material-design-icons/Plus.vue'

import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

import PickerResult from '../components/PickerResult.vue'

import axios from '@nextcloud/axios'
import { generateOcsUrl, imagePath } from '@nextcloud/router'
import { delay } from '../utils.js'

import Tooltip from '@nextcloud/vue/dist/Directives/Tooltip.js'
import Vue from 'vue'
Vue.directive('tooltip', Tooltip)

const searchProviderId = 'giphy-search-gifs'
const LIMIT = 10

export default {
	name: 'GifCustomPickerElement',

	components: {
		PickerResult,
		NcModal,
		NcButton,
		NcLoadingIcon,
		PlusIcon,
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
			show: true,
			searchQuery: '',
			searching: false,
			gifs: [],
			inputPlaceholder: t('integration_giphy', 'Search gifs'),
			cursor: 0,
			abortController: null,
			poweredByImgSrc: imagePath('integration_giphy', 'powered-by-giphy.gif'),
			poweredByTitle: t('integration_giphy', 'Powered by Giphy'),
		}
	},

	computed: {
	},

	watch: {
	},

	mounted() {
		this.focusOnInput()
	},

	methods: {
		focusOnInput() {
			this.$nextTick(() => {
				this.$refs['search-input']?.focus()
			})
		},
		onCancel() {
			this.cancelSearchRequests()
			this.show = false
			this.$emit('cancel')
		},
		onSubmit(gif) {
			this.cancelSearchRequests()
			this.show = false
			this.$emit('submit', gif.resourceUrl)
		},
		onInput() {
			delay(() => {
				this.updateSearch()
			}, 500)()
		},
		updateSearch() {
			this.cancelSearchRequests()
			this.gifs = []
			this.cursor = 0
			if (this.searchQuery === '') {
				this.searching = false
				return
			}
			this.search()
		},
		cancelSearchRequests() {
			if (this.abortController) {
				this.abortController.abort()
			}
		},
		search() {
			this.abortController = new AbortController()
			this.searching = true
			const url = this.cursor === null
				? generateOcsUrl(
					'search/providers/{searchProviderId}/search?term={term}&limit={limit}',
					{ searchProviderId, term: this.searchQuery, limit: LIMIT }
				)
				: generateOcsUrl(
					'search/providers/{searchProviderId}/search?term={term}&cursor={cursor}&limit={limit}',
					{ searchProviderId, term: this.searchQuery, cursor: this.cursor, limit: LIMIT }
				)
			return axios.get(url, {
				signal: this.abortController.signal,
			})
				.then((response) => {
					this.cursor = response.data.ocs.data.cursor
					this.gifs.push(...response.data.ocs.data.entries)
				})
				.catch((error) => {
					console.debug('giphy search request error', error)
				})
				.then(() => {
					this.searching = false
				})
		},
	},
}
</script>

<style scoped lang="scss">
// this is to avoid scroll on the container and leave it to the result block
#gif-picker-modal-wrapper {
	::v-deep .modal-container {
		display: flex !important;
		height: 100%;
	}
}

.gif-picker-modal-content {
	width: 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 16px;

	h2 {
		display: flex;
		align-items: center;
		.attribution {
			height: 30px;
			margin-left: 16px;
		}
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
	}

	.results {
		width: 100%;
		flex-grow: 1;
		display: grid;
		grid-auto-rows: 160px;
		grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
		grid-gap: 8px;
		overflow-y: scroll;
		scrollbar-width: auto;
		scrollbar-color: var(--color-primary);
		padding-right: 12px;
		margin: 12px 0;

		.last-element-wrapper {
			display: flex;
			align-items: center;
			justify-content: center;
			cursor: pointer;
			background-color: var(--color-background-dark);
			> * {
				cursor: pointer;
			}
		}
	}

	.footer {
		width: 100%;
		margin-top: 8px;
		display: flex;
		justify-content: end;
	}
}
</style>
