import { describe, expect, it } from 'vitest'
import { nextTick, reactive } from 'vue'
import { useDatePicker } from './useDatePicker'

function createForm () {
  return reactive({ date_from: '', date_to: '' })
}

describe('useDatePicker', () => {
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

    it('uses local date, not UTC, to avoid day shift', () => {
      const form = createForm()
      const { onDateSelected } = useDatePicker(form)

      // Simulate a date picker returning local midnight for March 11
      const localDate = new Date(2024, 2, 11, 0, 0, 0) // March 11 local
      onDateSelected('from', localDate)

      expect(form.date_from).toBe('2024-03-11')
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

  describe('date range constraints', () => {
    it('dateFromMax equals the to-date when set', () => {
      const form = createForm()
      const { dateFromMax } = useDatePicker(form)

      form.date_to = '2024-06-15'
      expect(dateFromMax.value).toBeInstanceOf(Date)
      expect(dateFromMax.value?.toISOString()).toContain('2024-06-15')
    })

    it('dateFromMax is undefined when no to-date', () => {
      const form = createForm()
      const { dateFromMax } = useDatePicker(form)

      expect(dateFromMax.value).toBeUndefined()
    })

    it('dateToMin equals the from-date when set', () => {
      const form = createForm()
      const { dateToMin } = useDatePicker(form)

      form.date_from = '2024-03-01'
      expect(dateToMin.value).toBeInstanceOf(Date)
      expect(dateToMin.value?.toISOString()).toContain('2024-03-01')
    })

    it('dateToMin is undefined when no from-date', () => {
      const form = createForm()
      const { dateToMin } = useDatePicker(form)

      expect(dateToMin.value).toBeUndefined()
    })

    it('clears to-date when selecting a from-date after it', () => {
      const form = createForm()
      const { onDateSelected } = useDatePicker(form)

      form.date_to = '2024-03-01'
      onDateSelected('from', new Date(2024, 5, 15)) // June 15 — after March 1

      expect(form.date_from).toBe('2024-06-15')
      expect(form.date_to).toBe('')
    })

    it('clears from-date when selecting a to-date before it', () => {
      const form = createForm()
      const { onDateSelected } = useDatePicker(form)

      form.date_from = '2024-06-15'
      onDateSelected('to', new Date(2024, 2, 1)) // March 1 — before June 15

      expect(form.date_to).toBe('2024-03-01')
      expect(form.date_from).toBe('')
    })

    it('does not clear to-date when from-date is before it', () => {
      const form = createForm()
      const { onDateSelected } = useDatePicker(form)

      form.date_to = '2024-06-15'
      onDateSelected('from', new Date(2024, 2, 1)) // March 1 — before June 15

      expect(form.date_from).toBe('2024-03-01')
      expect(form.date_to).toBe('2024-06-15')
    })
  })
})
