/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { config } from '@vue/test-utils'

// Mock Nextcloud globals
window.OC = {
	config: { version: '30.0.0' },
	webroot: '',
}
window.OCA = {}
window._oc_webroot = ''

// Translation stubs — return the string as-is
const t = (app, str) => str
const n = (app, singular, plural, count) => (count === 1 ? singular : plural)
window.t = t
window.n = n

// Make t() and n() available on all Vue component instances,
// matching the mixin the real app uses: app.mixin({ methods: { t, n } })
config.global.mocks = {
	t,
	n,
}
