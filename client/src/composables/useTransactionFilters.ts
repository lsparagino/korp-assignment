import type { Transaction } from '@/api/transactions'
import type { Wallet } from '@/api/wallets'
import { useQuery, useQueryCache } from '@pinia/colada'
import { computed, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useDatePicker } from '@/composables/useDatePicker'
import { type PaginationMeta, useUrlPagination } from '@/composables/useUrlPagination'
import { TRANSACTION_QUERY_KEYS, transactionsListQuery } from '@/queries/transactions'
import { walletsListQuery } from '@/queries/wallets'

type WalletParamValue = number | string | null

const FILTER_KEYS = [
  'date_from', 'date_to', 'type', 'status',
  'amount_min', 'amount_max', 'reference',
  'has_wallet_id', 'from_wallet_id', 'to_wallet_id',
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
    has_wallet_id: null as WalletParamValue,
    from_wallet_id: null as WalletParamValue,
    to_wallet_id: null as WalletParamValue,
  })

  // 'simple' = single wallet filter (has_wallet_id), 'specific' = from/to wallet filters
  const walletFilterMode = ref<'simple' | 'specific'>('simple')

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

  const userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone

  const { dateFromMenu, dateToMenu, dateFromValue, dateToValue, dateFromMax, dateToMin, onDateSelected } = useDatePicker(filterForm)

  const { data: walletsData } = useQuery(walletsListQuery, () => ({ page: 1, perPage: 500 }))
  const wallets = computed<Wallet[]>(() => walletsData.value?.data ?? [])
  const walletOptions = computed(() => [{ id: 'external', name: t('transactions.external') }, ...wallets.value])

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
      filterForm.has_wallet_id = parseWalletParam(route.query.has_wallet_id as string | undefined)
      filterForm.from_wallet_id = parseWalletParam(route.query.from_wallet_id as string | undefined)
      filterForm.to_wallet_id = parseWalletParam(route.query.to_wallet_id as string | undefined)

      walletFilterMode.value = route.query.from_wallet_id || route.query.to_wallet_id ? 'specific' : 'simple'
    },
    { immediate: true },
  )

  const { data: transactionsData, isPending: processing } = useQuery(
    transactionsListQuery,
    () => {
      const dateFrom = (route.query.date_from as string) || undefined
      const dateTo = (route.query.date_to as string) || undefined
      return {
        page: page.value,
        perPage: perPage.value,
        dateFrom,
        dateTo,
        tz: dateFrom || dateTo ? userTimezone : undefined,
        type: (route.query.type as string) || undefined,
        status: (route.query.status as string) || undefined,
        amountMin: (route.query.amount_min as string) || undefined,
        amountMax: (route.query.amount_max as string) || undefined,
        reference: (route.query.reference as string) || undefined,
        fromWalletId: parseWalletParam(route.query.from_wallet_id as string | undefined),
        toWalletId: parseWalletParam(route.query.to_wallet_id as string | undefined),
        hasWalletId: parseWalletParam(route.query.has_wallet_id as string | undefined),
        initiatorUserId: route.query.initiator_user_id ? Number(route.query.initiator_user_id) : null,
      }
    },
  )

  const transactions = computed<Transaction[]>(() => transactionsData.value?.data ?? [])
  const meta = computed<PaginationMeta>(() => transactionsData.value?.meta ?? {
    current_page: 1, last_page: 1, per_page: 25, total: 0, from: null, to: null,
  })

  const activeAdvancedFiltersCount = computed(() =>
    ['amount_min', 'amount_max', 'reference', 'has_wallet_id', 'from_wallet_id', 'to_wallet_id']
      .filter(k => route.query[k])
      .length,
  )

  const activeFiltersCount = computed(() =>
    activeAdvancedFiltersCount.value
    + ['date_from', 'date_to', 'type', 'status'].filter(k => route.query[k]).length,
  )

  function toggleWalletFilterMode () {
    if (walletFilterMode.value === 'simple') {
      walletFilterMode.value = 'specific'
      filterForm.has_wallet_id = null
    } else {
      walletFilterMode.value = 'simple'
      filterForm.from_wallet_id = null
      filterForm.to_wallet_id = null
    }
  }

  function handleFilter () {
    const dateFrom = filterForm.date_from || undefined
    const dateTo = filterForm.date_to || undefined

    const raw: Record<string, string | undefined> = {
      ...route.query,
      page: '1',
      date_from: dateFrom,
      date_to: dateTo,
      tz: dateFrom || dateTo ? userTimezone : undefined,
      type: filterForm.type === 'All' ? undefined : filterForm.type.toLowerCase(),
      status: filterForm.status === 'All' ? undefined : filterForm.status,
      amount_min: filterForm.amount_min || undefined,
      amount_max: filterForm.amount_max || undefined,
      reference: filterForm.reference || undefined,
      has_wallet_id: walletFilterMode.value === 'simple' && filterForm.has_wallet_id
        ? String(filterForm.has_wallet_id)
        : undefined,
      from_wallet_id: walletFilterMode.value === 'specific' && filterForm.from_wallet_id
        ? String(filterForm.from_wallet_id)
        : undefined,
      to_wallet_id: walletFilterMode.value === 'specific' && filterForm.to_wallet_id
        ? String(filterForm.to_wallet_id)
        : undefined,
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
    delete query.tz
    query.page = '1'
    walletFilterMode.value = 'simple'
    router.push({ query })
  }

  async function invalidateQueries () {
    await queryCache.invalidateQueries({ key: TRANSACTION_QUERY_KEYS.root })
  }

  return {
    filterForm,
    types,
    statuses,
    dateFromMenu,
    dateToMenu,
    dateFromValue,
    dateToValue,
    dateFromMax,
    dateToMin,
    advancedPanel,
    wallets,
    walletOptions,
    walletFilterMode,
    transactions,
    meta,
    processing,
    activeAdvancedFiltersCount,
    activeFiltersCount,
    toggleWalletFilterMode,
    onDateSelected,
    handlePageChange,
    handlePerPageChange,
    handleFilter,
    clearFilters,
    invalidateQueries,
  }
}
