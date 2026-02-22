import { ref } from 'vue'

export function useRefreshData(refreshFn: () => Promise<void>) {
    const refreshing = ref(false)

    async function refresh() {
        if (refreshing.value) return
        refreshing.value = true
        try {
            await refreshFn()
        } finally {
            refreshing.value = false
        }
    }

    return { refreshing, refresh }
}
