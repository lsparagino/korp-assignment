<script lang="ts" setup>
  import type { PaginationMeta } from '@/composables/usePagination'
  import Pagination from '@/components/Pagination.vue'

  interface Props {
    items: any[]
    loading?: boolean
    wallets?: any[]
    showPagination?: boolean
    meta?: PaginationMeta
    title?: string
    isAdmin?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    loading: false,
    wallets: () => [],
    showPagination: false,
    title: 'Transactions',
    isAdmin: false,
  })

  defineEmits(['update:page', 'update:per-page'])

  function isAssigned (walletId: number | null) {
    if (!walletId) return false
    if (props.isAdmin) return true
    return props.wallets.some(w => w.id === walletId)
  }

  function formatAmount (item: any) {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: item.currency,
    }).format(item.amount)
  }

  function getAmountColor (item: any) {
    if (item.to_wallet_id === null) return 'text-red-darken-1'
    if (item.from_wallet_id === null) return 'text-green-darken-1'
    return item.type.toLowerCase() === 'debit'
      ? 'text-red-darken-1'
      : 'text-green-darken-1'
  }

  function formatDate (dateString: string) {
    if (!dateString) return ''
    const date = new Date(dateString)
    if (isNaN(date.getTime())) return 'Invalid Date'

    const pad = (n: number) => n.toString().padStart(2, '0')
    const m = pad(date.getMonth() + 1)
    const d = pad(date.getDate())
    const y = date.getFullYear().toString().slice(-2)
    const h = pad(date.getHours())
    const min = pad(date.getMinutes())

    return `${m}/${d}/${y} ${h}:${min}`
  }
</script>

<template>
  <v-card border flat :loading="loading" rounded="lg">
    <v-card-title class="pa-4 bg-grey-lighten-5 border-b">
      <span class="text-subtitle-1 font-weight-bold text-grey-darken-3">{{
        title
      }}</span>
    </v-card-title>

    <div class="overflow-x-auto">
      <v-table density="comfortable">
        <thead>
          <tr>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              Date
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
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              From Wallet
            </th>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              To Wallet
            </th>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              Reference
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td
              class="text-grey-darken-2 text-caption text-no-wrap"
            >
              {{ formatDate(item.created_at) }}
            </td>
            <td class="text-grey-darken-3 font-weight-bold">
              <v-chip
                class="text-uppercase font-weight-bold"
                :color="
                  item.type.toLowerCase() === 'debit'
                    ? 'red-lighten-4'
                    : 'green-lighten-4'
                "
                size="x-small"
                variant="flat"
              >
                <span
                  :class="
                    item.type.toLowerCase() === 'debit'
                      ? 'text-red-darken-3'
                      : 'text-green-darken-3'
                  "
                >
                  {{ item.type }}
                </span>
              </v-chip>
            </td>
            <td
              :class="[getAmountColor(item), 'font-weight-black']"
            >
              {{ formatAmount(item) }}
            </td>
            <td>
              <div class="d-flex align-center">
                <v-avatar
                  class="me-2"
                  :color="
                    isAssigned(item.from_wallet_id)
                      ? 'primary'
                      : 'grey-lighten-2'
                  "
                  rounded="sm"
                  size="20"
                >
                  <v-icon
                    color="white"
                    icon="mdi-wallet"
                    size="12"
                  />
                </v-avatar>
                <span
                  class="text-caption font-weight-medium"
                  :class="
                    isAssigned(item.from_wallet_id)
                      ? 'text-grey-darken-2'
                      : 'text-grey-lighten-1'
                  "
                >{{
                  item.from_wallet?.name || 'EXTERNAL'
                }}</span>
              </div>
            </td>
            <td>
              <div class="d-flex align-center">
                <v-avatar
                  class="me-2"
                  :color="
                    isAssigned(item.to_wallet_id)
                      ? 'primary'
                      : 'grey-lighten-2'
                  "
                  rounded="sm"
                  size="20"
                >
                  <v-icon
                    color="white"
                    icon="mdi-wallet"
                    size="12"
                  />
                </v-avatar>
                <span
                  class="text-caption font-weight-medium"
                  :class="
                    isAssigned(item.to_wallet_id)
                      ? 'text-grey-darken-2'
                      : 'text-grey-lighten-1'
                  "
                >{{
                  item.to_wallet?.name || 'EXTERNAL'
                }}</span>
              </div>
            </td>
            <td class="text-grey-darken-2 text-caption">
              {{ item.reference }}
            </td>
          </tr>
          <tr v-if="!loading && items.length === 0">
            <td
              class="text-grey-darken-1 py-8 text-center"
              colspan="6"
            >
              No transactions found.
            </td>
          </tr>
        </tbody>
      </v-table>
    </div>

    <div v-if="showPagination && meta" class="border-t">
      <Pagination
        :meta="meta"
        @update:page="$emit('update:page', $event)"
        @update:per-page="$emit('update:per-page', $event)"
      />
    </div>

    <slot name="footer" />
  </v-card>
</template>
