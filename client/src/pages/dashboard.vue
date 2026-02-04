<script lang="ts" setup>
  import { Wallet } from 'lucide-vue-next'
  import { onMounted, ref } from 'vue'
  import api from '@/plugins/api'

  const totalBalances = ref<any[]>([])
  const wallets = ref<any[]>([])
  const transactions = ref<any[]>([])
  const processing = ref(true)

  async function fetchDashboardData () {
    try {
      const response = await api.get('/dashboard')
      totalBalances.value = response.data.balances.map((b: any) => ({
        amount: new Intl.NumberFormat('en-US', {
          style: 'currency',
          currency: b.currency,
        }).format(b.amount),
        currency: b.currency,
      }))
      wallets.value = response.data.wallets.map((w: any) => ({
        ...w,
        balanceFormatted: new Intl.NumberFormat('en-US', {
          style: 'currency',
          currency: w.currency,
        }).format(w.balance),
        color: w.currency === 'USD' ? 'primary' : 'blue-darken-3',
      }))
      transactions.value = response.data.transactions.map((t: any) => ({
        ...t,
        amountFormatted: new Intl.NumberFormat('en-US', {
          style: 'currency',
          currency: t.currency,
        }).format(t.amount),
        amountColor: t.type === 'Debit' ? 'red-darken-1' : 'green-darken-1',
      }))
    } catch (error) {
      console.error('Error fetching dashboard data:', error)
    } finally {
      processing.value = false
    }
  }

  onMounted(fetchDashboardData)
</script>

<template>
  <div class="mb-8">
    <h1 class="text-h5 font-weight-bold text-grey-darken-2">
      Dashboard – Acme Corp
    </h1>
  </div>

  <!-- Total Balance Card -->
  <v-card
    border
    class="mb-6 pa-6"
    flat
    :loading="processing"
    rounded="lg"
  >
    <div class="text-subtitle-1 font-weight-bold text-grey-darken-3 mb-6">
      Total Balance
    </div>
    <div
      v-for="balance in totalBalances"
      :key="balance.currency"
      class="mb-2"
    >
      <span class="text-h4 font-weight-black mr-2">{{
        balance.amount
      }}</span>
      <span
        class="text-subtitle-1 font-weight-medium text-grey-darken-1"
      >{{ balance.currency }}</span>
    </div>
  </v-card>

  <!-- Wallets Grid -->
  <v-row class="mb-6">
    <v-col v-for="wallet in wallets" :key="wallet.name" cols="12" md="4">
      <v-card border class="pa-4" flat rounded="lg">
        <div class="d-flex align-center mb-6">
          <v-avatar
            class="me-3"
            :color="wallet.color"
            rounded="lg"
            size="32"
          >
            <v-icon color="white" :icon="Wallet" size="18" />
          </v-avatar>
          <span class="font-weight-bold text-grey-darken-3">{{
            wallet.name
          }}</span>
        </div>
        <div>
          <span class="text-h5 font-weight-black mr-2">{{
            wallet.balanceFormatted
          }}</span>
          <span
            class="text-caption font-weight-bold text-grey-darken-1 text-uppercase"
          >{{ wallet.currency }}</span>
        </div>
      </v-card>
    </v-col>
  </v-row>

  <!-- Recent Transactions -->
  <v-card border flat :loading="processing" rounded="lg">
    <div class="pa-4 border-b">
      <div class="text-subtitle-1 font-weight-bold text-grey-darken-3">
        Recent Transactions
      </div>
    </div>

    <v-table density="comfortable">
      <thead class="bg-grey-lighten-4">
        <tr>
          <th
            class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
          >
            Date
          </th>
          <th
            class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
          >
            From/To
          </th>
          <th
            class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
          >
            Type
          </th>
          <th
            class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
          >
            Amount
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in transactions" :key="item.date + item.to">
          <td class="text-grey-darken-2">{{ item.date }}</td>
          <td>
            <div class="d-flex align-center">
              <span
                class="text-caption text-grey-darken-2 font-weight-medium"
              >{{ item.to }}</span>
            </div>
          </td>
          <td class="text-grey-darken-2">{{ item.type }}</td>
          <td :class="`text-${item.amountColor} font-weight-bold`">
            {{ item.amountFormatted }}
          </td>
        </tr>
      </tbody>
    </v-table>

    <div
      class="pa-4 d-flex align-center justify-space-between bg-grey-lighten-4 border-t"
    >
      <span class="text-caption text-grey-darken-1">Showing {{ transactions.length }} of 100</span>
      <v-pagination
        active-color="primary"
        class="my-0"
        density="compact"
        :length="3"
      />
    </div>
  </v-card>
</template>

<route lang="yaml">
meta:
    layout: App
</route>
