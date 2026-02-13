<script lang="ts" setup>
  import type { Wallet } from '@/types'
  import { ref } from 'vue'
  import ConfirmDialog from '@/components/ConfirmDialog.vue'
  import PageHeader from '@/components/PageHeader.vue'
  import Pagination from '@/components/Pagination.vue'
  import { useConfirmDialog } from '@/composables/useConfirmDialog'
  import { usePagination } from '@/composables/usePagination'
  import api from '@/plugins/api'
  import { useAuthStore } from '@/stores/auth'
  import { getCurrencyColors, getStatusColors } from '@/utils/colors'
  import { formatCurrency, getAmountColor } from '@/utils/formatters'

  const authStore = useAuthStore()
  const wallets = ref<Wallet[]>([])
  const snackbar = ref({ show: false, text: '', color: 'error' })
  const { confirmDialog, openConfirmDialog } = useConfirmDialog()

  const { meta, processing, handlePageChange, handlePerPageChange }
    = usePagination(
      async params => {
        const response = await api.get('/wallets', { params })
        wallets.value = response.data.data
        meta.value = {
          current_page: response.data.meta.current_page,
          last_page: response.data.meta.last_page,
          per_page: response.data.meta.per_page,
          total: response.data.meta.total,
          from: response.data.meta.from,
          to: response.data.meta.to,
        }
      },
      { defaultPerPage: 10 },
    )

  async function toggleFreeze (wallet: Wallet) {
    const isFreezing = wallet.status === 'active'
    openConfirmDialog({
      title: isFreezing ? 'Freeze Wallet' : 'Unfreeze Wallet',
      message: `Are you sure you want to ${isFreezing ? 'freeze' : 'unfreeze'} the wallet "${wallet.name}"?`,
      requiresPin: false,
      onConfirm: async () => {
        try {
          await api.patch(`/wallets/${wallet.id}/toggle-freeze`)
          const response = await api.get('/wallets', {
            params: {
              page: meta.value.current_page,
              per_page: meta.value.per_page,
            },
          })
          wallets.value = response.data.data
        } catch (error) {
          console.error('Error toggling wallet status:', error)
        }
      },
    })
  }

  async function deleteWallet (wallet: Wallet) {
    openConfirmDialog({
      title: 'Delete Wallet',
      message: `Warning: You are about to permanently delete the wallet "${wallet.name}". This action cannot be undone.`,
      requiresPin: true,
      onConfirm: async () => {
        try {
          await api.delete(`/wallets/${wallet.id}`)
          const response = await api.get('/wallets', {
            params: {
              page: meta.value.current_page,
              per_page: meta.value.per_page,
            },
          })
          wallets.value = response.data.data
        } catch (error: unknown) {
          const err = error as {
            response?: { status?: number, data?: { message?: string } }
          }
          if (err.response?.status === 403) {
            snackbar.value = {
              show: true,
              text:
                err.response.data?.message
                || 'You are not authorized to delete this wallet.',
              color: 'error',
            }
          } else {
            console.error('Error deleting wallet:', error)
          }
        }
      },
    })
  }
</script>

<template>
  <PageHeader title="Wallets">
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

  <v-card border flat :loading="processing" rounded="lg">
    <div class="overflow-x-auto">
      <v-table density="comfortable">
        <thead class="bg-grey-lighten-4">
          <tr>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              Name
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
              v-if="authStore.isAdmin"
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-right"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="w in wallets" :key="w.id">
            <td class="font-weight-bold text-grey-darken-3">
              {{ w.name }}
            </td>
            <td
              class="font-weight-bold"
              :class="getAmountColor(w.balance)"
            >
              {{ formatCurrency(w.balance, w.currency) }}
            </td>
            <td>
              <v-chip
                class="font-weight-bold"
                :color="getCurrencyColors(w.currency).bg"
                size="small"
                variant="flat"
              >
                <span
                  class="font-weight-bold"
                  :class="`text-${getCurrencyColors(w.currency).text}`"
                >{{ w.currency }}</span>
              </v-chip>
            </td>
            <td>
              <v-chip
                class="font-weight-bold text-uppercase"
                :color="getStatusColors(w.status).bg"
                size="small"
                variant="flat"
              >
                <span
                  class="font-weight-bold"
                  :class="`text-${getStatusColors(w.status).text}`"
                >{{ w.status }}</span>
              </v-chip>
            </td>
            <td v-if="authStore.isAdmin" class="text-right">
              <div class="d-flex ga-2 justify-end">
                <v-btn
                  color="primary"
                  density="comfortable"
                  icon="mdi-pencil"
                  size="small"
                  :to="`/wallets/${w.id}/edit`"
                  variant="text"
                />
                <v-btn
                  :color="
                    w.status === 'active'
                      ? 'warning'
                      : 'success'
                  "
                  density="comfortable"
                  :icon="
                    w.status === 'active'
                      ? 'mdi-snowflake'
                      : 'mdi-fire'
                  "
                  size="small"
                  variant="text"
                  @click="toggleFreeze(w)"
                />
                <v-btn
                  color="error"
                  density="comfortable"
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  @click="deleteWallet(w)"
                />
              </div>
            </td>
          </tr>
        </tbody>
      </v-table>
    </div>

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

  <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="5000">
    {{ snackbar.text }}
  </v-snackbar>
</template>

<route lang="yaml">
meta:
    layout: App
</route>
