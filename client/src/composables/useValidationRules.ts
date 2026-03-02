import { useI18n } from 'vue-i18n'

export function useValidationRules () {
  const { t } = useI18n()

  function requiredRule (v: unknown) {
    return !!v || t('validation.required')
  }

  function positiveAmountRule (v: number) {
    return v > 0 || t('validation.positiveAmount')
  }

  return { requiredRule, positiveAmountRule }
}
