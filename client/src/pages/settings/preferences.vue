<script lang="ts" setup>
import type { UserPreferences } from '@/api/settings'
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { fetchUserPreferences, updateUserPreferences } from '@/api/settings'
import SettingsLayout from '@/components/layout/SettingsLayout.vue'
import Heading from '@/components/ui/Heading.vue'
import IdentityConfirmDialog from '@/components/ui/IdentityConfirmDialog.vue'
import { useAppNotification } from '@/composables/useAppNotification'
import { useFormSubmit } from '@/composables/useFormSubmit'
import { useIdentityConfirm } from '@/composables/useIdentityConfirm'
import { useAuthStore } from '@/stores/auth'
import { usePreferencesStore } from '@/stores/preferences'

const { t } = useI18n()
const authStore = useAuthStore()
const { notifyError } = useAppNotification()
const preferencesStore = usePreferencesStore()
const identity = useIdentityConfirm()

const loading = ref(true)
const form = reactive<Omit<UserPreferences, 'id'>>({
  notify_money_received: true,
  notify_money_sent: true,
  notify_transaction_approved: true,
  notify_transaction_rejected: true,
  notify_approval_needed: true,
  date_format: 'en-GB',
  number_format: 'en-GB',
  daily_transaction_limit: null,
  security_threshold: null,
})

const originalLimit = ref<string | null>(null)
const originalThreshold = ref<string | null>(null)

const isMemberOnly = authStore.user?.role === 'member'
const isManagerOrAdmin = authStore.user?.role === 'admin' || authStore.user?.role === 'manager'

const SUPPORTED_LOCALES = ['en-GB', 'en-US', 'de-DE', 'fr-FR', 'it-IT', 'es-ES', 'ja-JP', 'zh-CN']

const SAMPLE_DATE = new Date(2000, 0, 31, 15, 30)
const SAMPLE_NUMBER = 1234.56

function getLocaleName(locale: string): string {
  const dn = new Intl.DisplayNames([locale], { type: 'language' })
  const name = dn.of(locale) ?? locale
  return name.charAt(0).toUpperCase() + name.slice(1)
}

const localeOptions = computed(() =>
  SUPPORTED_LOCALES.map(locale => ({
    title: `${getLocaleName(locale)} — ${new Intl.DateTimeFormat(locale, { dateStyle: 'short', timeStyle: 'short' }).format(SAMPLE_DATE)}`,
    value: locale,
  })),
)

const numberFormatOptions = computed(() =>
  SUPPORTED_LOCALES.map(locale => ({
    title: `${new Intl.NumberFormat(locale).format(SAMPLE_NUMBER)} (${locale})`,
    value: locale,
  })),
)

onMounted(async () => {
  try {
    const response = await fetchUserPreferences()
    Object.assign(form, response.data.data)
    originalLimit.value = response.data.data.daily_transaction_limit
    originalThreshold.value = response.data.data.security_threshold
  } catch (error) {
    notifyError(error)
  } finally {
    loading.value = false
  }
})

const limitRules = [
  (v: string | null) => {
    if (v === null || v === '') return true
    const num = Number(v)
    if (Number.isNaN(num) || num <= 0) return t('settings.preferences.limitMustBePositive')
    return true
  },
]

const thresholdRules = [
  ...limitRules,
  (v: string | null) => {
    if (v === null || v === '') return true
    const limit = form.daily_transaction_limit
    if (limit === null || limit === '') return true
    if (Number(v) > Number(limit)) return t('settings.preferences.thresholdExceedsLimit')
    return true
  },
]

function isSensitiveFieldChanged(): boolean {
  const currentLimit = form.daily_transaction_limit === '' ? null : form.daily_transaction_limit
  const currentThreshold = form.security_threshold === '' ? null : form.security_threshold
  return currentLimit !== originalLimit.value || currentThreshold !== originalThreshold.value
}

function handleSubmit() {
  if (isSensitiveFieldChanged()) {
    identity.requireConfirmation(async cred => {
      const payload = { ...form, ...cred }
      await updateUserPreferences(payload)
      preferencesStore.update(form.date_format, form.number_format)
      syncOriginals()
      recentlySuccessful.value = true
      setTimeout(() => (recentlySuccessful.value = false), 3000)
    }).catch(() => {
      // cancelled or handled by the dialog
    })
  } else {
    submit(form)
  }
}

function syncOriginals() {
  originalLimit.value = form.daily_transaction_limit === '' ? null : form.daily_transaction_limit
  originalThreshold.value = form.security_threshold === '' ? null : form.security_threshold
}

const { processing, recentlySuccessful, submit } = useFormSubmit({
  submitFn: async (data: typeof form) => {
    await updateUserPreferences(data)
    preferencesStore.update(data.date_format, data.number_format)
    syncOriginals()
  },
})
</script>

