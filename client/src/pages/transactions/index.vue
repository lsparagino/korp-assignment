<script lang="ts" setup>
  import { Calendar, ChevronDown } from 'lucide-vue-next'
  import { reactive, ref } from 'vue'
  import api from '@/plugins/api'
  import { usePagination } from '@/composables/usePagination'
  import Pagination from '@/components/Pagination.vue'

  const company = ref('')
  const transactions = ref<any[]>([])
  const processing = ref(false)

  const filterForm = reactive({
    date_from: '',
    date_to: '',
    type: 'All',
  })

  const types = ['All', 'Debit', 'Credit']

  const {
    meta,
    handlePageChange,
    handlePerPageChange,
    refresh
  } = usePagination(async (params) => {
    processing.value = true
    try {
      const response = await api.get('/transactions', {
        params: {
          ...params,
          ...filterForm,
          type: filterForm.type === 'All' ? undefined : filterForm.type.toLowerCase()
        }
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
          t.type === 'debit' ? 'text-red-darken-1' : 'text-green-darken-1',
      }))

      if (response.data.meta) {
        meta.value = response.data.meta
      }
    } catch (error) {
      console.error('Error fetching transactions:', error)
    } finally {
      processing.value = false
    }
  })

  function handleFilter () {
    handlePageChange(1) // Reset to page 1 and trigger fetch via URL change
  }

  function clearFilters () {
    Object.assign(filterForm, {
      date_from: '',
      date_to: '',
      type: 'All',
    })
    handlePageChange(1)
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
      <v-row>
        <v-col cols="12" md="4">
          <label
            class="text-caption font-weight-bold text-grey-darken-2 mb-2 d-block"
          >
            Date From
          </label>
          <v-text-field
            v-model="filterForm.date_from"
            density="comfortable"
            hide-details
            placeholder="YYYY-MM-DD"
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
        </v-col>

        <v-col cols="12" md="4">
          <label
            class="text-caption font-weight-bold text-grey-darken-2 mb-2 d-block"
          >
            Date To
          </label>
          <v-text-field
            v-model="filterForm.date_to"
            density="comfortable"
            hide-details
            placeholder="YYYY-MM-DD"
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
            Wallet
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
                color="primary"
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
                class="text-caption text-grey-darken-2 font-weight-medium"
              >{{ item.to_wallet?.name || 'External' }}</span>
            </div>
          </td>
          <td class="text-grey-darken-3 font-weight-bold">
            <v-chip
              class="text-uppercase font-weight-bold"
              :color="item.type === 'debit' ? 'red-lighten-4' : 'green-lighten-4'"
              size="x-small"
              variant="flat"
            >
              <span :class="item.type === 'debit' ? 'text-red-darken-3' : 'text-green-darken-3'">
                {{ item.type }}
              </span>
            </v-chip>
          </td>
          <td :class="[item.amountColor, 'font-weight-black']">
            {{ item.amountFormatted }}
          </td>
          <td class="text-grey-darken-2 text-caption">{{ item.reference }}</td>
        </tr>
        <tr v-if="!processing && transactions.length === 0">
          <td colspan="5" class="text-center py-8 text-grey-darken-1">
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
