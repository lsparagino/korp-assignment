<script lang="ts" setup>
  import { Wallet } from 'lucide-vue-next'
  import { onMounted, ref } from 'vue'
  import api from '@/plugins/api'

  const company = ref('')
  const wallets = ref<any[]>([])
  const processing = ref(true)

  async function fetchWallets () {
    try {
      const response = await api.get('/wallets')
      company.value = response.data.company
      wallets.value = response.data.wallets.map((w: any) => ({
        ...w,
        balanceFormatted: new Intl.NumberFormat('en-US', {
          style: 'currency',
          currency: w.currency,
        }).format(w.balance),
        statusColor:
          w.status === 'Active' ? 'green-lighten-4' : 'red-lighten-4',
        statusTextColor:
          w.status === 'Active' ? 'green-darken-3' : 'red-darken-3',
      }))
    } catch (error) {
      console.error('Error fetching wallets:', error)
    } finally {
      processing.value = false
    }
  }

  onMounted(fetchWallets)
</script>

<template>
  <div class="d-flex align-center justify-space-between mb-8">
    <h1 class="text-h5 font-weight-bold text-grey-darken-2">
      Wallets - {{ company }}
    </h1>
    <v-btn
      class="text-none font-weight-bold"
      color="primary"
      prepend-icon="mdi-plus"
      rounded="lg"
      to="/wallets/create"
      variant="flat"
    >
      Create Wallet
    </v-btn>
  </div>

  <v-card border flat :loading="processing" rounded="lg">
    <v-table density="comfortable">
      <thead class="bg-grey-lighten-4">
        <tr>
          <th
            class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
          >
            Wallet Name
          </th>
          <th
            class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
          >
            Balance
          </th>
          <th
            class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
          >
            Currency
          </th>
          <th
            class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
          >
            Status
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="wallet in wallets" :key="wallet.id">
          <td class="font-weight-bold text-grey-darken-3">
            <div class="d-flex align-center">
              <v-avatar
                class="me-3"
                color="primary"
                rounded="sm"
                size="24"
              >
                <v-icon
                  color="white"
                  :icon="Wallet"
                  size="14"
                />
              </v-avatar>
              {{ wallet.name }}
            </div>
          </td>
          <td class="font-weight-black text-grey-darken-3">
            {{ wallet.balanceFormatted }}
          </td>
          <td>
            <v-chip
              class="font-weight-bold text-grey-darken-3"
              color="grey-lighten-3"
              size="small"
              variant="flat"
            >
              {{ wallet.currency }}
            </v-chip>
          </td>
          <td>
            <v-chip
              class="font-weight-bold"
              :color="wallet.statusColor"
              size="small"
              variant="flat"
            >
              <span
                class="font-weight-bold"
                :class="`text-${wallet.statusTextColor}`"
              >{{ wallet.status }}</span>
            </v-chip>
          </td>
        </tr>
      </tbody>
    </v-table>

    <div
      class="pa-4 d-flex align-center justify-space-between bg-grey-lighten-5 border-t"
    >
      <span class="text-caption text-grey-darken-1">Showing {{ wallets.length }} of 100</span>
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
