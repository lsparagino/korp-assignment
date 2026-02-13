<script lang="ts" setup>
  import type { Transaction, Wallet } from '@/types'
  import { Calendar } from 'lucide-vue-next'
  import { computed, onMounted, reactive, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import TransactionTable from '@/components/TransactionTable.vue'
  import { usePagination } from '@/composables/usePagination'
  import api from '@/plugins/api'
  import { useAuthStore } from '@/stores/auth'
  import { useCompanyStore } from '@/stores/company'

  const auth = useAuthStore()
  const companyStore = useCompanyStore()
  const route = useRoute()
  const router = useRouter()
  const transactions = ref<Transaction[]>([])

  const filterForm = reactive({
    date_from: '',
    date_to: '',
    type: 'All',
    amount_min: '',
    amount_max: '',
    reference: '',
    from_wallet_id: null as number | null,
    to_wallet_id: null as number | null,
  })

  const types = ['All', 'Debit', 'Credit']

  const dateFromMenu = ref(false)
  const dateToMenu = ref(false)
  const dateFromValue = ref<Date | null>(null)
  const dateToValue = ref<Date | null>(null)
  const advancedPanel = ref<number[]>([])
  const wallets = ref<Wallet[]>([])
  const walletOptions = computed(() => [
    { id: 'external', name: 'External' },
    ...wallets.value,
  ])

  async function fetchWallets () {
    try {
      const response = await api.get('/wallets')
      wallets.value = response.data.data
    } catch (error) {
      console.error('Error fetching wallets:', error)
    }
  }

  onMounted(fetchWallets)

  const activeAdvancedFiltersCount = computed(() => {
    let count = 0
    if (route.query.amount_min) {
      count++
    }
    if (route.query.amount_max) {
      count++
    }
    if (route.query.reference) {
      count++
    }
    if (route.query.from_wallet_id) {
      count++
    }
    if (route.query.to_wallet_id) {
      count++
    }
    return count
  })

  const activeFiltersCount = computed(() => {
    let count = activeAdvancedFiltersCount.value
    if (route.query.date_from) {
      count++
    }
    if (route.query.date_to) {
      count++
    }
    if (route.query.type) {
      count++
    }
    return count
  })

  // Sync date values from strings to Date objects when filterForm changes (e.g. on load)
  watch(
    () => filterForm.date_from,
    val => {
      if (val && !dateFromValue.value) {
        dateFromValue.value = new Date(val)
      } else if (!val) {
        dateFromValue.value = null
      }
    },
  )

  watch(
    () => filterForm.date_to,
    val => {
      if (val && !dateToValue.value) {
        dateToValue.value = new Date(val)
      } else if (!val) {
        dateToValue.value = null
      }
    },
  )

  function onDateSelected (type: 'from' | 'to', value: any) {
    if (!value) {
      return
    }

    const date = new Date(value)
    const formatted = date.toISOString().split('T')[0] as string

    if (type === 'from') {
      filterForm.date_from = formatted
      dateFromMenu.value = false
    } else {
      filterForm.date_to = formatted
      dateToMenu.value = false
    }
  }

  const { meta, handlePageChange, handlePerPageChange, _, processing }
    = usePagination(async params => {
      // Sync filterForm with URL query params
      filterForm.date_from = (route.query.date_from as string) || ''
      filterForm.date_to = (route.query.date_to as string) || ''
      const queryType = (route.query.type as string) || 'All'
      filterForm.type
        = queryType.charAt(0).toUpperCase()
          + queryType.slice(1).toLowerCase()

      filterForm.amount_min = (route.query.amount_min as string) || ''
      filterForm.amount_max = (route.query.amount_max as string) || ''
      filterForm.reference = (route.query.reference as string) || ''
      filterForm.from_wallet_id = route.query.from_wallet_id
        ? (route.query.from_wallet_id === 'external'
          ? ('external' as any)
          : Number(route.query.from_wallet_id))
        : null
      filterForm.to_wallet_id = route.query.to_wallet_id
        ? (route.query.to_wallet_id === 'external'
          ? ('external' as any)
          : Number(route.query.to_wallet_id))
        : null

      try {
        const response = await api.get('/transactions', {
          params: {
            ...params,
            date_from: route.query.date_from,
            date_to: route.query.date_to,
            type: route.query.type,
            amount_min: route.query.amount_min,
            amount_max: route.query.amount_max,
            reference: route.query.reference,
            from_wallet_id: route.query.from_wallet_id,
            to_wallet_id: route.query.to_wallet_id,
          },
        })

        // company.value = response.data.company // If company is returned in response

        transactions.value = response.data.data

        if (response.data.meta) {
          meta.value = response.data.meta
        }
      } catch (error) {
        console.error('Error fetching transactions:', error)
      }
    })

  function handleFilter () {
    const query = {
      ...route.query,
      page: '1',
      date_from: filterForm.date_from || undefined,
      date_to: filterForm.date_to || undefined,
      type:
        filterForm.type === 'All'
          ? undefined
          : filterForm.type.toLowerCase(),
      amount_min: filterForm.amount_min || undefined,
      amount_max: filterForm.amount_max || undefined,
      reference: filterForm.reference || undefined,
      from_wallet_id: filterForm.from_wallet_id || undefined,
      to_wallet_id: filterForm.to_wallet_id || undefined,
    }

    // Remove undefined keys
    for (const key of Object.keys(query))
      (query as any)[key] === undefined && delete (query as any)[key]

    router.push({ query })
  }

  function clearFilters () {
    const query = { ...route.query }
    delete query.date_from
    delete query.date_to
    delete query.type
    delete query.amount_min
    delete query.amount_max
    delete query.reference
    delete query.from_wallet_id
    delete query.to_wallet_id
    query.page = '1'

    router.push({ query })
  }
</script>

<template>
  <div class="mb-8">
    <h1 class="text-h5 font-weight-bold text-grey-darken-2">
      Transactions
      <span
        v-if="companyStore.currentCompany"
        class="text-grey-darken-1"
      >- {{ companyStore.currentCompany.name }}</span>
    </h1>
  </div>

  <!-- Filter Card -->
  <v-card border class="mb-6" flat rounded="lg">
    <v-card-text class="pa-6">
      <div class="d-flex align-center justify-space-between mb-4">
        <span
          class="text-subtitle-2 font-weight-bold text-grey-darken-3"
        >Filter Options</span>
        <v-chip
          v-if="activeFiltersCount > 0"
          color="primary"
          density="comfortable"
          size="x-small"
          variant="flat"
        >
          {{ activeFiltersCount }} active
        </v-chip>
      </div>
      <v-row>
        <v-col cols="12" md="4">
          <label
            class="text-caption font-weight-bold text-grey-darken-2 d-block mb-2"
          >
            Date From
          </label>
          <v-menu
            v-model="dateFromMenu"
            :close-on-content-click="false"
            location="bottom"
            min-width="auto"
            transition="scale-transition"
          >
            <template #activator="{ props }">
              <v-text-field
                v-model="filterForm.date_from"
                density="comfortable"
                hide-details
                placeholder="YYYY-MM-DD"
                readonly
                rounded="lg"
                v-bind="props"
                variant="outlined"
              >
                <template #append-inner>
                  <v-icon
                    class="cursor-pointer"
                    color="grey-darken-1"
                    :icon="Calendar"
                    size="18"
                  />
                </template>
              </v-text-field>
            </template>
            <v-date-picker
              v-model="dateFromValue"
              color="primary"
              hide-header
              @update:model-value="onDateSelected('from', $event)"
            />
          </v-menu>
        </v-col>

        <v-col cols="12" md="4">
          <label
            class="text-caption font-weight-bold text-grey-darken-2 d-block mb-2"
          >
            Date To
          </label>
          <v-menu
            v-model="dateToMenu"
            :close-on-content-click="false"
            location="bottom"
            min-width="auto"
            transition="scale-transition"
          >
            <template #activator="{ props }">
              <v-text-field
                v-model="filterForm.date_to"
                density="comfortable"
                hide-details
                placeholder="YYYY-MM-DD"
                readonly
                rounded="lg"
                v-bind="props"
                variant="outlined"
              >
                <template #append-inner>
                  <v-icon
                    class="cursor-pointer"
                    color="grey-darken-1"
                    :icon="Calendar"
                    size="18"
                  />
                </template>
              </v-text-field>
            </template>
            <v-date-picker
              v-model="dateToValue"
              color="primary"
              hide-header
              @update:model-value="onDateSelected('to', $event)"
            />
          </v-menu>
        </v-col>

        <v-col cols="12" md="4">
          <label
            class="text-caption font-weight-bold text-grey-darken-2 d-block mb-2"
          >
            Type
          </label>
          <v-select
            v-model="filterForm.type"
            density="comfortable"
            hide-details
            :items="types"
            rounded="lg"
            variant="outlined"
          />
        </v-col>
      </v-row>

      <v-expansion-panels v-model="advancedPanel" class="mt-4">
        <v-expansion-panel border elevation="0">
          <v-expansion-panel-title
            class="text-caption font-weight-bold text-primary min-h-0"
          >
            <v-icon class="mr-3" icon="mdi-filter-cog" />Advanced
            Filters
            <v-chip
              v-if="activeAdvancedFiltersCount > 0"
              class="ms-2"
              color="primary"
              density="comfortable"
              size="x-small"
              variant="flat"
            >
              {{ activeAdvancedFiltersCount }}
            </v-chip>
          </v-expansion-panel-title>
          <v-expansion-panel-text class="pa-0 mt-4">
            <v-row>
              <v-col cols="12" md="6">
                <label
                  class="text-caption font-weight-bold text-grey-darken-2 d-block mb-2"
                >
                  Amount Range
                </label>
                <div class="d-flex align-center gap-2">
                  <v-text-field
                    v-model="filterForm.amount_min"
                    density="comfortable"
                    hide-details
                    placeholder="Min"
                    rounded="lg"
                    type="number"
                    variant="outlined"
                  />
                  <span class="text-grey-darken-1 mx-2">-</span>
                  <v-text-field
                    v-model="filterForm.amount_max"
                    density="comfortable"
                    hide-details
                    placeholder="Max"
                    rounded="lg"
                    type="number"
                    variant="outlined"
                  />
                </div>
              </v-col>
              <v-col cols="12" md="6">
                <label
                  class="text-caption font-weight-bold text-grey-darken-2 d-block mb-2"
                >
                  Reference
                </label>
                <v-text-field
                  v-model="filterForm.reference"
                  density="comfortable"
                  hide-details
                  placeholder="Contains..."
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
            </v-row>
            <v-row class="mt-4">
              <v-col cols="12" md="6">
                <label
                  class="text-caption font-weight-bold text-grey-darken-2 d-block mb-2"
                >
                  From Wallet
                </label>
                <v-select
                  v-model="filterForm.from_wallet_id"
                  clearable
                  density="comfortable"
                  hide-details
                  item-title="name"
                  item-value="id"
                  :items="walletOptions"
                  placeholder="Select source wallet"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <label
                  class="text-caption font-weight-bold text-grey-darken-2 d-block mb-2"
                >
                  To Wallet
                </label>
                <v-select
                  v-model="filterForm.to_wallet_id"
                  clearable
                  density="comfortable"
                  hide-details
                  item-title="name"
                  item-value="id"
                  :items="walletOptions"
                  placeholder="Select destination wallet"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </v-expansion-panel-text>
        </v-expansion-panel>
      </v-expansion-panels>
    </v-card-text>
    <v-divider />
    <v-card-actions class="pa-4 bg-grey-lighten-5 justify-end">
      <v-btn
        class="text-none mr-2"
        color="grey-darken-1"
        rounded="lg"
        variant="outlined"
        @click="clearFilters"
      >
        Clear
      </v-btn>
      <v-btn
        class="text-none font-weight-bold px-6"
        color="primary"
        rounded="lg"
        variant="flat"
        @click="handleFilter"
      >
        Filter
      </v-btn>
    </v-card-actions>
  </v-card>

  <TransactionTable
    :is-admin="auth.isAdmin"
    :items="transactions"
    :loading="processing"
    :meta="meta"
    :show-pagination="true"
    title="Transactions List"
    :wallets="wallets"
    @update:page="handlePageChange"
    @update:per-page="handlePerPageChange"
  />
</template>

<route lang="yaml">
meta:
    layout: App
</route>
