<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div id="giphy_prefs" class="section">
		<h2>
			<GiphyIcon class="icon" />
			{{ t('integration_giphy', 'Giphy integration') }}
		</h2>
		<div id="giphy-content">
			<NcCheckboxRadioSwitch
				:model-value="state.admin_search_gifs_enabled && state.search_gifs_enabled"
				:disabled="!state.admin_search_gifs_enabled"
				@update:model-value="onCheckboxChanged($event, 'search_gifs_enabled')">
				{{ t('integration_giphy', 'Enable search provider for GIFs') }}
			</NcCheckboxRadioSwitch>
			<p v-if="!state.admin_search_gifs_enabled" class="settings-hint">
				<InformationOutlineIcon :size="20" class="icon" />
				{{ t('integration_giphy', 'Disabled by your administrator') }}
			</p>
			<NcCheckboxRadioSwitch
				:model-value="state.admin_link_preview_enabled && state.link_preview_enabled"
				:disabled="!state.admin_link_preview_enabled"
				@update:model-value="onCheckboxChanged($event, 'link_preview_enabled')">
				{{ t('integration_giphy', 'Enable Giphy link previews') }}
			</NcCheckboxRadioSwitch>
			<p v-if="!state.admin_link_preview_enabled" class="settings-hint">
				<InformationOutlineIcon :size="20" class="icon" />
				{{ t('integration_giphy', 'Disabled by your administrator') }}
			</p>
		</div>
	</div>
</template>

<script>
import InformationOutlineIcon from 'vue-material-design-icons/InformationOutline.vue'

import GiphyIcon from './icons/GiphyIcon.vue'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { showSuccess, showError } from '@nextcloud/dialogs'

import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

export default {
	name: 'PersonalSettings',

	components: {
		GiphyIcon,
		NcCheckboxRadioSwitch,
		InformationOutlineIcon,
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
	}

	.settings-hint,
	h2 {
		display: flex;
		align-items: center;
		.icon {
			margin-right: 8px;
		}
	}
	.settings-hint .icon {
		margin-right: 4px;
	}
}
</style>
