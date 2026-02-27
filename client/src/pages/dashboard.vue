<script lang="ts" setup>
  import type { Transaction } from '@/api/transactions'
  import type { Wallet } from '@/api/wallets'
  import { useQuery, useQueryCache } from '@pinia/colada'
  import { computed } from 'vue'
  import TransactionTable from '@/components/features/TransactionTable.vue'
  import PageHeader from '@/components/layout/PageHeader.vue'
  import { DASHBOARD_QUERY_KEYS, dashboardQuery } from '@/queries/dashboard'
  import { useAuthStore } from '@/stores/auth'
  import { useCompanyStore } from '@/stores/company'
  import { formatCurrency, getAmountColor, getCurrencyIcon } from '@/utils/formatters'

  const authStore = useAuthStore()
  const companyStore = useCompanyStore()

  interface BalanceSummary {
    currency: string
    amount: number
    formatted: string
  }

  const { data, isPending: loading } = useQuery(
    dashboardQuery,
    () => companyStore.currentCompany?.id ?? 0,
  )

  const queryCache = useQueryCache()

  const balances = computed<BalanceSummary[]>(() =>
    (data.value?.balances ?? []).map(
      (b: { currency: string, amount: number }) => ({
        ...b,
        formatted: formatCurrency(b.amount, b.currency),
      }),
    ),
  )
  const topWallets = computed<Wallet[]>(() => data.value?.top_wallets ?? [])
  const otherWallets = computed(() => data.value?.others ?? { count: 0, totalUSD: 0, totalEUR: 0 })
  const recentTransactions = computed<Transaction[]>(() => data.value?.transactions?.data ?? data.value?.transactions ?? [])
  const wallets = computed<Wallet[]>(() => (data.value?.wallets ?? []).map((w: any) => ({ id: w.id, name: w.name })))
  const pendingTransactions = computed<Transaction[]>(() => data.value?.pending_transactions?.data ?? data.value?.pending_transactions ?? [])

  function invalidateDashboard () {
    const companyId = companyStore.currentCompany?.id ?? 0
    queryCache.invalidateQueries({ key: DASHBOARD_QUERY_KEYS.byCompany(companyId) })
  }

</script>

