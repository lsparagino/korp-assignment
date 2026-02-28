import { useQueryCache } from '@pinia/colada'
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { preferencesQuery } from '@/queries/preferences'

export const usePreferencesStore = defineStore('preferences', () => {
  const dateLocale = ref(localStorage.getItem('pref_date_format') ?? 'en-GB')
  const numberLocale = ref(localStorage.getItem('pref_number_format') ?? 'en-GB')
  const queryCache = useQueryCache()

  async function load() {
    try {
      const entry = queryCache.ensure(preferencesQuery)
      await queryCache.fetch(entry)
      const data = entry.state.value.data
      if (data) {
        dateLocale.value = data.date_format
        numberLocale.value = data.number_format
        localStorage.setItem('pref_date_format', data.date_format)
        localStorage.setItem('pref_number_format', data.number_format)
      }
    } catch {
      // Keep defaults / cached values
    }
  }

  function update(dateFormat: string, numberFormat: string) {
    dateLocale.value = dateFormat
    numberLocale.value = numberFormat
    localStorage.setItem('pref_date_format', dateFormat)
    localStorage.setItem('pref_number_format', numberFormat)
  }

  function clear() {
    dateLocale.value = 'en-GB'
    numberLocale.value = 'en-GB'
    localStorage.removeItem('pref_date_format')
    localStorage.removeItem('pref_number_format')
  }

  return { dateLocale, numberLocale, load, update, clear }
})
