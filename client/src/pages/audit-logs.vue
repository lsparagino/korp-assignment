<script lang="ts" setup>
  import type { AuditLog, AuditLogFilters } from '@/api/audit-logs'
  import { ShieldCheck } from 'lucide-vue-next'
  import { reactive, ref } from 'vue'
  import { useI18n } from 'vue-i18n'
  import { fetchAuditLogs } from '@/api/audit-logs'
  import PageHeader from '@/components/layout/PageHeader.vue'
  import DataTable from '@/components/ui/DataTable.vue'
  import { useRefreshData } from '@/composables/useRefreshData'
  import { useCompanyStore } from '@/stores/company'

  const { t } = useI18n()
  const companyStore = useCompanyStore()

  const logs = ref<AuditLog[]>([])
  const nextCursor = ref<number | null>(null)
  const loading = ref(false)
  const loadingMore = ref(false)
  const dateFromMenu = ref(false)
  const dateToMenu = ref(false)

  const filters = reactive<AuditLogFilters>({
    category: '',
    severity: '',
    date_from: undefined,
    date_to: undefined,
    per_page: 25,
  })

  const categories = [
    { title: t('auditLogs.categories.all'), value: '' },
    { title: t('auditLogs.categories.auth'), value: 'auth' },
    { title: t('auditLogs.categories.transaction'), value: 'transaction' },
    { title: t('auditLogs.categories.team'), value: 'team' },
    { title: t('auditLogs.categories.wallet'), value: 'wallet' },
    { title: t('auditLogs.categories.settings'), value: 'settings' },
  ]

  const severities = [
    { title: t('auditLogs.severities.all'), value: '' },
    { title: t('auditLogs.severities.normal'), value: 'normal' },
    { title: t('auditLogs.severities.medium'), value: 'medium' },
    { title: t('auditLogs.severities.high'), value: 'high' },
    { title: t('auditLogs.severities.critical'), value: 'critical' },
  ]


  function severityColor(severity: string): string {
    const map: Record<string, string> = {
      normal: 'success',
      medium: 'info',
      high: 'warning',
      critical: 'error',
    }
    return map[severity] ?? 'grey'
  }

  function categoryColor(category: string): string {
    const map: Record<string, string> = {
      auth: 'blue',
      transaction: 'green',
      team: 'purple',
      wallet: 'orange',
      settings: 'teal',
    }
    return map[category] ?? 'grey'
  }

  function formatDate(timestamp: number): string {
    return new Date(timestamp * 1000).toLocaleString()
  }

  function onDateSelected(field: 'from' | 'to', value: unknown) {
    if (!value) return
    const date = value instanceof Date ? value : new Date(value as string)
    const formatted = date.toISOString().split('T')[0]

    if (field === 'from') {
      filters.date_from = formatted
      dateFromMenu.value = false
    } else {
      filters.date_to = formatted
      dateToMenu.value = false
    }
  }

  async function search(append = false) {
    if (append) {
      loadingMore.value = true
    } else {
      loading.value = true
      nextCursor.value = null
    }

    try {
      // Strip empty values so they aren't sent as query params
      const params: AuditLogFilters = {
        per_page: filters.per_page,
        cursor: append ? (nextCursor.value ?? undefined) : undefined,
      }
      if (filters.category) params.category = filters.category
      if (filters.severity) params.severity = filters.severity
      if (filters.date_from) params.date_from = filters.date_from
      if (filters.date_to) params.date_to = filters.date_to

      if (companyStore.currentCompany) {
        (params as Record<string, unknown>).company_id = companyStore.currentCompany.id
      }

      const { data } = await fetchAuditLogs(params)
      if (append) {
        logs.value.push(...data.data)
      } else {
        logs.value = data.data
      }
      nextCursor.value = data.meta.next_cursor
    } finally {
      loading.value = false
      loadingMore.value = false
    }
  }

  function clearFilters() {
    filters.category = ''
    filters.severity = ''
    filters.date_from = undefined
    filters.date_to = undefined
    search()
  }

  const { refreshing, refresh } = useRefreshData(async () => {
    await search()
  })

  // initial load
  search()
</script>

