<script lang="ts" setup>
  import { Wallet } from 'lucide-vue-next'
  import { computed, onMounted, ref } from 'vue'
  import TransactionTable from '@/components/TransactionTable.vue'
  import api from '@/plugins/api'
  import { useAuthStore } from '@/stores/auth'

  const auth = useAuthStore()
  const isAdmin = computed(() => auth.user?.role === 'admin')
  const totalBalances = ref<any[]>([])
  const topWallets = ref<any[]>([])
  const otherWallets = ref<any[]>([])
  const transactions = ref<any[]>([])
  const processing = ref(true)

  async function fetchDashboardData () {
    try {
      const response = await api.get('/dashboard')
      totalBalances.value = response.data.balances.map((b: any) => ({
        amountRaw: b.amount,
        amount: new Intl.NumberFormat('en-US', {
          style: 'currency',
          currency: b.currency,
        }).format(b.amount),
        currency: b.currency,
      }))

      topWallets.value = response.data.top_wallets.map((w: any) => ({
        ...w,
        balanceRaw: w.balance,
        balanceFormatted: new Intl.NumberFormat('en-US', {
          style: 'currency',
          currency: w.currency,
        }).format(w.balance),
        color: w.currency === 'USD' ? 'primary' : 'blue-darken-3',
      }))

      otherWallets.value = response.data.others.map((o: any) => ({
        ...o,
        amountRaw: o.amount,
        amountFormatted: new Intl.NumberFormat('en-US', {
          style: 'currency',
          currency: o.currency,
        }).format(o.amount),
      }))

      transactions.value = response.data.transactions
    } catch (error) {
      console.error('Error fetching dashboard data:', error)
    } finally {
      processing.value = false
    }
  }

  function getAmountColor (amount: number) {
    if (amount > 0) return 'text-green-darken-1'
    if (amount < 0) return 'text-red-darken-1'
    return 'text-grey-darken-1'
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
      <span
        class="text-h4 font-weight-black mr-2"
        :class="getAmountColor(balance.amountRaw)"
      >{{
        balance.amount
      }}</span>
      <span
        class="text-subtitle-1 font-weight-medium text-grey-darken-1"
      >{{ balance.currency }}</span>
    </div>
  </v-card>

  <v-row class="mb-6">
    <!-- Empty State -->
    <v-col v-if="!processing && topWallets.length === 0" cols="12">
      <v-card
        border
        class="pa-8 d-flex flex-column align-center justify-center text-center"
        flat
        rounded="lg"
      >
        <v-avatar color="grey-lighten-4" class="mb-4" size="64">
          <v-icon color="grey-darken-1" :icon="Wallet" size="32" />
        </v-avatar>
        <div class="text-h6 font-weight-bold text-grey-darken-3 mb-2">No wallets found</div>
        <p class="text-body-2 text-grey-darken-1 mb-6">
          You don't have any wallets assigned yet.
        </p>
        <v-btn
          v-if="isAdmin"
          class="text-none font-weight-bold"
          color="primary"
          prepend-icon="mdi-plus"
          to="/wallets/create"
          variant="flat"
        >
          Create Wallet
        </v-btn>
      </v-card>
    </v-col>

    <!-- Wallet Cards -->
    <v-col
      v-for="wallet in topWallets"
      :key="wallet.name"
      cols="12"
      md="3"
      sm="6"
    >
      <v-card border class="pa-4 h-100" flat rounded="lg">
        <div class="d-flex align-center mb-6">
          <v-avatar
            class="me-3"
            :color="wallet.color"
            rounded="lg"
            size="32"
          >
            <v-icon color="white" :icon="Wallet" size="18" />
          </v-avatar>
          <span class="font-weight-bold text-grey-darken-3 text-truncate">{{
            wallet.name
          }}</span>
        </div>
        <div>
          <span
            class="text-h5 font-weight-black mr-2"
            :class="getAmountColor(wallet.balanceRaw)"
          >{{
            wallet.balanceFormatted
          }}</span>
          <span
            class="text-caption font-weight-bold text-grey-darken-1 text-uppercase"
          >{{ wallet.currency }}</span>
        </div>
      </v-card>
    </v-col>

    <!-- Other Wallets Badge -->
    <v-col
      v-if="otherWallets.length > 0"
      cols="12"
      md="3"
      sm="6"
    >
      <v-card border class="pa-4 bg-grey-lighten-4 h-100" flat rounded="lg">
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
          <span
            class="text-subtitle-1 font-weight-black mr-2"
            :class="getAmountColor(other.amountRaw)"
          >{{
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
  <TransactionTable
    :is-admin="isAdmin"
    :items="transactions"
    :loading="processing"
    title="Recent Transactions"
  >
    <template #footer>
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
    </template>
  </TransactionTable>
</template>

<route lang="yaml">
meta:
    layout: App
</route>
