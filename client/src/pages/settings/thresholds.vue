<script lang="ts" setup>
  import type { CompanyThreshold } from '@/api/settings'
  import { onMounted, reactive, ref, computed } from 'vue'
  import { useI18n } from 'vue-i18n'
  import { deleteCompanyThreshold, fetchCompanyThresholds, upsertCompanyThreshold } from '@/api/settings'
  import { fetchCurrencies } from '@/api/wallets'
  import SettingsLayout from '@/components/layout/SettingsLayout.vue'
  import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
  import Heading from '@/components/ui/Heading.vue'
  import { useAppNotification } from '@/composables/useAppNotification'
  import { useFormSubmit } from '@/composables/useFormSubmit'
  import { useFormValidation } from '@/composables/useFormValidation'

  const { t } = useI18n()
  const { notifyError } = useAppNotification()

  const loading = ref(true)
  const thresholds = ref<CompanyThreshold[]>([])

  const dialog = ref(false)
  const editingId = ref<number | null>(null)
  const form = reactive({
    currency: '',
    approval_threshold: 0,
  })

  const confirmDialog = reactive({
    show: false,
    message: '',
    onConfirm: () => {},
  })

  const currencyOptions = ref<string[]>([])
  const { formRef, formValid, validate, resetValidation } = useFormValidation()

  const requiredRule = (v: unknown) => !!v || t('validation.required')
  const positiveRule = (v: number) => v > 0 || t('validation.positiveAmount')

  const availableCurrencies = computed(() => {
    const used = new Set(thresholds.value.map(t => t.currency))
    return currencyOptions.value.filter(c => !used.has(c))
  })

  const allThresholdsSet = computed(() =>
    currencyOptions.value.length > 0 && availableCurrencies.value.length === 0,
  )

  onMounted(async () => {
    await loadThresholds()
  })

  async function loadThresholds () {
    loading.value = true
    try {
      const [thresholdsRes, currenciesRes] = await Promise.all([
        fetchCompanyThresholds(),
        fetchCurrencies(),
      ])
      thresholds.value = thresholdsRes.data.data
      currencyOptions.value = currenciesRes.data
    } catch (err) {
      notifyError(err)
    } finally {
      loading.value = false
    }
  }

  function openAdd () {
    editingId.value = null
    form.currency = ''
    form.approval_threshold = 0
    resetValidation()
    dialog.value = true
  }

  function openEdit (threshold: CompanyThreshold) {
    editingId.value = threshold.id
    form.currency = threshold.currency
    form.approval_threshold = Number(threshold.approval_threshold)
    resetValidation()
    dialog.value = true
  }

  function confirmDelete (threshold: CompanyThreshold) {
    confirmDialog.message = t('settings.thresholds.deleteConfirmMessage', { currency: threshold.currency })
    confirmDialog.onConfirm = async () => {
      try {
        await deleteCompanyThreshold(threshold.id)
        thresholds.value = thresholds.value.filter(t => t.id !== threshold.id)
      } catch (err) {
        notifyError(err)
      }
    }
    confirmDialog.show = true
  }

  const { processing, errors, submit } = useFormSubmit({
    submitFn: async (data: typeof form) => {
      const response = await upsertCompanyThreshold(data)
      const saved = response.data.data
      const index = thresholds.value.findIndex(t => t.id === saved.id)
      if (index >= 0) {
        thresholds.value[index] = saved
      } else {
        thresholds.value.push(saved)
      }
      dialog.value = false
    },
  })

  async function handleSubmit () {
    const valid = await validate()
    if (!valid) return
    await submit(form)
  }

  function formatAmount (value: string) {
    return Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
  }
</script>

