/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { delay } from '../utils.js'

describe('delay', () => {
	beforeEach(() => {
		vi.useFakeTimers()
	})

	afterEach(() => {
		vi.useRealTimers()
	})

	it('fires callback only after the specified delay', () => {
		const cb = vi.fn()
		const delayed = delay(cb, 200)

		delayed()
		expect(cb).not.toHaveBeenCalled()

		vi.advanceTimersByTime(199)
		expect(cb).not.toHaveBeenCalled()

		vi.advanceTimersByTime(1)
		expect(cb).toHaveBeenCalledOnce()
	})

	it('resets timer on rapid calls — only the last one fires', () => {
		const cb = vi.fn()
		const delayed = delay(cb, 100)

		delayed()
		vi.advanceTimersByTime(80)
		delayed() // resets the timer
		vi.advanceTimersByTime(80)
		expect(cb).not.toHaveBeenCalled()

		vi.advanceTimersByTime(20)
		expect(cb).toHaveBeenCalledOnce()
	})

	it('fires with zero delay when ms is omitted', () => {
		const cb = vi.fn()
		const delayed = delay(cb)

		delayed()
		expect(cb).not.toHaveBeenCalled()

		vi.advanceTimersByTime(0)
		expect(cb).toHaveBeenCalledOnce()
	})

	it('preserves this context and arguments', () => {
		const cb = vi.fn()
		const delayed = delay(cb, 50)

		const context = { name: 'giphy' }
		delayed.call(context, 'arg1', 'arg2')

		vi.advanceTimersByTime(50)
		expect(cb).toHaveBeenCalledWith('arg1', 'arg2')
		expect(cb.mock.instances[0]).toBe(context)
	})
})
