import { describe, expect, it } from 'vitest'
import { reactive, nextTick } from 'vue'
import { useDatePicker } from './useDatePicker'

describe('useDatePicker', () => {
    function createForm() {
        return reactive({ date_from: '', date_to: '' })
    }

    describe('initial state', () => {
        it('starts with menus closed and null date values', () => {
            const form = createForm()
            const { dateFromMenu, dateToMenu, dateFromValue, dateToValue } = useDatePicker(form)

            expect(dateFromMenu.value).toBe(false)
            expect(dateToMenu.value).toBe(false)
            expect(dateFromValue.value).toBeNull()
            expect(dateToValue.value).toBeNull()
        })
    })

    describe('onDateSelected', () => {
        it('formats and sets "from" date, closes menu', () => {
            const form = createForm()
            const { dateFromMenu, onDateSelected } = useDatePicker(form)
            dateFromMenu.value = true

            onDateSelected('from', new Date('2024-06-15T12:00:00Z'))

            expect(form.date_from).toBe('2024-06-15')
            expect(dateFromMenu.value).toBe(false)
        })

        it('formats and sets "to" date, closes menu', () => {
            const form = createForm()
            const { dateToMenu, onDateSelected } = useDatePicker(form)
            dateToMenu.value = true

            onDateSelected('to', new Date('2024-12-25T00:00:00Z'))

            expect(form.date_to).toBe('2024-12-25')
            expect(dateToMenu.value).toBe(false)
        })

        it('does nothing when value is null', () => {
            const form = createForm()
            const { dateFromMenu, onDateSelected } = useDatePicker(form)
            dateFromMenu.value = true

            onDateSelected('from', null)

            expect(form.date_from).toBe('')
            expect(dateFromMenu.value).toBe(true)
        })
    })

    describe('date string ↔ Date sync', () => {
        it('sets dateFromValue when form.date_from is populated', async () => {
            const form = createForm()
            const { dateFromValue } = useDatePicker(form)

            form.date_from = '2024-03-15'
            await nextTick()

            expect(dateFromValue.value).toBeInstanceOf(Date)
            expect(dateFromValue.value?.toISOString()).toContain('2024-03-15')
        })

        it('clears dateFromValue when form.date_from is cleared', async () => {
            const form = createForm()
            const { dateFromValue, onDateSelected } = useDatePicker(form)

            onDateSelected('from', new Date('2024-03-15'))
            await nextTick()

            form.date_from = ''
            await nextTick()

            expect(dateFromValue.value).toBeNull()
        })

        it('sets dateToValue when form.date_to is populated', async () => {
            const form = createForm()
            const { dateToValue } = useDatePicker(form)

            form.date_to = '2024-06-01'
            await nextTick()

            expect(dateToValue.value).toBeInstanceOf(Date)
        })

        it('clears dateToValue when form.date_to is cleared', async () => {
            const form = createForm()
            const { dateToValue, onDateSelected } = useDatePicker(form)

            onDateSelected('to', new Date('2024-06-01'))
            await nextTick()

            form.date_to = ''
            await nextTick()

            expect(dateToValue.value).toBeNull()
        })
    })
})
