import { defineStore } from 'pinia'
import { ref } from 'vue'
import { fetchUserPreferences } from '@/api/settings'

export const usePreferencesStore = defineStore('preferences', () => {
  const dateLocale = ref(localStorage.getItem('pref_date_format') ?? 'en-GB')
  const numberLocale = ref(localStorage.getItem('pref_number_format') ?? 'en-GB')

  async function load () {
    try {
      const { data } = await fetchUserPreferences()
      dateLocale.value = data.data.date_format
      numberLocale.value = data.data.number_format
      localStorage.setItem('pref_date_format', data.data.date_format)
      localStorage.setItem('pref_number_format', data.data.number_format)
    } catch {
      // Keep defaults / cached values
    }
  }

  function update (dateFormat: string, numberFormat: string) {
    dateLocale.value = dateFormat
    numberLocale.value = numberFormat
    localStorage.setItem('pref_date_format', dateFormat)
    localStorage.setItem('pref_number_format', numberFormat)
  }

  function clear () {
    dateLocale.value = 'en-GB'
    numberLocale.value = 'en-GB'
    localStorage.removeItem('pref_date_format')
    localStorage.removeItem('pref_number_format')
  }

  return { dateLocale, numberLocale, load, update, clear }
})
