/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Page } from '@playwright/test'

import { login } from '@nextcloud/e2e-test-server/playwright'
import { test as base, expect } from '@playwright/test'

// the test container always has this admin user
const admin = { userId: 'admin', password: 'admin' }
const ocs = { 'OCS-APIRequest': 'true', Accept: 'application/json' }

// served for every GIF the app proxies
const gif = Buffer.from('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', 'base64')

// a GIF as the app puts it into the rich object of a link preview
const gifObject = {
	id: 'nc42',
	title: 'a dancing cloud',
	proxied_url: '/apps/integration_giphy/gif/direct/nc42/media0/giphy.gif/cid/rid/ct',
	url: 'https://giphy.com/gifs/nc42',
}

// one result in the shape the GIF picker expects from the app
const searchEntry = {
	thumbnailUrl: '/apps/integration_giphy/gif/direct/nc42/media0/giphy.gif/cid/rid/ct',
	title: 'a dancing cloud',
	subline: 'Giphy',
	resourceUrl: 'https://giphy.com/gifs/nc42',
	icon: '',
	rounded: false,
}

// Every test also fails on an uncaught exception, or on an unexpected failing request to one of the app's own routes.
// Errors of other apps on the instance are ignored on purpose.
const test = base.extend<{ appErrors: void }>({
	appErrors: [async ({ page }, use) => {
		const errors: string[] = []
		page.on('pageerror', (error) => errors.push(`uncaught: ${error.message}`))
		page.on('response', (response) => {
			if (response.status() >= 400 && response.url().includes('/integration_giphy/')) {
				errors.push(`${response.status()} ${response.request().method()} ${new URL(response.url()).pathname}`)
			}
		})
		await use()
		expect(errors).toEqual([])
	}, { auto: true }],
})

/**
 * Store an API key for the app, or remove it again.
 *
 * The app only registers its link preview and search providers when an API key is set,
 * so the tests have to store one first.
 *
 * @param page a page of a logged in admin
 * @param apiKey the key to store, an empty string removes it
 */
async function setApiKey(page: Page, apiKey: string) {
	const response = await page.request.put('apps/integration_giphy/admin-config/sensitive', {
		headers: { requesttoken: await page.evaluate(() => (window as unknown as { OC: { requestToken: string } }).OC.requestToken) },
		data: { values: { api_key: apiKey } },
	})
	expect(response.ok(), `storing the API key: ${response.status()}`).toBe(true)
}

test.beforeEach(async ({ page }) => {
	await login(page.request, admin)
	// the test container cannot reach Giphy, so the app's GIF proxy answers with a pixel
	await page.route('**/apps/integration_giphy/gif/**', (route) => route.fulfill({ contentType: 'image/gif', body: gif }))
	await page.goto('apps/files/')
	await setApiKey(page, 'a-key-the-test-never-uses')
})

test.afterEach(async ({ page }) => {
	await setApiKey(page, '')
})

test.describe('Link previews', () => {
	test('offer the provider to the smart picker', async ({ page }) => {
		const response = await page.request.get('../ocs/v2.php/references/providers', { headers: ocs })
		expect(response.ok()).toBe(true)
		const providers = (await response.json()).ocs.data as Array<{ id: string, title: string, icon_url: string }>
		const provider = providers.find((candidate) => candidate.id === 'giphy-gif')
		expect(provider).toBeDefined()
		expect(provider?.title).toBe('GIF picker (by Giphy)')
		// the provider icon is served by the app
		expect((await page.request.get(provider!.icon_url)).ok()).toBe(true)
	})

	test('render a GIF in the reference widget', async ({ page }) => {
		await page.goto('apps/files/')
		const registered = await page.evaluate(async (richObject) => {
			const globals = window as unknown as {
				OC: { appswebroots: Record<string, string> }
				_vue_richtext_widgets: Record<string, { callback: (element: HTMLElement, data: object) => void }>
			}
			await import(/* @vite-ignore */ `${globals.OC.appswebroots.integration_giphy}/js/integration_giphy-referenceGif.mjs`)
			const element = document.createElement('div')
			element.id = 'reference-widget'
			document.body.appendChild(element)
			globals._vue_richtext_widgets.integration_giphy_gif.callback(element, {
				richObjectType: 'integration_giphy_gif',
				richObject,
				accessible: false,
			})
			return Object.keys(globals._vue_richtext_widgets)
		}, gifObject)
		expect(registered).toContain('integration_giphy_gif')

		const widget = page.locator('#reference-widget')
		await expect(widget.locator('img.image')).toBeVisible()
		await expect(widget.getByRole('link', { name: 'Powered by Giphy' })).toHaveAttribute('href', 'https://giphy.com')
	})
})

test.describe('GIF picker', () => {
	test('list the GIFs of the search provider', async ({ page }) => {
		// the picker asks the app for trending GIFs, which the test container cannot fetch from Giphy.
		// The answer is held back until the searching state has been checked.
		let answerTrending!: () => void
		const held = new Promise<void>((resolve) => {
			answerTrending = resolve
		})
		await page.route('**/apps/integration_giphy/api/v1/gifs/**', async (route) => {
			await held
			await route.fulfill({
				json: { ocs: { meta: { status: 'ok', statuscode: 200, message: 'OK' }, data: { entries: [searchEntry], cursor: 1 } } },
			})
		})
		const registered = await page.evaluate(async () => {
			const globals = window as unknown as {
				OC: { appswebroots: Record<string, string> }
				_vue_richtext_custom_picker_elements: Record<string, { callback: (element: HTMLElement, data: object) => void }>
			}
			await import(/* @vite-ignore */ `${globals.OC.appswebroots.integration_giphy}/js/integration_giphy-referenceGif.mjs`)
			const element = document.createElement('div')
			element.id = 'custom-picker'
			document.body.appendChild(element)
			globals._vue_richtext_custom_picker_elements['giphy-gif'].callback(element, {
				providerId: 'giphy-gif',
				accessible: false,
			})
			return Object.keys(globals._vue_richtext_custom_picker_elements)
		})
		expect(registered).toContain('giphy-gif')

		const picker = page.locator('#custom-picker')
		await expect(picker.getByText('Giphy GIF picker')).toBeVisible()
		await expect(picker.getByLabel('Search GIFs', { exact: true })).toBeVisible()
		// the picker says what it is doing while it waits for the answer
		await expect(picker.getByText('Searching...')).toBeVisible()

		answerTrending()
		const result = picker.locator('.result')
		await expect(result).toHaveCount(1)
		await expect(result).toHaveAttribute('title', searchEntry.title)
		await expect(result.locator('img.gif-image')).toBeVisible()
	})
})

test.describe('Search provider', () => {
	test('offer the provider to unified search', async ({ page }) => {
		const response = await page.request.get('../ocs/v2.php/search/providers', { headers: ocs })
		expect(response.ok()).toBe(true)
		const providers = (await response.json()).ocs.data as Array<{ id: string, appId: string, name: string }>
		expect(providers.find((candidate) => candidate.id === 'giphy-search-gifs')).toMatchObject({
			appId: 'integration_giphy',
			name: 'Giphy GIFs',
		})
	})
})
