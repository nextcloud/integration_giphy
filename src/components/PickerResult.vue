<template>
	<div v-tooltip.top="{ content: gif.title }"
		class="result"
		@click="$emit('click')">
		<div v-if="!isLoaded" class="loading-icon">
			<NcLoadingIcon
				:size="44"
				:title="t('integration_giphy', 'Loading gif')" />
		</div>
		<img v-show="isLoaded"
			class="gif-image"
			:src="gif.thumbnailUrl"
			@load="isLoaded = true">
	</div>
</template>

<script>
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

export default {
	name: 'PickerResult',

	components: {
		NcLoadingIcon,
	},

	props: {
		gif: {
			type: Object,
			required: true,
		},
	},

	data() {
		return {
			isLoaded: false,
		}
	},

	watch: {
	},

	mounted() {
	},

	methods: {
	},
}
</script>

<style scoped lang="scss">
.result {
	display: flex;
	flex-direction: column;
	align-items: center;

	> * {
		cursor: pointer;
	}

	.loading-icon {
		display: flex;
		align-items: center;
		width: 100%;
		height: 100%;
	}

	.gif-image {
		height: 100%;
		width: 100%;
		object-fit: cover;
		border-radius: var(--border-radius);
	}
}
</style>
