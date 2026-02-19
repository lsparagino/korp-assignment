import type { Transaction } from '@/api/transactions'
import type { Wallet } from '@/api/wallets'
import { computed, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery } from '@pinia/colada'
import { useDatePicker } from '@/composables/useDatePicker'
import { useUrlPagination } from '@/composables/useUrlPagination'
import { transactionsListQuery } from '@/queries/transactions'
import { walletsListQuery } from '@/queries/wallets'

const FILTER_KEYS = [
    'date_from', 'date_to', 'type',
    'amount_min', 'amount_max', 'reference',
    'from_wallet_id', 'to_wallet_id',
] as const

export function useTransactionFilters() {
    const route = useRoute()
    const router = useRouter()
    const { page, perPage, handlePageChange, handlePerPageChange } = useUrlPagination({ defaultPerPage: 25 })

    const filterForm = reactive({
        date_from: '',
        date_to: '',
        type: 'All',
        amount_min: '',
        amount_max: '',
        reference: '',
        from_wallet_id: null as number | string | null,
        to_wallet_id: null as number | string | null,
    })

    const types = ['All', 'Debit', 'Credit']
    const advancedPanel = ref<number[]>([])

    const { dateFromMenu, dateToMenu, dateFromValue, dateToValue, onDateSelected } = useDatePicker(filterForm)

    // Wallet dropdown data
    const { data: walletsData } = useQuery(walletsListQuery, () => ({ page: 1, perPage: 500 }))
    const wallets = computed<Wallet[]>(() => walletsData.value?.data ?? [])
    const walletOptions = computed(() => [{ id: 'external', name: 'External' }, ...wallets.value])

    // Sync filterForm from URL query params
    watch(
        () => route.fullPath,
        () => {
            filterForm.date_from = (route.query.date_from as string) || ''
            filterForm.date_to = (route.query.date_to as string) || ''
            const queryType = (route.query.type as string) || 'All'
            filterForm.type = queryType.charAt(0).toUpperCase() + queryType.slice(1).toLowerCase()
            filterForm.amount_min = (route.query.amount_min as string) || ''
            filterForm.amount_max = (route.query.amount_max as string) || ''
            filterForm.from_wallet_id = route.query.from_wallet_id
                ? (route.query.from_wallet_id === 'external' ? 'external' : Number(route.query.from_wallet_id))
                : null
            filterForm.to_wallet_id = route.query.to_wallet_id
                ? (route.query.to_wallet_id === 'external' ? 'external' : Number(route.query.to_wallet_id))
                : null
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
            amountMin: (route.query.amount_min as string) || undefined,
            amountMax: (route.query.amount_max as string) || undefined,
            reference: (route.query.reference as string) || undefined,
            fromWalletId: route.query.from_wallet_id ? Number(route.query.from_wallet_id) : null,
            toWalletId: route.query.to_wallet_id ? Number(route.query.to_wallet_id) : null,
        }),
    )

    const transactions = computed<Transaction[]>(() => transactionsData.value?.data ?? [])
    const meta = computed(() => transactionsData.value?.meta ?? {
        current_page: 1, last_page: 1, per_page: 25, total: 0, from: null, to: null,
    })

    const activeAdvancedFiltersCount = computed(() =>
        ['amount_min', 'amount_max', 'reference', 'from_wallet_id', 'to_wallet_id']
            .filter(k => route.query[k]).length,
    )

    const activeFiltersCount = computed(() =>
        activeAdvancedFiltersCount.value
        + ['date_from', 'date_to', 'type'].filter(k => route.query[k]).length,
    )

    function handleFilter() {
        const query: Record<string, string | undefined> = { ...route.query, page: '1' }
        query.date_from = filterForm.date_from || undefined
        query.date_to = filterForm.date_to || undefined
        query.type = filterForm.type === 'All' ? undefined : filterForm.type.toLowerCase()
        query.amount_min = filterForm.amount_min || undefined
        query.amount_max = filterForm.amount_max || undefined
        query.reference = filterForm.reference || undefined
        query.from_wallet_id = filterForm.from_wallet_id ? String(filterForm.from_wallet_id) : undefined
        query.to_wallet_id = filterForm.to_wallet_id ? String(filterForm.to_wallet_id) : undefined

        for (const key of Object.keys(query)) {
            if (query[key] === undefined) delete query[key]
        }
        router.push({ query })
    }

    function clearFilters() {
        const query = { ...route.query }
        for (const key of FILTER_KEYS) delete query[key]
        query.page = '1'
        router.push({ query })
    }

    return {
        filterForm,
        types,
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
    }
}
