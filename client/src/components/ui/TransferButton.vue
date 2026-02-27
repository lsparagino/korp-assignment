<script lang="ts" setup>
  import { useQuery } from '@pinia/colada'
  import { computed } from 'vue'
  import { walletsListQuery } from '@/queries/wallets'

  withDefaults(defineProps<{
    block?: boolean
    size?: string
  }>(), {
    block: false,
    size: 'default',
  })

  const { data: walletsData } = useQuery(
    walletsListQuery,
    () => ({ page: 1, perPage: 1 }),
  )

  const hasWallets = computed(() => (walletsData.value?.data?.length ?? 0) > 0)
</script>

<template>
  <v-btn
    :block="block"
    class="text-none font-weight-bold"
    color="primary"
    data-testid="transfer-btn"
    :disabled="!hasWallets"
    prepend-icon="mdi-swap-horizontal"
    rounded="lg"
    :size="size"
    to="/transactions/create"
    variant="flat"
  >
    {{ $t('transfers.initiateTransfer') }}
  </v-btn>
</template>
