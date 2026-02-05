import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}

export function usePagination (
  fetchData: (params: { page: number, per_page: number }) => Promise<void>,
  options: {
    defaultPerPage?: number
    perPageOptions?: number[]
  } = {},
) {
  const route = useRoute()
  const router = useRouter()

  const perPageOptions = options.perPageOptions || [25, 50, 100, 250, 500]
  const defaultPerPage = options.defaultPerPage || perPageOptions[0] || 25

  const page = ref(1)
  const perPage = ref(defaultPerPage)

  const meta = ref<PaginationMeta>({
    current_page: 1,
    last_page: 1,
    per_page: defaultPerPage,
    total: 0,
    from: null,
    to: null,
  })

  const updateUrl = (newPage: number, newPerPage: number) => {
    const query = { ...route.query }

    if (newPage === 1) {
      delete query.page
    } else {
      query.page = String(newPage)
    }

    if (newPerPage === defaultPerPage) {
      delete query.per_page
    } else {
      query.per_page = String(newPerPage)
    }

    router.push({ query })
  }

  const handlePageChange = (newPage: number) => {
    updateUrl(newPage, perPage.value)
  }

  const handlePerPageChange = (newPerPage: number) => {
    updateUrl(1, newPerPage) // Reset to first page when size changes
  }

  // Handle browser back/forward and internal navigation
  // We watch fullPath to ensure any change in query triggers a fetch
  watch(
    () => route.fullPath,
    () => {
      const qPage = Number(route.query.page) || 1
      const qPerPage = Number(route.query.per_page) || defaultPerPage

      page.value = qPage
      perPage.value = qPerPage

      fetchData({ page: qPage, per_page: qPerPage })
    },
    { immediate: true },
  )

  const refresh = () => {
    return fetchData({ page: page.value, per_page: perPage.value })
  }

  return {
    page,
    perPage,
    perPageOptions,
    meta,
    handlePageChange,
    handlePerPageChange,
    refresh,
  }
}
