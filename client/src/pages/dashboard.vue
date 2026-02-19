<script lang="ts" setup>
  import type { Transaction, Wallet } from '@/types'
  import { ref, watch } from 'vue'
  import PageHeader from '@/components/PageHeader.vue'
  import TransactionTable from '@/components/TransactionTable.vue'
  import { fetchDashboard as apiFetchDashboard } from '@/api/dashboard'
  import { useAuthStore } from '@/stores/auth'
  import { useCompanyStore } from '@/stores/company'
  import { formatCurrency, getAmountColor } from '@/utils/formatters'

  const authStore = useAuthStore()
  const companyStore = useCompanyStore()

  interface BalanceSummary {
    currency: string
    amount: number
    formatted: string
  }

  const balances = ref<BalanceSummary[]>([])
  const topWallets = ref<Wallet[]>([])
  const otherWallets = ref<{ count: number, totalUSD: number, totalEUR: number }>(
    {
      count: 0,
      totalUSD: 0,
      totalEUR: 0,
    },
  )
  const recentTransactions = ref<Transaction[]>([])
  const loading = ref(true)

  async function fetchDashboard () {
    loading.value = true
    try {
      const response = await apiFetchDashboard()
      const data = response.data

      balances.value = data.balances.map(
        (b: { currency: string, amount: number }) => ({
          ...b,
          formatted: formatCurrency(b.amount, b.currency),
        }),
      )

      topWallets.value = data.top_wallets || []
      otherWallets.value = data.others || {
        count: 0,
        totalUSD: 0,
        totalEUR: 0,
      }
      recentTransactions.value = data.transactions || []
    } catch (error) {
      console.error('Failed to load dashboard:', error)
    } finally {
      loading.value = false
    }
  }

  watch(
    () => companyStore.currentCompany,
    company => {
      if (company) {
        fetchDashboard()
      }
    },
    { immediate: true },
  )

  function getCurrencyIcon (currency: string): string {
    return currency === 'EUR' ? 'mdi-currency-eur' : 'mdi-currency-usd'
  }
</script>

<template>
  <PageHeader title="Dashboard">
    <v-btn
      v-if="authStore.isAdmin"
      class="text-none font-weight-bold"
      color="primary"
      prepend-icon="mdi-plus"
      rounded="lg"
      to="/wallets/create"
      variant="flat"
    >
      Create Wallet
    </v-btn>
  </PageHeader>

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
              Total {{ b.currency }} Balance
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
    <div class="mb-8">
      <h2 class="text-h6 font-weight-bold text-grey-darken-2 mb-4">
        Top Performing Wallets
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
              Other Wallets ({{ otherWallets.count }})
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
        No wallets found. Create one to get started.
      </v-card>
    </div>

    <!-- Recent Transactions -->
    <div>
      <h2 class="text-h6 font-weight-bold text-grey-darken-2 mb-4">
        Recent Transactions
      </h2>
      <v-card
        v-if="recentTransactions.length > 0"
        border
        flat
        rounded="lg"
      >
        <TransactionTable
          :is-admin="authStore.isAdmin"
          :items="recentTransactions"
        />
      </v-card>
      <v-card
        v-else
        border
        class="pa-8 text-grey-darken-1 text-center"
        flat
        rounded="lg"
      >
        No transactions yet.
      </v-card>
    </div>
  </template>
</template>

<route lang="yaml">
meta:
    layout: App
</route>