<template>
  <SettingsLayout>
    <div class="d-flex flex-column ga-6">
      <Heading :description="$t('settings.preferences.description')" :title="$t('settings.preferences.title')"
        variant="small" />

      <v-skeleton-loader v-if="loading" type="article, actions" />

      <v-form v-else @submit.prevent="handleSubmit()">
        <div class="d-flex flex-column ga-6">
          <!-- Email Notifications -->
          <div>
            <h3 class="text-subtitle-1 font-weight-bold mb-1">
              {{ $t('settings.preferences.notifications') }}
            </h3>
            <p class="text-body-2 text-grey-darken-1 mb-3">
              {{ $t('settings.preferences.notificationsDescription') }}
            </p>

            <div class="d-flex flex-column">
              <v-switch v-model="form.notify_money_received" color="primary" density="compact" hide-details
                :label="$t('settings.preferences.notifyMoneyReceived')" />
              <v-switch v-model="form.notify_money_sent" color="primary" density="compact" hide-details
                :label="$t('settings.preferences.notifyMoneySent')" />
              <v-switch v-if="isMemberOnly" v-model="form.notify_transaction_approved" color="primary" density="compact"
                hide-details :label="$t('settings.preferences.notifyTransactionApproved')" />
              <v-switch v-if="isMemberOnly" v-model="form.notify_transaction_rejected" color="primary" density="compact"
                hide-details :label="$t('settings.preferences.notifyTransactionRejected')" />
              <v-switch v-if="isManagerOrAdmin" v-model="form.notify_approval_needed" color="primary" density="compact"
                hide-details :label="$t('settings.preferences.notifyApprovalNeeded')" />
            </div>
          </div>

          <v-divider />

          <!-- Format & Locale -->
          <div>
            <h3 class="text-subtitle-1 font-weight-bold mb-1">
              {{ $t('settings.preferences.formatting') }}
            </h3>
            <p class="text-body-2 text-grey-darken-1 mb-3">
              {{ $t('settings.preferences.formattingDescription') }}
            </p>

            <v-row>
              <v-col cols="12" sm="6">
                <v-select v-model="form.date_format" density="comfortable" hide-details :items="localeOptions"
                  :label="$t('settings.preferences.dateFormat')" variant="outlined" />
              </v-col>
              <v-col cols="12" sm="6">
                <v-select v-model="form.number_format" density="comfortable" hide-details :items="numberFormatOptions"
                  :label="$t('settings.preferences.numberFormat')" variant="outlined" />
              </v-col>
            </v-row>
          </div>

          <v-divider />

          <!-- Personal Limits -->
          <div>
            <h3 class="text-subtitle-1 font-weight-bold mb-1">
              {{ $t('settings.preferences.limits') }}
            </h3>
            <p class="text-body-2 text-grey-darken-1 mb-3">
              {{ $t('settings.preferences.limitsDescription') }}
            </p>

            <v-row>
              <v-col cols="12" sm="6">
                <v-text-field v-model="form.daily_transaction_limit" data-testid="daily-limit-input"
                  density="comfortable" hide-details="auto" :hint="$t('settings.preferences.dailyTransactionLimitHint')"
                  :label="$t('settings.preferences.dailyTransactionLimit')" min="0" persistent-hint :rules="limitRules"
                  type="number" variant="outlined" />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field v-model="form.security_threshold" data-testid="security-threshold-input"
                  density="comfortable" hide-details="auto" :hint="$t('settings.preferences.securityThresholdHint')"
                  :label="$t('settings.preferences.securityThreshold')" min="0" persistent-hint :rules="thresholdRules"
                  type="number" variant="outlined" />
              </v-col>
            </v-row>
          </div>

          <!-- Submit -->
          <div class="d-flex align-center ga-4 mt-2">
            <v-btn class="text-none font-weight-bold" color="primary" data-testid="preferences-save-btn"
              :loading="processing" type="submit" variant="flat">
              {{ $t('common.save') }}
            </v-btn>

            <v-fade-transition>
              <p v-show="recentlySuccessful" class="text-body-2 text-grey-darken-1">
                {{ $t('common.saved') }}
              </p>
            </v-fade-transition>
          </div>
        </div>
      </v-form>

      <IdentityConfirmDialog v-model="identity.showDialog.value" v-model:credential="identity.credential.value"
        :error="identity.error.value" :has-two-factor="identity.hasTwoFactor.value"
        :is-submitting="identity.isSubmitting.value" :title="$t('settings.preferences.confirmLimitChange')"
        @cancel="identity.cancel()" @confirm="identity.confirm()" />
    </div>
  </SettingsLayout>
</template>

<route lang="yaml">
meta:
    layout: App
</route>
