/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import {
	registerWidget,
	registerCustomPickerElement,
	NcCustomPickerRenderResult,
} from '@nextcloud/vue/dist/Components/NcRichText.js'
import { linkTo } from '@nextcloud/router'
import { getRequestToken } from '@nextcloud/auth'

__webpack_nonce__ = btoa(getRequestToken()) // eslint-disable-line
__webpack_public_path__ = linkTo('integration_giphy', 'js/') // eslint-disable-line

registerWidget('integration_giphy_gif', async (el, { richObjectType, richObject, accessible }) => {
	const { default: Vue } = await import(/* webpackChunkName: "reference-lazy" */'vue')
	Vue.mixin({ methods: { t, n } })
	const { default: GifReferenceWidget } = await import(/* webpackChunkName: "reference-lazy" */'./views/GifReferenceWidget.vue')
	const Widget = Vue.extend(GifReferenceWidget)
	new Widget({
		propsData: {
			richObjectType,
			richObject,
			accessible,
		},
	}).$mount(el)
})

registerCustomPickerElement('giphy-gif', async (el, { providerId, accessible }) => {
	const { default: Vue } = await import(/* webpackChunkName: "reference-picker-lazy" */'vue')
	Vue.mixin({ methods: { t, n } })
	const { default: GifCustomPickerElement } = await import(/* webpackChunkName: "reference-picker-lazy" */'./views/GifCustomPickerElement.vue')
	const Element = Vue.extend(GifCustomPickerElement)
	const vueElement = new Element({
		propsData: {
			providerId,
			accessible,
		},
	}).$mount(el)
	return new NcCustomPickerRenderResult(vueElement.$el, vueElement)
}, (el, renderResult) => {
	renderResult.object.$destroy()
})
