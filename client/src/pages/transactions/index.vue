<script lang="ts" setup>
  import { Calendar, ChevronDown } from 'lucide-vue-next'
  import { onMounted, reactive, ref } from 'vue'
  import api from '@/plugins/api'

  const company = ref('')
  const transactions = ref<any[]>([])
  const processing = ref(true)

  const filterForm = reactive({
    date_from: '',
    date_to: '',
    type: 'All',
  })

  const types = ['All', 'Debit', 'Credit']

  async function fetchTransactions () {
    processing.value = true
    try {
      const response = await api.get('/transactions', { params: filterForm })
      company.value = response.data.company
      transactions.value = response.data.transactions.map((t: any) => ({
        ...t,
        amountFormatted: new Intl.NumberFormat('en-US', {
          style: 'currency',
          currency: t.currency,
        }).format(t.amount),
        amountColor:
          t.amount < 0 ? 'text-red-darken-1' : 'text-green-darken-1',
      }))
    } catch (error) {
      console.error('Error fetching transactions:', error)
    } finally {
      processing.value = false
    }
  }

  onMounted(fetchTransactions)
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
            placeholder=" / / "
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
            placeholder=" / / "
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
        @click="
          Object.assign(filterForm, {
            date_from: '',
            date_to: '',
            type: 'All',
          })
        "
      >
        Clear
      </v-btn>
      <v-btn
        class="text-none font-weight-bold px-6"
        color="primary"
        rounded="lg"
        variant="flat"
        @click="fetchTransactions"
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
          <td class="text-grey-darken-2">{{ item.date }}</td>
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
              >{{ item.wallet }}</span>
            </div>
          </td>
          <td class="text-grey-darken-3 font-weight-bold">
            {{ item.type }}
          </td>
          <td :class="[item.amountColor, 'font-weight-black']">
            {{ item.amountFormatted }}
          </td>
          <td class="text-grey-darken-2">{{ item.reference }}</td>
        </tr>
      </tbody>
    </v-table>

    <div
      class="pa-4 d-flex align-center justify-space-between bg-grey-lighten-5 border-t"
    >
      <span class="text-caption text-grey-darken-1">Showing {{ transactions.length }} of 100 transactions</span>
      <v-pagination
        active-color="primary"
        class="my-0"
        density="compact"
        :length="3"
        rounded="sm"
      />
    </div>
  </v-card>
</template>

<route lang="yaml">
meta:
    layout: App
</route>
