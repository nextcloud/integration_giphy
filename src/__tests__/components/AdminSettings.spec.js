/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'

const axiosMock = vi.hoisted(() => ({
	put: vi.fn(),
}))
vi.mock('@nextcloud/axios', () => ({ default: axiosMock }))
vi.mock('@nextcloud/router', () => ({
	generateUrl: (path) => path,
}))
vi.mock('@nextcloud/initial-state', () => ({
	loadState: () => ({
		api_key: 'test-key',
		rating: 'pg',
		search_gifs_enabled: true,
		link_preview_enabled: false,
	}),
}))
vi.mock('@nextcloud/dialogs', () => ({
	showSuccess: vi.fn(),
	showError: vi.fn(),
}))
vi.mock('@nextcloud/password-confirmation', () => ({
	confirmPassword: vi.fn().mockResolvedValue(undefined),
}))

import AdminSettings from '../../components/AdminSettings.vue'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { confirmPassword } from '@nextcloud/password-confirmation'

function mountAdmin() {
	return mount(AdminSettings, { shallow: true })
}

describe('AdminSettings', () => {
	beforeEach(() => {
		vi.resetAllMocks()
		axiosMock.put.mockResolvedValue({})
		confirmPassword.mockResolvedValue(undefined)
	})

	it('ratingOptions returns all 4 ratings with id/value/label', () => {
		const wrapper = mountAdmin()
		const opts = wrapper.vm.ratingOptions
		expect(opts).toHaveLength(4)
		expect(opts.map(o => o.value)).toEqual(['g', 'pg', 'pg-13', 'r'])
		opts.forEach(o => {
			expect(o).toHaveProperty('id')
			expect(o).toHaveProperty('label')
		})
	})

	it('selectedRating returns matching option for the current state', () => {
		const wrapper = mountAdmin()
		// state.rating = 'pg' from loadState mock
		expect(wrapper.vm.selectedRating).toEqual({
			id: 'pg',
			value: 'pg',
			label: 'PG - Level 2',
		})
	})

	it('selectedRating returns null for unknown rating', () => {
		const wrapper = mountAdmin()
		wrapper.vm.state.rating = 'xxx'
		expect(wrapper.vm.selectedRating).toBeNull()
	})

	it('saveOptions calls normal endpoint for non-sensitive values', async () => {
		const wrapper = mountAdmin()
		await wrapper.vm.saveOptions({ rating: 'g' })
		await flushPromises()

		expect(confirmPassword).not.toHaveBeenCalled()
		expect(axiosMock.put).toHaveBeenCalledWith(
			'/apps/integration_giphy/admin-config',
			{ values: { rating: 'g' } },
		)
		expect(showSuccess).toHaveBeenCalled()
	})

	it('saveOptions calls sensitive endpoint with password confirmation for API key', async () => {
		const wrapper = mountAdmin()
		await wrapper.vm.saveOptions({ api_key: 'new-key' }, true)
		await flushPromises()

		expect(confirmPassword).toHaveBeenCalled()
		expect(axiosMock.put).toHaveBeenCalledWith(
			'/apps/integration_giphy/admin-config/sensitive',
			{ values: { api_key: 'new-key' } },
		)
	})

	it('shows error toast when save fails', async () => {
		axiosMock.put.mockRejectedValue(new Error('network'))

		const wrapper = mountAdmin()
		await wrapper.vm.saveOptions({ rating: 'r' })
		await flushPromises()

		expect(showError).toHaveBeenCalled()
	})

	it('onCheckboxChanged converts boolean to "1"/"0" string', async () => {
		const wrapper = mountAdmin()
		wrapper.vm.onCheckboxChanged(false, 'search_gifs_enabled')
		await flushPromises()

		expect(axiosMock.put).toHaveBeenCalledWith(
			'/apps/integration_giphy/admin-config',
			{ values: { search_gifs_enabled: '0' } },
		)
	})
})
