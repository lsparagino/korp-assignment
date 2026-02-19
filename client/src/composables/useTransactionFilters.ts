import type { Transaction } from '@/api/transactions'
import type { Wallet } from '@/api/wallets'
import { computed, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery } from '@pinia/colada'
import { transactionsListQuery } from '@/queries/transactions'
import { walletsListQuery } from '@/queries/wallets'

const DEFAULT_PER_PAGE = 25

export function useTransactionFilters() {
    const route = useRoute()
    const router = useRouter()

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

    const dateFromMenu = ref(false)
    const dateToMenu = ref(false)
    const dateFromValue = ref<Date | null>(null)
    const dateToValue = ref<Date | null>(null)
    const advancedPanel = ref<number[]>([])

    const page = computed(() => Number(route.query.page) || 1)
    const perPage = computed(() => Number(route.query.per_page) || DEFAULT_PER_PAGE)

    // Wallet dropdown data
    const { data: walletsData } = useQuery(
        walletsListQuery,
        () => ({ page: 1, perPage: 500 }),
    )
    const wallets = computed<Wallet[]>(() => walletsData.value?.data ?? [])
    const walletOptions = computed(() => [
        { id: 'external', name: 'External' },
        ...wallets.value,
    ])

    // Sync filterForm from URL query params
    watch(
        () => route.fullPath,
        () => {
            filterForm.date_from = (route.query.date_from as string) || ''
            filterForm.date_to = (route.query.date_to as string) || ''
            const queryType = (route.query.type as string) || 'All'
            filterForm.type
                = queryType.charAt(0).toUpperCase()
                + queryType.slice(1).toLowerCase()
            filterForm.amount_min = (route.query.amount_min as string) || ''
            filterForm.amount_max = (route.query.amount_max as string) || ''
            filterForm.reference = (route.query.reference as string) || ''
            filterForm.from_wallet_id = route.query.from_wallet_id
                ? (route.query.from_wallet_id === 'external'
                    ? 'external'
                    : Number(route.query.from_wallet_id))
                : null
            filterForm.to_wallet_id = route.query.to_wallet_id
                ? (route.query.to_wallet_id === 'external'
                    ? 'external'
                    : Number(route.query.to_wallet_id))
                : null
        },
        { immediate: true },
    )

    // Transactions query — keyed by all filter + pagination params
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
        current_page: 1,
        last_page: 1,
        per_page: DEFAULT_PER_PAGE,
        total: 0,
        from: null,
        to: null,
    })

    const activeAdvancedFiltersCount = computed(() => {
        let count = 0
        if (route.query.amount_min) count++
        if (route.query.amount_max) count++
        if (route.query.reference) count++
        if (route.query.from_wallet_id) count++
        if (route.query.to_wallet_id) count++
        return count
    })

    const activeFiltersCount = computed(() => {
        let count = activeAdvancedFiltersCount.value
        if (route.query.date_from) count++
        if (route.query.date_to) count++
        if (route.query.type) count++
        return count
    })

    // Sync date values from strings to Date objects
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

    function handlePageChange(newPage: number) {
        const query = { ...route.query }
        if (newPage === 1) {
            delete query.page
        } else {
            query.page = String(newPage)
        }
        router.push({ query })
    }

    function handlePerPageChange(newPerPage: number) {
        const query: Record<string, string> = { ...route.query, page: '1' } as Record<string, string>
        if (newPerPage === DEFAULT_PER_PAGE) {
            delete query.per_page
        } else {
            query.per_page = String(newPerPage)
        }
        router.push({ query })
    }

    function handleFilter() {
        const query = {
            ...route.query,
            page: '1',
            date_from: filterForm.date_from || undefined,
            date_to: filterForm.date_to || undefined,
            type:
                filterForm.type === 'All'
                    ? undefined
                    : filterForm.type.toLowerCase(),
            amount_min: filterForm.amount_min || undefined,
            amount_max: filterForm.amount_max || undefined,
            reference: filterForm.reference || undefined,
            from_wallet_id: filterForm.from_wallet_id || undefined,
            to_wallet_id: filterForm.to_wallet_id || undefined,
        }

        for (const key of Object.keys(query)) {
            if ((query as Record<string, unknown>)[key] === undefined) {
                delete (query as Record<string, unknown>)[key]
            }
        }

        router.push({ query })
    }

    function clearFilters() {
        const query = { ...route.query }
        delete query.date_from
        delete query.date_to
        delete query.type
        delete query.amount_min
        delete query.amount_max
        delete query.reference
        delete query.from_wallet_id
        delete query.to_wallet_id
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
