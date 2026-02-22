<script lang="ts" setup>
  import type { TeamMember } from '@/api/team-members'
  import type { Wallet } from '@/api/wallets'
  import { computed, ref, watch } from 'vue'
  import { useQuery } from '@pinia/colada'
  import { useTeamMemberStore } from '@/stores/team-member'
  import { walletsListQuery } from '@/queries/wallets'
  import { getValidationErrors, isApiError } from '@/utils/errors'

  const props = defineProps<{
    user?: TeamMember | null
    modelValue: boolean
  }>()

  const emit = defineEmits(['update:modelValue', 'saved'])

  const dialog = ref(false)
  const processing = ref(false)
  const form = ref({
    name: '',
    email: '',
    wallets: [] as number[],
  })
  const errors = ref<Record<string, string[]>>({})
  const teamMemberStore = useTeamMemberStore()

  const { data: walletsData } = useQuery(
    walletsListQuery,
    () => ({ page: 1, perPage: 500 }),
  )
  const wallets = computed<Wallet[]>(() => walletsData.value?.data ?? [])

  watch(
    () => props.modelValue,
    val => {
      dialog.value = val
      if (val) {
        errors.value = {}
        form.value = props.user
          ? {
            name: props.user.name,
            email: props.user.email,
            wallets: props.user.assigned_wallets || [],
          }
          : { name: '', email: '', wallets: [] }
      }
    },
  )

  watch(dialog, val => {
    emit('update:modelValue', val)
  })

  async function save () {
    processing.value = true
    errors.value = {}
    try {
      if (props.user?.id) {
        await teamMemberStore.updateMember({ id: props.user.id, form: form.value })
      } else {
        await teamMemberStore.createMember(form.value)
      }
      emit('saved')
      dialog.value = false
    } catch (error: unknown) {
      if (isApiError(error, 422)) {
        errors.value = getValidationErrors(error)
      } else {
        console.error('Error saving member:', error)
      }
    } finally {
      processing.value = false
    }
  }
</script>

<template>
  <v-dialog v-model="dialog" max-width="600">
    <v-card rounded="lg">
      <v-card-title class="pa-4 font-weight-bold">
        {{ user ? $t('teamMembers.editMember') : $t('teamMembers.addMember') }}
      </v-card-title>
      <v-divider />
      <v-card-text class="pa-4">
        <v-form @submit.prevent="save">
          <v-text-field
            v-model="form.name"
            :error-messages="errors.name"
            :label="$t('common.fullName')"
            :placeholder="$t('teamMembers.fullNamePlaceholder')"
            variant="outlined"
          />
          <v-text-field
            v-model="form.email"
            :error-messages="errors.email"
            :label="$t('common.emailAddress')"
            :placeholder="$t('teamMembers.emailPlaceholder')"
            type="email"
            variant="outlined"
          />

          <div class="text-subtitle-1 font-weight-bold mb-2">
            {{ $t('teamMembers.walletAccess') }}
          </div>
          <div
            v-if="errors.wallets"
            class="text-caption text-error mb-2"
          >
            {{ errors.wallets[0] }}
          </div>
          <v-row dense>
            <v-col
              v-for="wallet in wallets"
              :key="wallet.id"
              cols="12"
              sm="6"
            >
              <v-checkbox
                v-model="form.wallets"
                density="compact"
                hide-details
                :label="`${wallet.name} (${wallet.currency})`"
                :value="wallet.id"
              />
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>
      <v-divider />
      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn
          color="grey-darken-1"
          variant="text"
          @click="dialog = false"
        >
          {{ $t('common.cancel') }}
        </v-btn>
        <v-btn
          color="primary"
          :loading="processing"
          variant="flat"
          @click="save"
        >
          {{ user ? $t('teamMembers.updateMember') : $t('teamMembers.inviteMember') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
