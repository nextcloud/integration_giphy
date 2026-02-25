/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
	plugins: [vue()],
	test: {
		environment: 'jsdom',
		environmentOptions: {
			jsdom: {
				url: 'http://nextcloud.local',
			},
		},
		setupFiles: ['src/__tests__/setup.js'],
		include: ['src/**/*.{test,spec}.?(c|m)[jt]s?(x)'],
		server: {
			deps: {
				inline: [/@nextcloud\//],
			},
		},
	},
})