<template>
  <PageHeader :title="$t('dashboard.title')" />

  <v-alert
    v-if="authStore.isAdmin && data?.missing_thresholds"
    class="mb-6 "
    color="warning"
    data-testid="missing-thresholds-warning"
    density="compact"
    type="warning"
    variant="tonal"
  >
    <div class="d-flex align-center justify-space-between">
      <span>{{ $t('dashboard.missingThresholds') }}</span>
      <v-btn
        color="warning"
        density="compact"
        to="/settings/thresholds"
        variant="text"
      >
        {{ $t('dashboard.configureThresholds') }}
      </v-btn>
    </div>

  </v-alert>

  <v-progress-linear v-if="loading" color="primary" indeterminate />

  <template v-else>
    <!-- Total Balances -->
    <div class="d-flex ga-4 mb-8 flex-wrap">
      <v-card
        v-for="b in balances"
        :key="b.currency"
        border
        class="flex-grow-1"
        flat
        min-width="250"
        rounded="lg"
      >
        <v-card-text class="pa-6">
          <div
            class="d-flex align-center ga-3 text-grey-darken-1 mb-2"
          >
            <v-icon :icon="getCurrencyIcon(b.currency)" size="20" />
            <span
              class="text-caption font-weight-bold text-uppercase"
            >
              {{ $t('dashboard.totalBalance', { currency: b.currency }) }}
            </span>
          </div>
          <div
            class="text-h4 font-weight-bold"
            :class="getAmountColor(b.amount)"
          >
            {{ b.formatted }}
          </div>
        </v-card-text>
      </v-card>
    </div>

    <!-- Top Wallets -->
    <div class="mb-8" data-testid="top-wallets-section">
      <h2 class="text-h6 font-weight-bold text-grey-darken-2 mb-4">
        {{ $t('dashboard.topPerformingWallets') }}
      </h2>
      <div v-if="topWallets.length > 0" class="d-flex ga-4 flex-wrap">
        <v-card
          v-for="w in topWallets"
          :key="w.id"
          border
          class="flex-grow-1"
          flat
          min-width="200"
          rounded="lg"
        >
          <v-card-text class="pa-5">
            <div
              class="text-body-1 font-weight-bold text-grey-darken-3 mb-1"
            >
              {{ w.name }}
            </div>
            <div
              class="text-h5 font-weight-bold"
              :class="getAmountColor(w.balance)"
            >
              {{ formatCurrency(w.balance, w.currency) }}
            </div>
          </v-card-text>
        </v-card>

        <v-card
          v-if="otherWallets.count > 0"
          border
          class="flex-grow-1"
          flat
          min-width="200"
          rounded="lg"
        >
          <v-card-text class="pa-5">
            <div
              class="text-body-1 font-weight-bold text-grey-darken-3 mb-1"
            >
              {{ $t('dashboard.otherWallets', { count: otherWallets.count }) }}
            </div>
            <div
              v-if="otherWallets.totalUSD !== 0"
              class="text-body-1 font-weight-bold"
              :class="getAmountColor(otherWallets.totalUSD)"
            >
              {{ formatCurrency(otherWallets.totalUSD, 'USD') }}
            </div>
            <div
              v-if="otherWallets.totalEUR !== 0"
              class="text-body-1 font-weight-bold"
              :class="getAmountColor(otherWallets.totalEUR)"
            >
              {{ formatCurrency(otherWallets.totalEUR, 'EUR') }}
            </div>
          </v-card-text>
        </v-card>
      </div>
      <v-card
        v-else
        border
        class="pa-8 text-grey-darken-1 text-center"
        flat
        rounded="lg"
      >
        {{ $t('dashboard.noWallets') }}
      </v-card>
    </div>

    <!-- Pending Approval (Admin/Manager only) -->
    <div
      v-if="authStore.isManagerOrAdmin && pendingTransactions.length > 0"
      class="mb-8"
      data-testid="pending-transactions-section"
    >
      <div class="d-flex align-center justify-space-between mb-4">
        <h2 class="text-h6 font-weight-bold text-grey-darken-2">
          {{ $t('dashboard.pendingApproval') }}
        </h2>
        <v-btn
          class="text-none font-weight-bold"
          color="primary"
          data-testid="view-all-pending-btn"
          density="compact"
          rounded="lg"
          to="/transactions?status=pending_approval"
          variant="text"
        >
          {{ $t('dashboard.viewAllPending') }}
        </v-btn>
      </div>
      <TransactionTable
        :is-admin="authStore.isAdmin"
        :is-manager-or-admin="authStore.isManagerOrAdmin"
        :items="pendingTransactions"
        :wallets="wallets"
        @refresh="invalidateDashboard"
        @reviewed="invalidateDashboard"
      />
    </div>

    <!-- Recent Transactions -->
    <div data-testid="recent-transactions-section">
      <h2 class="text-h6 font-weight-bold text-grey-darken-2 mb-4">
        {{ $t('dashboard.recentTransactions') }}
      </h2>
      <TransactionTable
        v-if="recentTransactions.length > 0"
        :is-admin="authStore.isAdmin"
        :is-manager-or-admin="authStore.isManagerOrAdmin"
        :items="recentTransactions"
        :wallets="wallets"
        @refresh="invalidateDashboard"
        @reviewed="invalidateDashboard"
      />
      <v-card
        v-else
        border
        class="pa-8 text-grey-darken-1 text-center"
        flat
        rounded="lg"
      >
        {{ $t('dashboard.noTransactions') }}
      </v-card>
    </div>
  </template>
</template>

<route lang="yaml">
meta:
    layout: App
</route>
