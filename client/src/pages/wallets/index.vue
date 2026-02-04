<script lang="ts" setup>
  import { Wallet } from 'lucide-vue-next'
  import { onMounted, ref } from 'vue'
  import api from '@/plugins/api'
  import { useAuthStore } from '@/stores/auth'

  const authStore = useAuthStore()

  const company = ref('')
  const wallets = ref<any[]>([])
  const processing = ref(true)

  async function fetchWallets () {
    try {
      const response = await api.get('/wallets')
      // company.value = response.data.company
      wallets.value = response.data.data.map((w: any) => ({
        ...w,
        balanceFormatted: new Intl.NumberFormat('en-US', {
          style: 'currency',
          currency: w.currency,
        }).format(w.balance),
        statusColor:
          w.status === 'active' ? 'green-lighten-4' : 'red-lighten-4',
        statusTextColor:
          w.status === 'active' ? 'green-darken-3' : 'red-darken-3',
      }))
    } catch (error) {
      console.error('Error fetching wallets:', error)
    } finally {
      processing.value = false
    }
  }

  async function toggleStatus (wallet: any) {
    try {
      await api.patch(`/wallets/${wallet.id}/toggle-freeze`)
      fetchWallets()
    } catch (error) {
      console.error('Error toggling status:', error)
    }
  }

  async function deleteWallet (wallet: any) {
    if (!confirm(`Are you sure you want to delete the wallet "${wallet.name}"?`)) return
    try {
      await api.delete(`/wallets/${wallet.id}`)
      fetchWallets()
    } catch (error: any) {
      if (error.response?.status === 403) {
        alert(error.response.data.message || 'You are not authorized to delete this wallet (it might not be empty).')
      } else {
        console.error('Error deleting wallet:', error)
      }
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
      v-if="authStore.user?.role === 'admin'"
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
          <th
            v-if="authStore.user?.role === 'admin'"
            class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-right"
          >
            Actions
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
                class="font-weight-bold text-capitalize"
                :class="`text-${wallet.statusTextColor}`"
              >{{ wallet.status }}</span>
            </v-chip>
          </td>
          <td v-if="authStore.user?.role === 'admin'" class="text-right">
            <div class="d-flex justify-end ga-2">
              <v-btn
                color="primary"
                density="comfortable"
                icon="mdi-pencil"
                size="small"
                :to="`/wallets/${wallet.id}/edit`"
                variant="text"
              />
              <v-btn
                :color="wallet.status === 'active' ? 'warning' : 'success'"
                density="comfortable"
                :icon="wallet.status === 'active' ? 'mdi-snowflake' : 'mdi-fire'"
                size="small"
                variant="text"
                @click="toggleStatus(wallet)"
              />
              <v-btn
                color="error"
                density="comfortable"
                icon="mdi-delete"
                size="small"
                variant="text"
                @click="deleteWallet(wallet)"
              />
            </div>
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
