<script lang="ts" setup>
  import { Wallet } from 'lucide-vue-next'
  import { ref } from 'vue'
  import ConfirmDialog from '@/components/ConfirmDialog.vue'
  import Pagination from '@/components/Pagination.vue'
  import { usePagination } from '@/composables/usePagination'
  import api from '@/plugins/api'
  import { useAuthStore } from '@/stores/auth'

  const authStore = useAuthStore()

  const company = ref('')
  const wallets = ref<any[]>([])

  const {
    meta,
    handlePageChange,
    handlePerPageChange,
    refresh,
  } = usePagination(async params => {
    try {
      const response = await api.get('/wallets', { params })

      // company.value = response.data.company // If company is returned in response

      wallets.value = response.data.data.map((w: any) => {
        const currencyColors: Record<string, { bg: string, text: string }> = {
          USD: { bg: 'blue-lighten-4', text: 'blue-darken-3' },
          EUR: { bg: 'orange-lighten-4', text: 'orange-darken-3' },
          GBP: { bg: 'indigo-lighten-4', text: 'indigo-darken-3' },
        }
        const colors = currencyColors[w.currency] || { bg: 'grey-lighten-3', text: 'grey-darken-3' }

        return {
          ...w,
          balanceFormatted: new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: w.currency,
          }).format(w.balance),
          balanceColor: w.balance > 0 ? 'text-green-darken-1' : (w.balance < 0 ? 'text-red-darken-1' : 'text-grey-darken-3'),
          statusColor:
            w.status === 'active' ? 'green-lighten-4' : 'red-lighten-4',
          statusTextColor:
            w.status === 'active' ? 'green-darken-3' : 'red-darken-3',
          currencyColor: colors.bg,
          currencyTextColor: colors.text,
        }
      })

      if (response.data.meta) {
        meta.value = response.data.meta
      }
    } catch (error) {
      console.error('Error fetching wallets:', error)
    }
  })

  // Dialog state
  const confirmDialog = ref({
    show: false,
    title: '',
    message: '',
    requiresPin: false,
    onConfirm: () => {},
  })

  function toggleStatus (wallet: any) {
    const isFreezing = wallet.status === 'active'
    confirmDialog.value = {
      show: true,
      title: isFreezing ? 'Freeze Wallet' : 'Unfreeze Wallet',
      message: `Are you sure you want to ${isFreezing ? 'freeze' : 'unfreeze'} the wallet "${wallet.name}"?`,
      requiresPin: false,
      onConfirm: async () => {
        try {
          await api.patch(`/wallets/${wallet.id}/toggle-freeze`)
          refresh()
        } catch (error) {
          console.error('Error toggling status:', error)
        }
      },
    }
  }

  function deleteWallet (wallet: any) {
    confirmDialog.value = {
      show: true,
      title: 'Delete Wallet',
      message: `Warning: You are about to permanently delete the wallet "${wallet.name}". This action cannot be undone. Only empty wallets can be deleted.`,
      requiresPin: true,
      onConfirm: async () => {
        try {
          await api.delete(`/wallets/${wallet.id}`)
          refresh()
        } catch (error: any) {
          if (error.response?.status === 403) {
            alert(error.response.data.message || 'You are not authorized to delete this wallet (it might not be empty).')
          } else {
            console.error('Error deleting wallet:', error)
          }
        }
      },
    }
  }
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

  <v-card border flat rounded="lg">
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
          <td class="font-weight-black" :class="wallet.balanceColor">
            {{ wallet.balanceFormatted }}
          </td>
          <td>
            <v-chip
              class="font-weight-bold"
              :class="`text-${wallet.currencyTextColor}`"
              :color="wallet.currencyColor"
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
                v-if="wallet.can_delete"
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

    <div class="border-t">
      <Pagination
        :meta="meta"
        @update:page="handlePageChange"
        @update:per-page="handlePerPageChange"
      />
    </div>
  </v-card>

  <ConfirmDialog
    v-model="confirmDialog.show"
    :message="confirmDialog.message"
    :requires-pin="confirmDialog.requiresPin"
    :title="confirmDialog.title"
    @confirm="confirmDialog.onConfirm"
  />
</template>

<route lang="yaml">
meta:
    layout: App
</route>
