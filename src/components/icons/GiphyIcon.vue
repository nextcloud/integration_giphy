<template>
	<span :aria-hidden="!title"
		:aria-label="title"
		class="material-design-icon giphy-icon"
		role="img"
		v-bind="$attrs"
		@click="$emit('click', $event)">
		<svg
			:fill="fillColor"
			:width="size"
			:height="size"
			enable-background="new 0 0 40 40"
			version="1.1"
			viewBox="0 0 40 40"
			xml:space="preserve"
			xmlns="http://www.w3.org/2000/svg">
			<g transform="translate(6 2.5)">
				<path d="m0 3h4v29h-4z" />
				<path d="m24 11h4v21h-4z" />
				<path d="m0 31h28v4h-28z" />
				<path d="m0 0h16v4h-16z" />
				<path d="m24 8v-4h-4v-4h-4v12h12v-4" />
				<path d="m24 16v-4h4m-12-12v4h-4" />
			</g>
		</svg>
	</span>
</template>

<script>
export default {
	name: 'GiphyIcon',
	props: {
		title: {
			type: String,
			default: '',
		},
		fillColor: {
			type: String,
			default: 'currentColor',
		},
		size: {
			type: Number,
			default: 24,
		},
	},
}
</script>