<template>
  <PageHeader :title="$t('auditLogs.title')">
    <div class="d-flex ga-2 align-center">
      <v-icon
        class="text-grey-darken-1"
        :icon="ShieldCheck"
        size="24"
      />
    </div>
  </PageHeader>

  <!-- Filters -->
  <v-card
    border
    class="mb-6"
    data-testid="audit-filter-card"
    flat
    rounded="lg"
  >
    <v-card-title class="pa-4 bg-grey-lighten-4 d-flex align-center border-b">
      <span class="text-grey-darken-1 text-uppercase text-caption font-weight-bold">{{ $t('auditLogs.filterOptions') }}</span>
    </v-card-title>
    <v-card-text class="pa-4">
      <v-row align="center" dense>
        <v-col cols="12" md="3" sm="6">
          <v-select
            v-model="filters.category"
            color="primary"
            data-testid="audit-category-select"
            density="comfortable"
            hide-details
            :items="categories"
            :label="$t('auditLogs.category')"
            variant="outlined"
          />
        </v-col>

        <v-col cols="12" md="3" sm="6">
          <v-select
            v-model="filters.severity"
            color="primary"
            data-testid="audit-severity-select"
            density="comfortable"
            hide-details
            :items="severities"
            :label="$t('auditLogs.severity')"
            variant="outlined"
          />
        </v-col>

        <v-col cols="12" md="3" sm="6">
          <v-menu
            v-model="dateFromMenu"
            :close-on-content-click="false"
          >
            <template #activator="{ props }">
              <v-text-field
                v-model="filters.date_from"
                clearable
                color="primary"
                density="comfortable"
                hide-details
                :label="$t('auditLogs.dateFrom')"
                prepend-inner-icon="mdi-calendar"
                readonly
                variant="outlined"
                v-bind="props"
                @click:clear="filters.date_from = undefined"
              />
            </template>
            <v-date-picker
              @update:model-value="onDateSelected('from', $event)"
            />
          </v-menu>
        </v-col>

        <v-col cols="12" md="3" sm="6">
          <v-menu
            v-model="dateToMenu"
            :close-on-content-click="false"
          >
            <template #activator="{ props }">
              <v-text-field
                v-model="filters.date_to"
                clearable
                color="primary"
                density="comfortable"
                hide-details
                :label="$t('auditLogs.dateTo')"
                prepend-inner-icon="mdi-calendar"
                readonly
                variant="outlined"
                v-bind="props"
                @click:clear="filters.date_to = undefined"
              />
            </template>
            <v-date-picker
              @update:model-value="onDateSelected('to', $event)"
            />
          </v-menu>
        </v-col>
      </v-row>

      <v-row>
        <v-col class="d-flex ga-2 mt-4 justify-end align-center">
          <v-btn
            class="text-none font-weight-bold"
            color="grey-darken-1"
            data-testid="audit-clear-btn"
            prepend-icon="mdi-close"
            rounded="lg"
            variant="outlined"
            @click="clearFilters"
          >
            {{ $t('auditLogs.clear') }}
          </v-btn>
          <v-btn
            class="text-none font-weight-bold"
            color="primary"
            data-testid="audit-filter-btn"
            :loading="loading"
            prepend-icon="mdi-filter-variant"
            rounded="lg"
            variant="flat"
            @click="search()"
          >
            {{ $t('auditLogs.filter') }}
          </v-btn>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>

  <!-- Results Table -->
  <DataTable
    :loading="loading"
    :refreshing="refreshing"
    :title="$t('auditLogs.results')"
    @refresh="refresh"
  >
    <template #columns>
      <th>{{ $t('auditLogs.columns.date') }}</th>
      <th>{{ $t('auditLogs.columns.user') }}</th>
      <th>{{ $t('auditLogs.columns.category') }}</th>
      <th>{{ $t('auditLogs.columns.severity') }}</th>
      <th>{{ $t('auditLogs.columns.action') }}</th>
      <th>{{ $t('auditLogs.columns.description') }}</th>
      <th>{{ $t('auditLogs.columns.ipAddress') }}</th>
    </template>

    <template #body>
      <tr v-for="log in logs" :key="log.id">
        <td class="text-caption">{{ formatDate(log.created_at) }}</td>
        <td>{{ log.user_name ?? '—' }}</td>
        <td>
          <v-chip
            :color="categoryColor(log.category)"
            size="small"
            variant="tonal"
          >
            {{ log.category }}
          </v-chip>
        </td>
        <td>
          <v-chip
            :color="severityColor(log.severity)"
            size="small"
            variant="flat"
          >
            {{ log.severity }}
          </v-chip>
        </td>
        <td><code class="text-caption">{{ log.action }}</code></td>
        <td>{{ log.description }}</td>
        <td class="text-caption text-grey-darken-1">{{ log.ip_address ?? '—' }}</td>
      </tr>
    </template>

    <template #footer>
      <div
        v-if="nextCursor"
        class="d-flex justify-center pa-4 border-t"
      >
        <v-btn
          class="text-none font-weight-bold"
          color="primary"
          data-testid="audit-load-more"
          :loading="loadingMore"
          prepend-icon="mdi-chevron-down"
          rounded="lg"
          variant="tonal"
          @click="search(true)"
        >
          {{ $t('auditLogs.loadMore') }}
        </v-btn>
      </div>
      <div
        v-else-if="logs.length > 0"
        class="text-center pa-4 text-caption text-grey-darken-1 border-t"
      >
        {{ $t('auditLogs.allLoaded') }}
      </div>
    </template>
  </DataTable>
</template>

<route lang="yaml">
meta:
    layout: App
</route>
