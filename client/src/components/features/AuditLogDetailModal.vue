<script lang="ts" setup>
import type { AuditLog } from '@/api/audit-logs'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { getAuditCategoryColors, getAuditSeverityColors } from '@/utils/colors'

const props = defineProps<{
  log: AuditLog | null
  modelValue: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const { t } = useI18n()

const categoryLabelMap: Record<string, string> = {
  auth: t('auditLogs.categories.auth'),
  transaction: t('auditLogs.categories.transaction'),
  team: t('auditLogs.categories.team'),
  wallet: t('auditLogs.categories.wallet'),
  settings: t('auditLogs.categories.settings'),
}

function categoryLabel(category: string): string {
  return categoryLabelMap[category] ?? category
}

function formatDate(timestamp: number): string {
  return new Date(timestamp * 1000).toLocaleString()
}

function formatMetadataKey(key: string): string {
  return key.split('_').join(' ').replaceAll(/\b\w/g, (c: string) => c.toUpperCase())
}

function parseMetadata(metadata: unknown): Record<string, unknown> | null {
  if (!metadata) return null
  if (typeof metadata === 'string') {
    try {
      const parsed = JSON.parse(metadata)
      if (typeof parsed === 'object' && parsed !== null) return parsed as Record<string, unknown>
    } catch {
      return null
    }
  }
  if (typeof metadata === 'object' && !Array.isArray(metadata)) {
    return metadata as Record<string, unknown>
  }
  return null
}

const parsedMetadata = computed(() => {
  if (!props.log?.metadata) return null
  return parseMetadata(props.log.metadata)
})

function isChangeEntry(value: unknown): value is { from: unknown, to: unknown } {
  return (
    typeof value === 'object'
    && value !== null
    && !Array.isArray(value)
    && 'from' in value
    && 'to' in value
  )
}

function isChangesObject(value: unknown): boolean {
  if (typeof value !== 'object' || value === null || Array.isArray(value)) return false
  const entries = Object.values(value as Record<string, unknown>)
  return entries.length > 0 && entries.every(v => isChangeEntry(v))
}

function isObject(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value)
}

function formatValue(value: unknown): string {
  if (value === null || value === undefined) return '—'
  if (typeof value === 'boolean') return value ? 'Yes' : 'No'
  if (typeof value === 'number') return String(value)
  if (typeof value === 'string') return value || '—'
  return JSON.stringify(value, null, 2)
}
</script>

