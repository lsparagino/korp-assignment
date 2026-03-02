<script lang="ts" setup>
import type { Transaction, FilterId, FilterNumericId } from '@/api/transactions'
import { computed, ref } from 'vue'
import { useQuery } from '@pinia/colada'
import TransactionTable from '@/components/features/TransactionTable.vue'
import { transactionsListQuery } from '@/queries/transactions'
import { useAuthStore } from '@/stores/auth'

interface Props {
    filterParams: Record<string, unknown>
    viewAllQuery: Record<string, string>
    limit?: number
    title?: string
}

const props = withDefaults(defineProps<Props>(), {
    limit: 5,
    title: 'Recent Transactions',
})

const authStore = useAuthStore()

const queryParams = computed(() => ({
    page: 1,
    perPage: props.limit,
    hasWalletId: (props.filterParams.has_wallet_id as FilterId) ?? undefined,
    initiatorUserId: (props.filterParams.initiator_user_id as FilterNumericId) ?? undefined,
    fromWalletId: (props.filterParams.from_wallet_id as FilterId) ?? undefined,
    toWalletId: (props.filterParams.to_wallet_id as FilterId) ?? undefined,
}))

const { data: transactionsData, isPending: loading, refetch } = useQuery(
    transactionsListQuery,
    () => queryParams.value,
)

const transactions = computed<Transaction[]>(() => {
    if (!authStore.isManagerOrAdmin) return []
    return transactionsData.value?.data ?? []
})

const refreshing = ref(false)

async function refresh() {
    if (refreshing.value) return
    refreshing.value = true
    try {
        await refetch()
    } finally {
        refreshing.value = false
    }
}

const viewAllUrl = `/transactions?${new URLSearchParams(props.viewAllQuery).toString()}`
</script>

<template>
    <TransactionTable compact :is-admin="authStore.isAdmin" :is-manager-or-admin="authStore.isManagerOrAdmin"
        :items="transactions" :loading="loading" :refreshing="refreshing" :title="title" @refresh="refresh">
        <template #footer>
            <div class="d-flex justify-end pa-2">
                <v-btn class="text-none font-weight-bold" color="primary" data-testid="view-all-link" :to="viewAllUrl"
                    variant="text">
                    {{ $t('transactions.viewAll') }}
                </v-btn>
            </div>
        </template>
    </TransactionTable>
</template>
