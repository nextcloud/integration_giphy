/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Page } from '@playwright/test'

import { login } from '@nextcloud/e2e-test-server/playwright'
import { test as base, expect } from '@playwright/test'

// the test container always has this admin user
const admin = { userId: 'admin', password: 'admin' }

// Every test also fails on an uncaught exception, or on a failing request to one of the app's own routes.
// Errors of other apps on the instance are ignored on purpose.
const test = base.extend<{ appErrors: void }>({
	appErrors: [async ({ page }, use) => {
		const errors: string[] = []
		page.on('pageerror', (error) => errors.push(`uncaught: ${error.message}`))
		page.on('response', (response) => {
			if (response.status() >= 400 && response.url().includes('/integration_giphy/')) {
				errors.push(`${response.status()} ${response.request().method()} ${response.url()}`)
			}
		})
		await use()
		expect(errors).toEqual([])
	}, { auto: true }],
})

/**
 * Flip a switch of the Giphy section, check that the new value survives a reload, then flip it back.
 *
 * @param page the page showing the section
 * @param label the text of the switch
 * @param route the app route that stores the value
 */
async function expectSwitchToBeSaved(page: Page, label: string, route: string) {
	const section = page.locator('#giphy_prefs')
	const toggle = async () => {
		const saved = page.waitForResponse((response) => response.url().includes(route))
		// the switch hides its input, so click the label
		await section.getByText(label).click()
		expect((await saved).ok()).toBe(true)
	}

	const before = await section.getByLabel(label).isChecked()
	await toggle()
	try {
		await page.reload()
		await expect(section.getByLabel(label)).toBeChecked({ checked: !before })
	} finally {
		await toggle()
	}
}

test.beforeEach(async ({ page }) => {
	await login(page.request, admin)
})

test.describe('Admin settings', () => {
	test.beforeEach(async ({ page }) => {
		await page.goto('settings/admin/connected-accounts')
	})

	test('show the Giphy section', async ({ page }) => {
		const section = page.locator('#giphy_prefs')
		await expect(section.getByRole('heading', { name: /Giphy integration/ })).toBeVisible()
		await expect(section.getByLabel('Giphy API key')).toBeVisible()
		await expect(section.getByText('Rating filter')).toBeVisible()
	})

	test('save the link preview setting', async ({ page }) => {
		await expectSwitchToBeSaved(page, 'Enable Giphy link previews', '/apps/integration_giphy/admin-config')
	})
})

test.describe('Personal settings', () => {
	test.beforeEach(async ({ page }) => {
		await page.goto('settings/user/connected-accounts')
	})

	test('show the Giphy section', async ({ page }) => {
		const section = page.locator('#giphy_prefs')
		await expect(section.getByRole('heading', { name: /Giphy integration/ })).toBeVisible()
		await expect(section.getByLabel('Enable search provider for GIFs')).toBeVisible()
	})

	test('save the link preview setting', async ({ page }) => {
		await expectSwitchToBeSaved(page, 'Enable Giphy link previews', '/apps/integration_giphy/config')
	})
})
