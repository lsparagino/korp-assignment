<script lang="ts" setup>
  import { watch } from 'vue'
  import { useRouter } from 'vue-router'
  import PageHeader from '@/components/layout/PageHeader.vue'
  import DataTable from '@/components/ui/DataTable.vue'
  import { useRefreshData } from '@/composables/useRefreshData'
  import { useUrlPagination } from '@/composables/useUrlPagination'
  import { useWalletList } from '@/queries/wallets'
  import { useAuthStore } from '@/stores/auth'
  import { getCurrencyColors, getStatusColors } from '@/utils/colors'
  import { formatCurrency } from '@/utils/formatters'

  const router = useRouter()
  const authStore = useAuthStore()
  const { page: urlPage, perPage: urlPerPage, handlePageChange, handlePerPageChange } = useUrlPagination()

  const { wallets, meta, isPending: processing, refetch, page, perPage } = useWalletList()

  watch(urlPage, val => {
    page.value = val
  }, { immediate: true })
  watch(urlPerPage, val => {
    perPage.value = val
  }, { immediate: true })

  const { refreshing, refresh } = useRefreshData(async () => {
    await refetch()
  })

  function navigateToWallet (id: number) {
    router.push(`/wallets/${id}`)
  }
</script>

<template>
  <PageHeader :title="$t('wallets.title')">
    <div class="d-flex ga-2 align-center">
      <v-btn
        v-if="authStore.isAdmin"
        class="text-none font-weight-bold"
        color="primary"
        data-testid="create-wallet-btn"
        prepend-icon="mdi-plus"
        rounded="lg"
        to="/wallets/create"
        variant="flat"
      >
        {{ $t('wallets.createWallet') }}
      </v-btn>
    </div>
  </PageHeader>

  <DataTable
    :loading="processing"
    :meta="meta"
    :refreshing="refreshing"
    :title="$t('wallets.title')"
    @refresh="refresh"
    @update:page="handlePageChange"
    @update:per-page="handlePerPageChange"
  >
    <template #columns>
      <th>{{ $t('wallets.tableHeaders.name') }}</th>
      <th>{{ $t('wallets.tableHeaders.balance') }}</th>
      <th>{{ $t('wallets.tableHeaders.availableBalance') }}</th>
      <th>{{ $t('wallets.tableHeaders.currency') }}</th>
      <th>{{ $t('wallets.tableHeaders.status') }}</th>
      <th class="text-right" style="width: 48px" />
    </template>

    <template #body>
      <tr
        v-for="w in wallets"
        :key="w.id"
        class="cursor-pointer"
        :data-testid="`wallet-row-${w.id}`"
        @click="navigateToWallet(w.id)"
      >
        <td class="font-weight-bold text-grey-darken-3">
          {{ w.name }}
        </td>
        <td class="font-weight-bold">
          {{ formatCurrency(w.balance, w.currency) }}
        </td>
        <td class="font-weight-bold">
          {{ formatCurrency(w.available_balance, w.currency) }}
          <v-icon
            v-if="w.available_balance !== w.balance"
            class="ms-1"
            color="amber-darken-2"
            icon="mdi-alert"
            size="14"
          />
        </td>
        <td>
          <v-chip class="font-weight-bold" :color="getCurrencyColors(w.currency).bg" size="small" variant="flat">
            <span class="font-weight-bold" :class="`text-${getCurrencyColors(w.currency).text}`">{{ w.currency }}</span>
          </v-chip>
        </td>
        <td>
          <v-chip
            class="font-weight-bold text-uppercase"
            :color="getStatusColors(w.status).bg"
            size="small"
            variant="flat"
          >
            <span class="font-weight-bold" :class="`text-${getStatusColors(w.status).text}`">{{ w.status }}</span>
          </v-chip>
        </td>
        <td class="text-right">
          <v-icon color="grey-darken-1" icon="mdi-chevron-right" size="20" />
        </td>
      </tr>
    </template>
  </DataTable>
</template>

<route lang="yaml">
meta:
    layout: App
</route>
