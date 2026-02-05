<script lang="ts" setup>
  import { Wallet } from 'lucide-vue-next'
  import { onMounted, ref } from 'vue'
  import api from '@/plugins/api'

  const totalBalances = ref<any[]>([])
  const topWallets = ref<any[]>([])
  const otherWallets = ref<any[]>([])
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

      topWallets.value = response.data.top_wallets.map((w: any) => ({
        ...w,
        balanceFormatted: new Intl.NumberFormat('en-US', {
          style: 'currency',
          currency: w.currency,
        }).format(w.balance),
        color: w.currency === 'USD' ? 'primary' : 'blue-darken-3',
      }))

      otherWallets.value = response.data.others.map((o: any) => ({
        ...o,
        amountFormatted: new Intl.NumberFormat('en-US', {
          style: 'currency',
          currency: o.currency,
        }).format(o.amount),
      }))

      transactions.value = response.data.transactions.map((t: any) => ({
        ...t,
        amountFormatted: new Intl.NumberFormat('en-US', {
          style: 'currency',
          currency: t.currency,
        }).format(t.amount),
        amountColor: t.type.toLowerCase() === 'debit' ? 'red-darken-1' : 'green-darken-1',
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

  <v-row class="mb-6">
    <v-col v-for="wallet in topWallets" :key="wallet.name" cols="12" md="4">
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

    <!-- Other Wallets Badge -->
    <v-col v-if="otherWallets.length > 0" cols="12" md="4">
      <v-card border class="pa-4 bg-grey-lighten-4" flat rounded="lg">
        <div class="d-flex align-center mb-4">
          <v-avatar
            class="me-3"
            color="grey-darken-1"
            rounded="lg"
            size="32"
          >
            <v-icon color="white" icon="mdi-plus" size="18" />
          </v-avatar>
          <span class="font-weight-bold text-grey-darken-3">Other Wallets</span>
        </div>
        <div v-for="other in otherWallets" :key="other.currency" class="mb-1">
          <span class="text-subtitle-1 font-weight-black mr-2">{{
            other.amountFormatted
          }}</span>
          <span
            class="text-caption font-weight-bold text-grey-darken-1 text-uppercase"
          >{{ other.currency }}</span>
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
        <tr v-for="item in transactions" :key="item.id">
          <td class="text-grey-darken-2">{{ item.date }}</td>
          <td>
            <div class="d-flex flex-column">
              <span class="text-caption font-weight-bold text-grey-darken-3">
                {{ item.from }} &rarr; {{ item.to }}
              </span>
            </div>
          </td>
          <td class="text-grey-darken-2">
            <v-chip class="text-uppercase" density="comfortable" size="x-small" variant="flat">
              {{ item.type }}
            </v-chip>
          </td>
          <td :class="`text-${item.amountColor} font-weight-bold`">
            {{ item.amountFormatted }}
          </td>
        </tr>
        <tr v-if="transactions.length === 0 && !processing">
          <td class="text-center py-4 text-grey" colspan="4">No recent transactions.</td>
        </tr>
      </tbody>
    </v-table>

    <div
      v-if="transactions.length > 0"
      class="pa-4 d-flex align-center justify-end bg-grey-lighten-4 border-t"
    >
      <v-btn
        class="text-none"
        color="primary"
        size="small"
        to="/transactions"
        variant="text"
      >
        View All Transactions
      </v-btn>
    </div>
  </v-card>
</template>

<route lang="yaml">
meta:
    layout: App
</route>
