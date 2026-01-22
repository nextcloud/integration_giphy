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
			<div>
				<NcNoteCard v-if="!state.admin_search_gifs_enabled" type="info">
					{{ t('integration_giphy', 'Searching gifs has been disabled by your administrator') }}
				</NcNoteCard>
				<NcNoteCard v-if="!state.admin_link_preview_enabled" type="info">
					{{ t('integration_giphy', 'Gif link previews have been disabled by your administrator') }}
				</NcNoteCard>
			</div>
			<NcFormBox>
				<NcFormBoxSwitch
					:model-value="state.admin_search_gifs_enabled && state.search_gifs_enabled"
					:disabled="!state.admin_search_gifs_enabled"
					@update:model-value="onCheckboxChanged($event, 'search_gifs_enabled')">
					{{ t('integration_giphy', 'Enable search provider for GIFs') }}
				</NcFormBoxSwitch>
				<NcFormBoxSwitch
					:model-value="state.admin_link_preview_enabled && state.link_preview_enabled"
					:disabled="!state.admin_link_preview_enabled"
					@update:model-value="onCheckboxChanged($event, 'link_preview_enabled')">
					{{ t('integration_giphy', 'Enable Giphy link previews') }}
				</NcFormBoxSwitch>
			</NcFormBox>
		</div>
	</div>
</template>

<script>
import GiphyIcon from './icons/GiphyIcon.vue'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { showSuccess, showError } from '@nextcloud/dialogs'

import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxSwitch from '@nextcloud/vue/components/NcFormBoxSwitch'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'

export default {
	name: 'PersonalSettings',

	components: {
		GiphyIcon,
		NcFormBox,
		NcFormBoxSwitch,
		NcNoteCard,
	},

	props: [],

	data() {
		return {
			state: loadState('integration_giphy', 'user-config'),
		}
	},

	computed: {
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
		async saveOptions(values) {
			const req = {
				values,
			}
			const url = generateUrl('/apps/integration_giphy/config')
			try {
				await axios.put(url, req)
				showSuccess(t('integration_giphy', 'Giphy options saved'))
			} catch (e) {
				showError(
					t('integration_giphy', 'Failed to save Giphy options')
					+ ': ' + e.response?.data?.error,
				)
			}
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
		gap: 8px;
		max-width: 800px;
	}

	h2 {
		display: flex;
		gap: 8px;
		align-items: center;
		justify-content: start;
	}
}
</style>
