import type { Transaction } from '@/api/transactions'
import type { Wallet } from '@/api/wallets'
import { useQuery, useQueryCache } from '@pinia/colada'
import { computed, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useDatePicker } from '@/composables/useDatePicker'
import { type PaginationMeta, useUrlPagination } from '@/composables/useUrlPagination'
import { TRANSACTION_QUERY_KEYS, transactionsListQuery } from '@/queries/transactions'
import { WALLET_QUERY_KEYS, walletsListQuery } from '@/queries/wallets'

type WalletParamValue = number | string | null

const FILTER_KEYS = [
  'date_from', 'date_to', 'type', 'status',
  'amount_min', 'amount_max', 'reference',
  'wallet_id', 'counterpart_wallet_id',
] as const

function parseWalletParam (value: string | undefined): WalletParamValue {
  if (!value) {
    return null
  }
  if (value === 'external') {
    return 'external'
  }
  return Number(value)
}

export function useTransactionFilters () {
  const route = useRoute()
  const router = useRouter()
  const { t } = useI18n()
  const queryCache = useQueryCache()
  const { page, perPage, handlePageChange, handlePerPageChange } = useUrlPagination({ defaultPerPage: 25 })

  const filterForm = reactive({
    date_from: '',
    date_to: '',
    type: 'All',
    status: 'All',
    amount_min: '',
    amount_max: '',
    reference: '',
    wallet_id: null as WalletParamValue,
    counterpart_wallet_id: null as WalletParamValue,
  })

  const types = computed(() => [
    { title: t('transactions.typeAll'), value: 'All' },
    { title: t('transactions.typeDebit'), value: 'Debit' },
    { title: t('transactions.typeCredit'), value: 'Credit' },
    { title: t('transactions.typeTransfer'), value: 'Transfer' },
  ])

  const statuses = computed(() => [
    { title: t('transactions.statusAll'), value: 'All' },
    { title: t('transactions.statusCompleted'), value: 'completed' },
    { title: t('transactions.statusPending'), value: 'pending_approval' },
    { title: t('transactions.statusRejected'), value: 'rejected' },
  ])
  const advancedPanel = ref<number[]>([])

  const { dateFromMenu, dateToMenu, dateFromValue, dateToValue, onDateSelected } = useDatePicker(filterForm)

  // Wallet dropdown data
  const { data: walletsData } = useQuery(walletsListQuery, () => ({ page: 1, perPage: 500 }))
  const wallets = computed<Wallet[]>(() => walletsData.value?.data ?? [])
  const walletOptions = computed(() => [{ id: 'external', name: t('transactions.external') }, ...wallets.value])

  // Sync filterForm from URL query params
  watch(
    () => route.fullPath,
    () => {
      filterForm.date_from = (route.query.date_from as string) || ''
      filterForm.date_to = (route.query.date_to as string) || ''
      const queryType = (route.query.type as string) || 'All'
      filterForm.type = queryType.charAt(0).toUpperCase() + queryType.slice(1).toLowerCase()
      filterForm.status = (route.query.status as string) || 'All'
      filterForm.amount_min = (route.query.amount_min as string) || ''
      filterForm.amount_max = (route.query.amount_max as string) || ''
      filterForm.wallet_id = parseWalletParam(route.query.wallet_id as string | undefined)
      filterForm.counterpart_wallet_id = parseWalletParam(route.query.counterpart_wallet_id as string | undefined)
    },
    { immediate: true },
  )

  // Transactions query
  const { data: transactionsData, isPending: processing } = useQuery(
    transactionsListQuery,
    () => ({
      page: page.value,
      perPage: perPage.value,
      dateFrom: (route.query.date_from as string) || undefined,
      dateTo: (route.query.date_to as string) || undefined,
      type: (route.query.type as string) || undefined,
      status: (route.query.status as string) || undefined,
      amountMin: (route.query.amount_min as string) || undefined,
      amountMax: (route.query.amount_max as string) || undefined,
      reference: (route.query.reference as string) || undefined,
      walletId: route.query.wallet_id ? Number(route.query.wallet_id) : null,
      counterpartWalletId: route.query.counterpart_wallet_id ? Number(route.query.counterpart_wallet_id) : null,
    }),
  )

  const transactions = computed<Transaction[]>(() => transactionsData.value?.data ?? [])
  const meta = computed<PaginationMeta>(() => transactionsData.value?.meta ?? {
    current_page: 1, last_page: 1, per_page: 25, total: 0, from: null, to: null,
  })

  const activeAdvancedFiltersCount = computed(() =>
    ['amount_min', 'amount_max', 'reference', 'wallet_id', 'counterpart_wallet_id']
      .filter(k => route.query[k])
      .length,
  )

  const activeFiltersCount = computed(() =>
    activeAdvancedFiltersCount.value
    + ['date_from', 'date_to', 'type', 'status'].filter(k => route.query[k]).length,
  )

  function handleFilter () {
    const raw: Record<string, string | undefined> = {
      ...route.query,
      page: '1',
      date_from: filterForm.date_from || undefined,
      date_to: filterForm.date_to || undefined,
      type: filterForm.type === 'All' ? undefined : filterForm.type.toLowerCase(),
      status: filterForm.status === 'All' ? undefined : filterForm.status,
      amount_min: filterForm.amount_min || undefined,
      amount_max: filterForm.amount_max || undefined,
      reference: filterForm.reference || undefined,
      wallet_id: filterForm.wallet_id ? String(filterForm.wallet_id) : undefined,
      counterpart_wallet_id: filterForm.counterpart_wallet_id ? String(filterForm.counterpart_wallet_id) : undefined,
    }

    const query = Object.fromEntries(
      Object.entries(raw).filter(([, v]) => v !== undefined),
    )
    router.push({ query })
  }

  function clearFilters () {
    const query = { ...route.query }
    for (const key of FILTER_KEYS) {
      delete query[key]
    }
    query.page = '1'
    router.push({ query })
  }

  async function invalidateQueries () {
    await Promise.all([
      queryCache.invalidateQueries({ key: TRANSACTION_QUERY_KEYS.root }),
      queryCache.invalidateQueries({ key: WALLET_QUERY_KEYS.root }),
    ])
  }

  return {
    filterForm,
    types,
    statuses,
    dateFromMenu,
    dateToMenu,
    dateFromValue,
    dateToValue,
    advancedPanel,
    wallets,
    walletOptions,
    transactions,
    meta,
    processing,
    activeAdvancedFiltersCount,
    activeFiltersCount,
    onDateSelected,
    handlePageChange,
    handlePerPageChange,
    handleFilter,
    clearFilters,
    invalidateQueries,
  }
}
