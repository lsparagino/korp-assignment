<script lang="ts" setup>
  import { Calendar, ChevronDown } from 'lucide-vue-next'
  import { computed, reactive, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import Pagination from '@/components/Pagination.vue'
  import { usePagination } from '@/composables/usePagination'
  import api from '@/plugins/api'

  const route = useRoute()
  const router = useRouter()
  const company = ref('')
  const transactions = ref<any[]>([])
  const processing = ref(false)

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
  const dateFromValue = ref<any>(null)
  const dateToValue = ref<any>(null)
  const advancedPanel = ref<number[]>([])
  const wallets = ref<any[]>([])

  async function fetchWallets () {
    try {
      const response = await api.get('/wallets')
      wallets.value = response.data.data
    } catch (error) {
      console.error('Error fetching wallets:', error)
    }
  }

  fetchWallets()

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

  const { meta, handlePageChange, handlePerPageChange, refresh } = usePagination(
    async params => {
      processing.value = true

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
        ? Number(route.query.from_wallet_id)
        : null
      filterForm.to_wallet_id = route.query.to_wallet_id
        ? Number(route.query.to_wallet_id)
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

        transactions.value = response.data.data.map((t: any) => ({
          ...t,
          dateFormatted: new Intl.DateTimeFormat('en-US', {
            dateStyle: 'medium',
            timeStyle: 'short',
          }).format(new Date(t.created_at)),
          amountFormatted: new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: t.currency,
          }).format(t.amount),
          amountColor:
            t.type === 'debit'
              ? 'text-red-darken-1'
              : 'text-green-darken-1',
        }))

        if (response.data.meta) {
          meta.value = response.data.meta
        }
      } catch (error) {
        console.error('Error fetching transactions:', error)
      } finally {
        processing.value = false
      }
    },
  )

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
    for (const key of Object.keys(query)) (query as any)[key] === undefined && delete (query as any)[key]

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

  function isAssigned (walletId: number | null) {
    if (!walletId) return false
    return wallets.value.some(w => w.id === walletId)
  }
</script>

<template>
  <div class="mb-8">
    <h1 class="text-h5 font-weight-bold text-grey-darken-2">
      Transactions - {{ company }}
    </h1>
  </div>

  <!-- Filter Card -->
  <v-card border class="mb-6" flat rounded="lg">
    <v-card-text class="pa-6">
      <div class="d-flex align-center justify-space-between mb-4">
        <span class="text-subtitle-2 font-weight-bold text-grey-darken-3">Filter Options</span>
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
            class="text-caption font-weight-bold text-grey-darken-2 mb-2 d-block"
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
                v-bind="props"
                density="comfortable"
                hide-details
                placeholder="YYYY-MM-DD"
                readonly
                rounded="lg"
                variant="outlined"
              >
                <template #append-inner>
                  <v-icon
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
            class="text-caption font-weight-bold text-grey-darken-2 mb-2 d-block"
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
                v-bind="props"
                density="comfortable"
                hide-details
                placeholder="YYYY-MM-DD"
                readonly
                rounded="lg"
                variant="outlined"
              >
                <template #append-inner>
                  <v-icon
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
            class="text-caption font-weight-bold text-grey-darken-2 mb-2 d-block"
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
          >
            <template #append-inner>
              <v-icon :icon="ChevronDown" size="18" />
            </template>
          </v-select>
        </v-col>
      </v-row>

      <v-expansion-panels v-model="advancedPanel" class="mt-4">
        <v-expansion-panel border elevation="0">
          <v-expansion-panel-title
            class="text-caption font-weight-bold min-h-0 text-primary"
          >
            <v-icon class="mr-3" icon="mdi-filter-cog" />Advanced Filters
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
                  class="text-caption font-weight-bold text-grey-darken-2 mb-2 d-block"
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
                  class="text-caption font-weight-bold text-grey-darken-2 mb-2 d-block"
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
                  class="text-caption font-weight-bold text-grey-darken-2 mb-2 d-block"
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
                  :items="wallets"
                  placeholder="Select source wallet"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <label
                  class="text-caption font-weight-bold text-grey-darken-2 mb-2 d-block"
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
                  :items="wallets"
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

  <!-- Transactions List Card -->
  <v-card border flat :loading="processing" rounded="lg">
    <v-card-title class="pa-4 bg-grey-lighten-5 border-b">
      <span class="text-subtitle-1 font-weight-bold text-grey-darken-3">Transactions List</span>
    </v-card-title>

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
            Reference
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in transactions" :key="item.id">
          <td class="text-grey-darken-2">{{ item.dateFormatted }}</td>
          <td>
            <div class="d-flex align-center">
              <v-avatar
                class="me-2"
                :color="isAssigned(item.from_wallet_id) ? 'primary' : 'grey-lighten-2'"
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
                :class="isAssigned(item.from_wallet_id) ? 'text-grey-darken-2' : 'text-grey-lighten-1'"
              >{{ item.from_wallet?.name || 'External' }}</span>
            </div>
          </td>
          <td>
            <div class="d-flex align-center">
              <v-avatar
                class="me-2"
                :color="isAssigned(item.to_wallet_id) ? 'primary' : 'grey-lighten-2'"
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
                :class="isAssigned(item.to_wallet_id) ? 'text-grey-darken-2' : 'text-grey-lighten-1'"
              >{{ item.to_wallet?.name || 'External' }}</span>
            </div>
          </td>
          <td class="text-grey-darken-3 font-weight-bold">
            <v-chip
              class="text-uppercase font-weight-bold"
              :color="
                item.type === 'debit'
                  ? 'red-lighten-4'
                  : 'green-lighten-4'
              "
              size="x-small"
              variant="flat"
            >
              <span
                :class="
                  item.type === 'debit'
                    ? 'text-red-darken-3'
                    : 'text-green-darken-3'
                "
              >
                {{ item.type }}
              </span>
            </v-chip>
          </td>
          <td :class="[item.amountColor, 'font-weight-black']">
            {{ item.amountFormatted }}
          </td>
          <td class="text-grey-darken-2 text-caption">
            {{ item.reference }}
          </td>
        </tr>
        <tr v-if="!processing && transactions.length === 0">
          <td class="py-8 text-grey-darken-1 text-center" colspan="6">
            No transactions found.
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
</template>

<route lang="yaml">
meta:
    layout: App
</route>
