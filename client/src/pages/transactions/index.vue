<script lang="ts" setup>
  import TransactionTable from '@/components/features/TransactionTable.vue'
  import PageHeader from '@/components/layout/PageHeader.vue'
  import { useTransactionFilters } from '@/composables/useTransactionFilters'

  const {
    filterForm,
    types,
    dateFromMenu,
    dateToMenu,
    dateFromValue,
    dateToValue,
    advancedPanel,
    wallets,
    walletOptions,
    transactions,
    meta,
    processing,
    activeAdvancedFiltersCount,
    activeFiltersCount,
    onDateSelected,
    handlePageChange,
    handlePerPageChange,
    handleFilter,
    clearFilters,
  } = useTransactionFilters()
</script>

<template>
  <PageHeader :title="$t('transactions.title')" />

  <!-- Filters -->
  <v-card border class="mb-6" flat rounded="lg">
    <v-card-title class="pa-4 bg-grey-lighten-5 d-flex align-center justify-space-between border-b">
      <span class="text-subtitle-1 font-weight-bold text-grey-darken-3">{{ $t('transactions.filterOptions') }}</span>
      <v-chip
        v-if="activeFiltersCount > 0"
        class="font-weight-bold"
        color="primary"
        size="small"
        variant="flat"
      >
        {{ $t('transactions.activeFilters', { count: activeFiltersCount }) }}
      </v-chip>
    </v-card-title>
    <v-card-text class="pa-4">
      <v-row align="center" dense>
        <v-col cols="12" md="3" sm="6">
          <v-menu
            v-model="dateFromMenu"
            :close-on-content-click="false"
            offset-y
            transition="scale-transition"
          >
            <template #activator="{ props }">
              <v-text-field
                v-model="filterForm.date_from"
                clearable
                color="primary"
                density="comfortable"
                hide-details
                :label="$t('transactions.dateFrom')"
                prepend-inner-icon="mdi-calendar"
                readonly
                variant="outlined"
                v-bind="props"
                @click:clear="filterForm.date_from = ''"
              />
            </template>
            <v-date-picker
              v-model="dateFromValue"
              @update:model-value="
                onDateSelected('from', $event);
                dateFromMenu = false;
              "
            />
          </v-menu>
        </v-col>

        <v-col cols="12" md="3" sm="6">
          <v-menu
            v-model="dateToMenu"
            :close-on-content-click="false"
            offset-y
            transition="scale-transition"
          >
            <template #activator="{ props }">
              <v-text-field
                v-model="filterForm.date_to"
                clearable
                color="primary"
                density="comfortable"
                hide-details
                :label="$t('transactions.dateTo')"
                prepend-inner-icon="mdi-calendar"
                readonly
                variant="outlined"
                v-bind="props"
                @click:clear="filterForm.date_to = ''"
              />
            </template>
            <v-date-picker
              v-model="dateToValue"
              @update:model-value="
                onDateSelected('to', $event);
                dateToMenu = false;
              "
            />
          </v-menu>
        </v-col>

        <v-col cols="12" md="3" sm="6">
          <v-select
            v-model="filterForm.type"
            color="primary"
            density="comfortable"
            hide-details
            :items="types"
            :label="$t('transactions.type')"
            variant="outlined"
          />
        </v-col>

        <v-col class="d-flex ga-2" cols="12" md="3" sm="6">
          <v-btn
            class="text-none font-weight-bold"
            color="primary"
            height="48"
            prepend-icon="mdi-filter-variant"
            rounded="lg"
            variant="flat"
            @click="handleFilter"
          >
            {{ $t('transactions.filter') }}
          </v-btn>
          <v-btn
            class="text-none font-weight-bold"
            color="grey-darken-1"
            height="48"
            prepend-icon="mdi-close"
            rounded="lg"
            variant="outlined"
            @click="clearFilters"
          >
            {{ $t('transactions.clear') }}
          </v-btn>
        </v-col>
      </v-row>

      <v-expansion-panels v-model="advancedPanel" class="mt-4">
        <v-expansion-panel elevation="0" rounded="lg">
          <v-expansion-panel-title class="text-subtitle-2 font-weight-bold text-grey-darken-2">
            {{ $t('transactions.advancedFilters') }}
            <v-chip
              v-if="activeAdvancedFiltersCount > 0"
              class="ms-2"
              color="primary"
              size="x-small"
              variant="flat"
            >
              {{ activeAdvancedFiltersCount }}
            </v-chip>
          </v-expansion-panel-title>
          <v-expansion-panel-text>
            <v-row dense>
              <v-col cols="12" sm="6">
                <div class="text-caption text-grey-darken-1 mb-1 font-weight-bold">
                  {{ $t('transactions.amountRange') }}
                </div>
                <div class="d-flex ga-2">
                  <v-text-field
                    v-model="filterForm.amount_min"
                    color="primary"
                    density="comfortable"
                    hide-details
                    :placeholder="$t('transactions.min')"
                    type="number"
                    variant="outlined"
                  />
                  <v-text-field
                    v-model="filterForm.amount_max"
                    color="primary"
                    density="comfortable"
                    hide-details
                    :placeholder="$t('transactions.max')"
                    type="number"
                    variant="outlined"
                  />
                </div>
              </v-col>

              <v-col cols="12" sm="6">
                <div class="text-caption text-grey-darken-1 mb-1 font-weight-bold">
                  {{ $t('transactions.reference') }}
                </div>
                <v-text-field
                  v-model="filterForm.reference"
                  color="primary"
                  density="comfortable"
                  hide-details
                  :placeholder="$t('transactions.referencePlaceholder')"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12" sm="6">
                <v-select
                  v-model="filterForm.from_wallet_id"
                  clearable
                  color="primary"
                  density="comfortable"
                  hide-details
                  item-title="name"
                  item-value="id"
                  :items="walletOptions"
                  :label="$t('transactions.fromWallet')"
                  :placeholder="$t('transactions.selectSource')"
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12" sm="6">
                <v-select
                  v-model="filterForm.to_wallet_id"
                  clearable
                  color="primary"
                  density="comfortable"
                  hide-details
                  item-title="name"
                  item-value="id"
                  :items="walletOptions"
                  :label="$t('transactions.toWallet')"
                  :placeholder="$t('transactions.selectDestination')"
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </v-expansion-panel-text>
        </v-expansion-panel>
      </v-expansion-panels>
    </v-card-text>
  </v-card>

  <!-- Transactions Table -->
  <TransactionTable
    :items="transactions"
    :loading="processing"
    :meta="meta"
    show-pagination
    :title="$t('transactions.transactionsList')"
    :wallets="wallets"
    @update:page="handlePageChange"
    @update:per-page="handlePerPageChange"
  />
</template>

<route lang="yaml">
meta:
    layout: App
</route>