<template>
  <v-dialog max-width="560" :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <v-card v-if="log" rounded="lg">
      <v-card-title class="d-flex align-center justify-space-between pa-5 border-b">
        <span class="text-h6 font-weight-bold">{{ $t('auditLogs.detail.title') }}</span>
        <v-btn density="comfortable" icon="mdi-close" variant="text" @click="emit('update:modelValue', false)" />
      </v-card-title>

      <v-card-text class="pa-5">
        <!-- Header: Category & Severity -->
        <div class="d-flex align-center ga-2 mb-5">
          <v-chip :color="getAuditCategoryColors(log.category).bg" size="small"
            :text-color="getAuditCategoryColors(log.category).text" variant="flat">
            <span class="font-weight-bold">{{ categoryLabel(log.category) }}</span>
          </v-chip>
          <v-chip :color="getAuditSeverityColors(log.severity).text" size="small" variant="text">
            <span class="font-weight-bold text-uppercase">{{ log.severity }}</span>
          </v-chip>
        </div>

        <!-- Detail Fields -->
        <v-list class="pa-0" lines="two">
          <v-list-item class="px-0">
            <template #prepend>
              <v-icon class="me-3" color="grey-darken-1" icon="mdi-calendar" />
            </template>
            <div class="text-caption text-grey-darken-1">{{ $t('auditLogs.columns.date') }}</div>
            <div class="text-body-2 font-weight-medium text-grey-darken-3">
              {{ formatDate(log.created_at) }}
            </div>
          </v-list-item>

          <v-divider />

          <v-list-item class="px-0">
            <template #prepend>
              <v-icon class="me-3" color="grey-darken-1" icon="mdi-account" />
            </template>
            <div class="text-caption text-grey-darken-1">{{ $t('auditLogs.columns.user') }}</div>
            <div class="text-body-2 font-weight-medium text-grey-darken-3">
              {{ log.user_name ?? '—' }}
            </div>
          </v-list-item>

          <v-divider />

          <v-list-item class="px-0">
            <template #prepend>
              <v-icon class="me-3" color="grey-darken-1" icon="mdi-lightning-bolt" />
            </template>
            <div class="text-caption text-grey-darken-1">{{ $t('auditLogs.detail.action') }}</div>
            <div class="text-body-2 font-weight-medium text-grey-darken-3">
              <code>{{ log.action }}</code>
            </div>
          </v-list-item>

          <v-divider />

          <v-list-item class="px-0">
            <template #prepend>
              <v-icon class="me-3" color="grey-darken-1" icon="mdi-text" />
            </template>
            <div class="text-caption text-grey-darken-1">{{ $t('auditLogs.columns.description') }}</div>
            <div class="text-body-2 font-weight-medium text-grey-darken-3">
              {{ log.description }}
            </div>
          </v-list-item>

          <v-divider />

          <v-list-item class="px-0">
            <template #prepend>
              <v-icon class="me-3" color="grey-darken-1" icon="mdi-ip-network" />
            </template>
            <div class="text-caption text-grey-darken-1">{{ $t('auditLogs.columns.ipAddress') }}</div>
            <div class="text-body-2 font-weight-medium text-grey-darken-3" style="font-family: monospace">
              {{ log.ip_address ?? '—' }}
            </div>
          </v-list-item>
        </v-list>

        <!-- Metadata Section -->
        <template v-if="parsedMetadata && Object.keys(parsedMetadata).length > 0">
          <v-divider class="my-4" />

          <div class="text-overline font-weight-bold text-grey-darken-1 mb-3">
            {{ $t('auditLogs.detail.metadata') }}
          </div>

          <v-card color="grey-lighten-5" flat rounded="lg">
            <v-list class="pa-0 bg-transparent" density="compact">
              <template v-for="(value, key) in parsedMetadata" :key="key">
                <v-list-item class="px-4 py-2">
                  <div class="d-flex flex-column">
                    <span class="text-caption font-weight-bold text-grey-darken-2">
                      {{ formatMetadataKey(String(key)) }}
                    </span>

                    <!-- Changes table (from → to) -->
                    <template v-if="isChangesObject(value)">
                      <v-table class="mt-1 rounded bg-grey-lighten-4" density="compact">
                        <thead>
                          <tr>
                            <th scope="col" class="text-caption font-weight-bold" style="width: 30%">
                              {{ $t('auditLogs.detail.field') }}
                            </th>
                            <th scope="col" class="text-caption font-weight-bold" style="width: 35%">
                              {{ $t('auditLogs.detail.previousValue') }}
                            </th>
                            <th scope="col" class="text-caption font-weight-bold" style="width: 35%">
                              {{ $t('auditLogs.detail.newValue') }}
                            </th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(change, changeKey) in (value as Record<string, { from: unknown; to: unknown }>)"
                            :key="String(changeKey)">
                            <td class="text-caption font-weight-medium">
                              {{ formatMetadataKey(String(changeKey)) }}
                            </td>
                            <td class="text-caption text-red-darken-2">
                              {{ formatValue(change.from) }}
                            </td>
                            <td class="text-caption text-green-darken-2">
                              {{ formatValue(change.to) }}
                            </td>
                          </tr>
                        </tbody>
                      </v-table>
                    </template>

                    <!-- Single change entry -->
                    <template v-else-if="isChangeEntry(value)">
                      <div class="mt-1 d-flex align-center ga-2">
                        <span class="text-caption text-red-darken-2">{{ formatValue((value as { from: unknown }).from)
                        }}</span>
                        <v-icon color="grey" icon="mdi-arrow-right" size="14" />
                        <span class="text-caption text-green-darken-2">{{ formatValue((value as { to: unknown }).to)
                        }}</span>
                      </div>
                    </template>

                    <!-- Nested object -->
                    <template v-else-if="isObject(value)">
                      <div class="mt-1 pa-2 rounded bg-grey-lighten-4">
                        <div v-for="(subVal, subKey) in (value as Record<string, unknown>)" :key="String(subKey)"
                          class="d-flex align-start ga-2 py-1">
                          <span class="text-caption text-grey-darken-1" style="min-width: 100px">
                            {{ formatMetadataKey(String(subKey)) }}:
                          </span>
                          <span class="text-caption font-weight-medium">
                            {{ formatValue(subVal) }}
                          </span>
                        </div>
                      </div>
                    </template>

                    <!-- Simple value -->
                    <span v-else class="text-body-2 text-grey-darken-3">
                      {{ formatValue(value) }}
                    </span>
                  </div>
                </v-list-item>
                <v-divider />
              </template>
            </v-list>
          </v-card>
        </template>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>
