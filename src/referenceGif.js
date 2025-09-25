/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import {
	registerWidget,
	registerCustomPickerElement,
	NcCustomPickerRenderResult,
} from '@nextcloud/vue/components/NcRichText'

registerWidget('integration_giphy_gif', async (el, { richObjectType, richObject, accessible }) => {
	const { createApp } = await import('vue')
	const { default: GifReferenceWidget } = await import('./views/GifReferenceWidget.vue')

	const app = createApp(
		GifReferenceWidget,
		{
			richObjectType,
			richObject,
			accessible,
		},
	)
	app.mixin({ methods: { t, n } })
	app.mount(el)
}, () => {}, { hasInteractiveView: false })

registerCustomPickerElement('giphy-gif', async (el, { providerId, accessible }) => {
	const { createApp } = await import('vue')
	const { default: GifCustomPickerElement } = await import('./views/GifCustomPickerElement.vue')

	const app = createApp(
		GifCustomPickerElement,
		{
			providerId,
			accessible,
		},
	)
	app.mixin({ methods: { t, n } })
	app.mount(el)

	return new NcCustomPickerRenderResult(el, app)
}, (el, renderResult) => {
	renderResult.object.unmount()
})
