<script lang="ts" setup>
import type { Transaction } from '@/api/transactions'
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { fetchTransactions } from '@/api/transactions'
import TransactionTable from '@/components/features/TransactionTable.vue'
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

const { t } = useI18n()
const authStore = useAuthStore()

const transactions = ref<Transaction[]>([])
const loading = ref(false)

async function loadTransactions() {
    loading.value = true
    try {
        const response = await fetchTransactions({
            ...props.filterParams,
            per_page: props.limit,
        })
        transactions.value = response.data.data
    } catch {
        transactions.value = []
    } finally {
        loading.value = false
    }
}

watch(() => authStore.isManagerOrAdmin, isAllowed => {
    if (isAllowed) {
        loadTransactions()
    }
}, { immediate: true })

const viewAllUrl = `/transactions?${new URLSearchParams(props.viewAllQuery).toString()}`
</script>

<template>
    <TransactionTable compact :is-admin="authStore.isAdmin" :is-manager-or-admin="authStore.isManagerOrAdmin"
        :items="transactions" :loading="loading" :title="title">
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