<template>
  <SettingsLayout>
    <div>
      <div class="d-flex flex-column ga-6">
        <div class="d-flex align-center justify-space-between">
          <Heading
            :description="$t('settings.thresholds.description')"
            :title="$t('settings.thresholds.title')"
            variant="small"
          />
          <v-btn
            class="text-none font-weight-bold"
            color="primary"
            :disabled="allThresholdsSet"
            prepend-icon="mdi-plus"
            variant="flat"
            @click="openAdd"
          >
            {{ $t('settings.thresholds.addThreshold') }}
          </v-btn>
        </div>

        <v-skeleton-loader v-if="loading" type="table" />

        <v-card v-else border flat rounded="lg">
          <v-table density="comfortable">
            <thead class="bg-grey-lighten-4">
              <tr>
                <th class="text-uppercase text-caption font-weight-bold text-grey-darken-1">
                  {{ $t('settings.thresholds.currency') }}
                </th>
                <th class="text-uppercase text-caption font-weight-bold text-grey-darken-1">
                  {{ $t('settings.thresholds.approvalThreshold') }}
                </th>
                <th class="text-uppercase text-caption font-weight-bold text-grey-darken-1 text-center" style="width: 100px">
                  {{ $t('common.actions') }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in thresholds" :key="item.id">
                <td class="font-weight-bold">
                  {{ item.currency }}
                </td>
                <td>{{ formatAmount(item.approval_threshold) }}</td>
                <td class="text-center">
                  <v-btn
                    color="primary"
                    density="comfortable"
                    icon="mdi-pencil"
                    size="small"
                    variant="text"
                    @click="openEdit(item)"
                  />
                  <v-btn
                    color="error"
                    density="comfortable"
                    icon="mdi-delete"
                    size="small"
                    variant="text"
                    @click="confirmDelete(item)"
                  />
                </td>
              </tr>
              <tr v-if="thresholds.length === 0">
                <td
                  class="text-grey-darken-1 py-8 text-center"
                  colspan="3"
                >
                  {{ $t('settings.thresholds.noThresholds') }}
                </td>
              </tr>
            </tbody>
          </v-table>
        </v-card>
      </div>

      <!-- Add/Edit Dialog -->
      <v-dialog v-model="dialog" max-width="500">
        <v-card rounded="lg">
          <v-card-title class="pa-4 font-weight-bold">
            {{ editingId ? $t('settings.thresholds.editThreshold') : $t('settings.thresholds.addThreshold') }}
          </v-card-title>
          <v-divider />
          <v-card-text class="pa-4">
            <v-form ref="formRef" v-model="formValid" @submit.prevent="handleSubmit">
              <v-select
                v-model="form.currency"
                :disabled="!!editingId"
                :error-messages="errors.currency"
                :items="editingId ? currencyOptions : availableCurrencies"
                :label="$t('settings.thresholds.currency')"
                :rules="[requiredRule]"
                variant="outlined"
              />
              <v-text-field
                v-model.number="form.approval_threshold"
                :error-messages="errors.approval_threshold"
                :label="$t('settings.thresholds.approvalThreshold')"
                min="0"
                :rules="[requiredRule, positiveRule]"
                type="number"
                variant="outlined"
              />
              <div class="d-flex justify-end ga-3 mt-2">
                <v-btn
                  class="text-none font-weight-bold"
                  variant="text"
                  @click="dialog = false"
                >
                  {{ $t('common.cancel') }}
                </v-btn>
                <v-btn
                  class="text-none font-weight-bold"
                  color="primary"
                  :disabled="!formValid"
                  :loading="processing"
                  type="submit"
                  variant="flat"
                >
                  {{ $t('common.save') }}
                </v-btn>
              </div>
            </v-form>
          </v-card-text>
        </v-card>
      </v-dialog>

      <ConfirmDialog
        v-model="confirmDialog.show"
        confirm-color="error"
        :message="confirmDialog.message"
        :title="$t('settings.thresholds.deleteConfirmTitle')"
        @confirm="confirmDialog.onConfirm"
      />
    </div>
  </SettingsLayout>
</template>

<route lang="yaml">
meta:
    layout: App
</route>
