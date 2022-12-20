<template>
	<div v-if="show" id="gif-picker-modal-wrapper">
		<NcModal
			size="large"
			:container="'#gif-picker-modal-wrapper'"
			@close="onCancel">
			<div class="gif-picker-modal-content">
				<h2>
					{{ t('integration_giphy', 'Giphy gif picker') }}
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
					<div class="last-element-wrapper">
						<NcLoadingIcon v-if="searching"
							:size="44"
							:title="t('integration_giphy', 'Loading gifs')" />
						<NcButton v-else-if="gifs.length > 0"
							class="more-button"
							@click="search()">
							{{ t('integration_giphy', 'More') }}
						</NcButton>
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
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

import PickerResult from '../components/PickerResult.vue'

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import { delay } from '../utils.js'

import Tooltip from '@nextcloud/vue/dist/Directives/Tooltip.js'
import Vue from 'vue'
Vue.directive('tooltip', Tooltip)

const searchProviderId = 'giphy-search-gifs'

export default {
	name: 'GifCustomPickerElement',

	components: {
		PickerResult,
		NcModal,
		NcButton,
		NcLoadingIcon,
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
			this.$refs['search-input']?.focus()
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
		search(limit = 5) {
			this.abortController = new AbortController()
			this.searching = true
			const url = this.cursor === null
				? generateOcsUrl('search/providers/{searchProviderId}/search?term={term}&limit={limit}', { searchProviderId, term: this.searchQuery, limit })
				: generateOcsUrl('search/providers/{searchProviderId}/search?term={term}&cursor={cursor}&limit={limit}', { searchProviderId, term: this.searchQuery, cursor: this.cursor, limit })
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
		display: flex;
		align-items: start;
		flex-wrap: wrap;
		overflow-y: scroll;

		.last-element-wrapper {
			height: 200px;
			display: flex;
			align-items: center;
		}
	}

	.footer {
		width: 100%;
		display: flex;
		justify-content: end;
	}
}
</style>
