/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'

const axiosMock = vi.hoisted(() => ({
	get: vi.fn(),
}))
vi.mock('@nextcloud/axios', () => ({ default: axiosMock }))
vi.mock('@nextcloud/router', () => ({
	generateOcsUrl: (tpl, params) => {
		let url = tpl
		for (const [key, value] of Object.entries(params || {})) {
			url = url.replace(`{${key}}`, encodeURIComponent(value))
		}
		return '/ocs/v2.php/' + url
	},
	imagePath: (app, img) => `/apps/${app}/img/${img}`,
}))
vi.mock('@nextcloud/auth', () => ({
	getRequestToken: () => 'fake-token',
}))
vi.mock('@codog/vue3-infinite-loading', () => ({
	default: { name: 'InfiniteLoading', template: '<slot />' },
}))

import GifCustomPickerElement from '../../views/GifCustomPickerElement.vue'

function makeGif(id) {
	return {
		title: `gif-${id}`,
		thumbnailUrl: `https://giphy.com/thumb/${id}`,
		resourceUrl: `https://giphy.com/gif/${id}`,
	}
}

function makeApiResponse(entries, cursor = null) {
	return {
		data: {
			ocs: {
				data: { entries, cursor },
			},
		},
	}
}

function mountPicker() {
	return mount(GifCustomPickerElement, {
		props: { providerId: 'giphy-gif' },
		shallow: true,
		global: {
			stubs: {
				InfiniteLoading: true,
			},
		},
	})
}

describe('GifCustomPickerElement', () => {
	beforeEach(() => {
		vi.resetAllMocks()
		// Default: trending returns some gifs
		axiosMock.get.mockResolvedValue(
			makeApiResponse(Array.from({ length: 5 }, (_, i) => makeGif(i)), 5),
		)
	})

	it('fetches trending GIFs on mount', async () => {
		mountPicker()
		await flushPromises()

		expect(axiosMock.get).toHaveBeenCalledOnce()
		const url = axiosMock.get.mock.calls[0][0]
		expect(url).toContain('gifs/trending')
		expect(url).not.toContain('term=')
	})

	it('renders PickerResult for each loaded GIF', async () => {
		const wrapper = mountPicker()
		await flushPromises()

		const results = wrapper.findAllComponents({ name: 'PickerResult' })
		expect(results).toHaveLength(5)
	})

	it('shows "No results" empty state when search returns nothing', async () => {
		axiosMock.get.mockResolvedValue(makeApiResponse([], null))

		const wrapper = mountPicker()
		await flushPromises()

		expect(wrapper.vm.gifs).toHaveLength(0)
		expect(wrapper.vm.searching).toBe(false)
		const emptyContent = wrapper.findComponent({ name: 'NcEmptyContent' })
		expect(emptyContent.exists()).toBe(true)
		expect(emptyContent.attributes('title')).toBe('No results')
	})

	it('searches by term when searchQuery is set', async () => {
		const trendingGifs = makeApiResponse([makeGif(0)], 1)
		const searchGifs = makeApiResponse([makeGif(10), makeGif(11)], 2)
		axiosMock.get
			.mockResolvedValueOnce(trendingGifs) // mount
			.mockResolvedValueOnce(searchGifs) // search

		const wrapper = mountPicker()
		await flushPromises()

		// Simulate search
		wrapper.vm.searchQuery = 'cats'
		wrapper.vm.updateSearch()
		await flushPromises()

		const searchUrl = axiosMock.get.mock.calls[1][0]
		expect(searchUrl).toContain('gifs/search')
		expect(searchUrl).toContain('term=cats')
	})

	it('clears results and fetches trending on clear', async () => {
		const trendingGifs = makeApiResponse([makeGif(0)], 1)
		const searchGifs = makeApiResponse([makeGif(10)], 2)
		axiosMock.get
			.mockResolvedValueOnce(trendingGifs)
			.mockResolvedValueOnce(searchGifs)
			.mockResolvedValueOnce(trendingGifs) // after clear

		const wrapper = mountPicker()
		await flushPromises()

		wrapper.vm.searchQuery = 'cats'
		wrapper.vm.updateSearch()
		await flushPromises()

		wrapper.vm.onClear()
		await flushPromises()

		expect(wrapper.vm.searchQuery).toBe('')
		const lastUrl = axiosMock.get.mock.lastCall[0]
		expect(lastUrl).toContain('gifs/trending')
	})

	it('advances cursor for pagination and appends results', async () => {
		const page1 = makeApiResponse(Array.from({ length: 20 }, (_, i) => makeGif(i)), 20)
		const page2 = makeApiResponse([makeGif(20), makeGif(21)], null)
		axiosMock.get
			.mockResolvedValueOnce(page1)
			.mockResolvedValueOnce(page2)

		const wrapper = mountPicker()
		await flushPromises()
		expect(wrapper.vm.gifs).toHaveLength(20)
		expect(wrapper.vm.cursor).toBe(20)

		// Simulate infinite scroll
		const state = { loaded: vi.fn(), complete: vi.fn() }
		wrapper.vm.infiniteHandler(state)
		await flushPromises()

		expect(wrapper.vm.gifs).toHaveLength(22)
		const url = axiosMock.get.mock.calls[1][0]
		expect(url).toContain('cursor=20')
		expect(state.loaded).toHaveBeenCalled()
		expect(state.complete).toHaveBeenCalled()
	})

	it('dispatches CustomEvent with resourceUrl on submit', async () => {
		const wrapper = mountPicker()
		await flushPromises()

		const events = []
		wrapper.element.addEventListener('submit', (e) => events.push(e))

		const gif = makeGif(42)
		wrapper.vm.onSubmit(gif)

		expect(events).toHaveLength(1)
		expect(events[0].detail).toBe('https://giphy.com/gif/42')
	})

	it('aborts in-flight request when a new search starts', async () => {
		const neverResolve = new Promise(() => {})
		axiosMock.get
			.mockReturnValueOnce(neverResolve) // mount — hangs
			.mockResolvedValueOnce(makeApiResponse([makeGif(1)], null))

		const wrapper = mountPicker()
		const firstController = wrapper.vm.abortController
		const abortSpy = vi.spyOn(firstController, 'abort')

		wrapper.vm.searchQuery = 'dogs'
		wrapper.vm.updateSearch()

		expect(abortSpy).toHaveBeenCalled()
	})
})
