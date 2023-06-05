/**
 * @copyright Copyright (c) 2022 Julien Veyssier <julien-nc@posteo.net>
 *
 * @author Julien Veyssier <julien-nc@posteo.net>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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
