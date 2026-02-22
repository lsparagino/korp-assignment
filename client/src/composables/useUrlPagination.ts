import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}

interface UrlPaginationOptions {
  defaultPerPage?: number
}

export function useUrlPagination (options: UrlPaginationOptions = {}) {
  const route = useRoute()
  const router = useRouter()
  const defaultPerPage = options.defaultPerPage ?? 10

  const page = computed(() => Number(route.query.page) || 1)
  const perPage = computed(() => Number(route.query.per_page) || defaultPerPage)

  function handlePageChange (newPage: number) {
    const query = { ...route.query }
    if (newPage === 1) {
      delete query.page
    } else {
      query.page = String(newPage)
    }
    router.push({ query })
  }

  function handlePerPageChange (newPerPage: number) {
    const query: Record<string, string> = { ...route.query, page: '1' } as Record<string, string>
    if (newPerPage === defaultPerPage) {
      delete query.per_page
    } else {
      query.per_page = String(newPerPage)
    }
    router.push({ query })
  }

  return {
    page,
    perPage,
    handlePageChange,
    handlePerPageChange,
  }
}
