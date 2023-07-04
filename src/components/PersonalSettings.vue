<template>
	<div id="giphy_prefs" class="section">
		<h2>
			<GiphyIcon class="icon" />
			{{ t('integration_giphy', 'Giphy integration') }}
		</h2>
		<div id="giphy-content">
			<NcCheckboxRadioSwitch
				:checked="state.link_preview_enabled"
				@update:checked="onCheckboxChanged($event, 'link_preview_enabled')">
				{{ t('integration_giphy', 'Enable Giphy link previews') }}
			</NcCheckboxRadioSwitch>
		</div>
	</div>
</template>

<script>
import GiphyIcon from './icons/GiphyIcon.vue'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { showSuccess, showError } from '@nextcloud/dialogs'

import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'

export default {
	name: 'PersonalSettings',

	components: {
		GiphyIcon,
		NcCheckboxRadioSwitch,
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
					+ ': ' + e.response?.data?.error
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
	h2 {
		display: flex;
		align-items: center;
		.icon {
			margin-right: 8px;
		}
	}
}
</style>
