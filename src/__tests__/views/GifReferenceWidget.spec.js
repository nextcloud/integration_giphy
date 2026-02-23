/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'

vi.mock('@nextcloud/router', () => ({
	imagePath: (app, img) => `/apps/${app}/img/${img}`,
}))
vi.mock('@nextcloud/auth', () => ({
	getRequestToken: () => 'test-req-token',
}))

const emitMock = vi.hoisted(() => vi.fn())
const subscribeMock = vi.hoisted(() => vi.fn())
vi.mock('@nextcloud/event-bus', () => ({
	emit: emitMock,
	subscribe: subscribeMock,
}))

import GifReferenceWidget from '../../views/GifReferenceWidget.vue'

function mountWidget(richObject = { proxied_url: 'https://nc.local/proxy/gif123' }) {
	return mount(GifReferenceWidget, {
		props: {
			richObjectType: 'integration_giphy_gif',
			richObject,
			accessible: true,
		},
		shallow: true,
	})
}

describe('GifReferenceWidget', () => {
	beforeEach(() => {
		vi.resetAllMocks()
	})

	it('constructs proxiedUrl with request token appended', () => {
		const wrapper = mountWidget()
		expect(wrapper.vm.proxiedUrl).toBe(
			'https://nc.local/proxy/gif123?requesttoken=test-req-token',
		)
	})

	it('returns empty string when proxied_url is missing', () => {
		const wrapper = mountWidget({ })
		expect(wrapper.vm.proxiedUrl).toBe('')
	})

	it('toggles visibility and emits on event bus', async () => {
		const wrapper = mountWidget()
		expect(wrapper.vm.gifsEnabled).toBe(true)

		wrapper.vm.handleGifsBtn()
		expect(wrapper.vm.gifsEnabled).toBe(false)
		expect(emitMock).toHaveBeenCalledWith('integration_giphy:gifs:enabled', false)

		wrapper.vm.handleGifsBtn()
		expect(wrapper.vm.gifsEnabled).toBe(true)
		expect(emitMock).toHaveBeenCalledWith('integration_giphy:gifs:enabled', true)
	})

	it('subscribes to event bus and syncs visibility from other widgets', () => {
		const wrapper = mountWidget()

		expect(subscribeMock).toHaveBeenCalledWith(
			'integration_giphy:gifs:enabled',
			expect.any(Function),
		)

		// Grab the callback that was registered and simulate an external toggle
		const callback = subscribeMock.mock.calls[0][1]
		callback(false)
		expect(wrapper.vm.gifsEnabled).toBe(false)

		callback(true)
		expect(wrapper.vm.gifsEnabled).toBe(true)
	})

	it('updates hideButtonTitle based on enabled state', () => {
		const wrapper = mountWidget()
		expect(wrapper.vm.hideButtonTitle).toBe('Fold all Giphy GIFs')

		wrapper.vm.gifsEnabled = false
		expect(wrapper.vm.hideButtonTitle).toBe('Unfold all Giphy GIFs')
	})
})
