import { DateTime } from 'luxon'
import { computed, ref, watch } from 'vue'

export function useDatePicker (filterForm: { date_from: string, date_to: string }) {
  const dateFromMenu = ref(false)
  const dateToMenu = ref(false)
  const dateFromValue = ref<Date | null>(null)
  const dateToValue = ref<Date | null>(null)

  // Constrain from-picker so it cannot go past the to-date
  const dateFromMax = computed<Date | undefined>(() => {
    if (filterForm.date_to) {
      return new Date(filterForm.date_to)
    }
    return undefined
  })

  // Constrain to-picker so it cannot go before the from-date
  const dateToMin = computed<Date | undefined>(() => {
    if (filterForm.date_from) {
      return new Date(filterForm.date_from)
    }
    return undefined
  })

  watch(
    () => filterForm.date_from,
    val => {
      if (val && !dateFromValue.value) {
        dateFromValue.value = new Date(val)
      } else if (!val) {
        dateFromValue.value = null
      }
    },
  )

  watch(
    () => filterForm.date_to,
    val => {
      if (val && !dateToValue.value) {
        dateToValue.value = new Date(val)
      } else if (!val) {
        dateToValue.value = null
      }
    },
  )

  function onDateSelected (type: 'from' | 'to', value: Date | null) {
    if (!value) {
      return
    }

    const date = new Date(value)
    const formatted = DateTime.fromJSDate(date).toFormat('yyyy-MM-dd')

    if (type === 'from') {
      filterForm.date_from = formatted

      // Safety: clear to-date if it is before the newly selected from-date
      if (filterForm.date_to && filterForm.date_to < formatted) {
        filterForm.date_to = ''
      }

      dateFromMenu.value = false
    } else {
      filterForm.date_to = formatted

      // Safety: clear from-date if it is after the newly selected to-date
      if (filterForm.date_from && filterForm.date_from > formatted) {
        filterForm.date_from = ''
      }

      dateToMenu.value = false
    }
  }

  return {
    dateFromMenu,
    dateToMenu,
    dateFromValue,
    dateToValue,
    dateFromMax,
    dateToMin,
    onDateSelected,
  }
}
