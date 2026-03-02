import type { AuditLogFilters, AuditLogResponse } from '@/api/audit-logs'
import { defineQuery, useQuery } from '@pinia/colada'
import { computed, reactive, ref, watch } from 'vue'
import { fetchAuditLogs } from '@/api/audit-logs'
import { useCompanyStore } from '@/stores/company'

export interface AuditLogQueryParams {
  category?: string
  severity?: string
  dateFrom?: string
  dateTo?: string
  perPage: number
}

export const AUDIT_LOG_QUERY_KEYS = {
  root: ['audit-logs'] as const,
  list: (params: AuditLogQueryParams) => [...AUDIT_LOG_QUERY_KEYS.root, params] as const,
}

export const useAuditLogList = defineQuery(() => {
  const companyStore = useCompanyStore()

  const filters = reactive<AuditLogQueryParams>({
    perPage: 25,
  })

  const cursor = ref<number | undefined>(undefined)
  const accumulatedData = ref<AuditLogResponse['data']>([])
  const nextCursor = ref<number | null>(null)
  const isLoadingMore = ref(false)

  const { data, ...rest } = useQuery({
    key: () => AUDIT_LOG_QUERY_KEYS.list({ ...filters }),
    query: async () => {
      const params: AuditLogFilters = {
        per_page: filters.perPage,
        cursor: cursor.value,
      }
      if (filters.category) {
        params.category = filters.category
      }
      if (filters.severity) {
        params.severity = filters.severity
      }
      if (filters.dateFrom) {
        params.date_from = filters.dateFrom
      }
      if (filters.dateTo) {
        params.date_to = filters.dateTo
      }
      if (companyStore.currentCompany) {
        (params as Record<string, unknown>).company_id = companyStore.currentCompany.id
      }

      const response = await fetchAuditLogs(params)
      return response.data
    },
  })

  watch(data, newData => {
    if (!newData) {
      return
    }

    if (isLoadingMore.value) {
      accumulatedData.value.push(...newData.data)
      isLoadingMore.value = false
    } else {
      accumulatedData.value = [...newData.data]
    }
    nextCursor.value = newData.meta.next_cursor
  })

  const logs = computed(() => accumulatedData.value)

  function applyFilters(newFilters: Partial<AuditLogQueryParams>) {
    cursor.value = undefined
    isLoadingMore.value = false
    accumulatedData.value = []
    Object.assign(filters, newFilters)
  }

  function loadMore() {
    if (!nextCursor.value) {
      return
    }
    isLoadingMore.value = true
    cursor.value = nextCursor.value
  }

  function clearFilters() {
    applyFilters({ category: undefined, severity: undefined, dateFrom: undefined, dateTo: undefined })
  }

  return {
    ...rest,
    logs,
    filters,
    nextCursor,
    isLoadingMore,
    applyFilters,
    loadMore,
    clearFilters,
  }
})
