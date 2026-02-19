import { ref, watch } from 'vue'

export function useDatePicker(filterForm: { date_from: string, date_to: string }) {
    const dateFromMenu = ref(false)
    const dateToMenu = ref(false)
    const dateFromValue = ref<Date | null>(null)
    const dateToValue = ref<Date | null>(null)

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

    function onDateSelected(type: 'from' | 'to', value: Date | null) {
        if (!value) return

        const date = new Date(value)
        const formatted = date.toISOString().split('T')[0] as string

        if (type === 'from') {
            filterForm.date_from = formatted
            dateFromMenu.value = false
        } else {
            filterForm.date_to = formatted
            dateToMenu.value = false
        }
    }

    return {
        dateFromMenu,
        dateToMenu,
        dateFromValue,
        dateToValue,
        onDateSelected,
    }
}
