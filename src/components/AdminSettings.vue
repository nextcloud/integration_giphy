<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div id="giphy_prefs" class="section">
		<h2>
			<GiphyIcon />
			{{ t('integration_giphy', 'Giphy integration') }}
		</h2>
		<div id="giphy-content">
			<NcTextField
				v-model="state.api_key"
				class="input"
				type="password"
				:label="t('integration_giphy', 'Giphy API key')"
				:showTrailingButton="!!state.api_key"
				@update:modelValue="onInput"
				@trailingButtonClick="state.api_key = '' ; onInput()">
				<template #icon>
					<KeyOutlineIcon :size="20" />
				</template>
			</NcTextField>
			<NcNoteCard v-if="state.api_key === ''"
				type="info">
				<p>
					{{ t('integration_giphy', 'You can create a Giphy API key in your Giphy developer settings.') }}
				</p>
				<a href="https://developers.giphy.com/dashboard/" target="_blank" class="external">
					{{ t('integration_giphy', 'Giphy developer settings') }}
				</a>
			</NcNoteCard>
			<NcSelect
				:modelValue="selectedRating"
				class="rating-select"
				:inputLabel="t('integration_giphy', 'Rating filter')"
				label="label"
				:options="ratingOptions"
				inputId="giphy-rating-select"
				@update:modelValue="onRatingChange" />
			<NcFormBox>
				<NcFormBoxSwitch
					:modelValue="state.search_gifs_enabled"
					@update:modelValue="onCheckboxChanged($event, 'search_gifs_enabled')">
					{{ t('integration_giphy', 'Enable search provider for GIFs') }}
				</NcFormBoxSwitch>
				<NcFormBoxSwitch
					:modelValue="state.link_preview_enabled"
					@update:modelValue="onCheckboxChanged($event, 'link_preview_enabled')">
					{{ t('integration_giphy', 'Enable Giphy link previews') }}
				</NcFormBoxSwitch>
			</NcFormBox>
		</div>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { generateUrl } from '@nextcloud/router'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxSwitch from '@nextcloud/vue/components/NcFormBoxSwitch'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import KeyOutlineIcon from 'vue-material-design-icons/KeyOutline.vue'
import GiphyIcon from './icons/GiphyIcon.vue'
import { delay } from '../utils.js'

const ratings = {
	g: {
		value: 'g',
		label: t('integration_giphy', 'G - Level 1'),
	},
	pg: {
		value: 'pg',
		label: t('integration_giphy', 'PG - Level 2'),
	},
	'pg-13': {
		value: 'pg-13',
		label: t('integration_giphy', 'PG 13 - Level 3'),
	},
	r: {
		value: 'r',
		label: t('integration_giphy', 'R - Level 4'),
	},
}

export default {
	name: 'AdminSettings',

	components: {
		GiphyIcon,
		NcFormBox,
		NcFormBoxSwitch,
		NcSelect,
		NcNoteCard,
		NcTextField,
		KeyOutlineIcon,
	},

	props: [],

	data() {
		return {
			state: loadState('integration_giphy', 'admin-config'),
			// to prevent some browsers to fill fields with remembered passwords
			readonly: true,
		}
	},

	computed: {
		ratingOptions() {
			return Object.values(ratings).map((ra) => {
				return {
					id: ra.value,
					value: ra.value,
					label: ra.label,
				}
			})
		},

		selectedRating() {
			if (ratings[this.state.rating]) {
				const ra = ratings[this.state.rating]
				return {
					id: ra.value,
					value: ra.value,
					label: ra.label,
				}
			}
			return null
		},
	},

	watch: {
	},

	mounted() {
	},

	methods: {
		onCheckboxChanged(newValue, key) {
			this.state[key] = newValue
			this.saveOptions({ [key]: this.state[key] ? '1' : '0' })
		},

		onRatingChange(newRating) {
			console.debug('rating change', newRating)
			this.state.rating = newRating.value
			this.saveOptions({ rating: this.state.rating })
		},

		onInput() {
			delay(() => {
				this.saveOptions({
					api_key: this.state.api_key,
				}, true)
			}, 2000)()
		},

		async saveOptions(values, sensitive = false) {
			if (sensitive) {
				await confirmPassword()
			}

			const req = {
				values,
			}
			const url = sensitive
				? generateUrl('/apps/integration_giphy/admin-config/sensitive')
				: generateUrl('/apps/integration_giphy/admin-config')
			axios.put(url, req)
				.then(() => {
					showSuccess(t('integration_giphy', 'Giphy admin options saved'))
				})
				.catch((error) => {
					showError(t('integration_giphy', 'Failed to save Giphy admin options'))
					console.error(error)
				})
		},
	},
}
</script>

<style scoped lang="scss">
#giphy_prefs {
	#giphy-content {
		margin-left: 40px;
		display: flex;
		flex-direction: column;
		max-width: 800px;
		gap: 8px;
	}

	h2 {
		display: flex;
		gap: 8px;
		align-items: center;
		justify-content: start;
	}

	.rating-select {
		min-width: 350px;
		margin: 0 !important;
	}
}
</style>
