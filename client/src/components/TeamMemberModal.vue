<script lang="ts" setup>
  import type { TeamMember, Wallet } from '@/types'
  import { computed, ref, watch } from 'vue'
  import { useMutation, useQuery, useQueryCache } from '@pinia/colada'
  import { createTeamMember, updateTeamMember } from '@/api/team-members'
  import { walletsListQuery } from '@/queries/wallets'
  import { TEAM_MEMBER_QUERY_KEYS } from '@/queries/team-members'
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
  const queryCache = useQueryCache()

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

  const { mutateAsync: saveMember } = useMutation({
    mutation: (data: { form: { name: string, email: string, wallets: number[] }, userId?: number }) =>
      data.userId
        ? updateTeamMember(data.userId, data.form)
        : createTeamMember(data.form),
    onSettled: () => {
      queryCache.invalidateQueries({ key: TEAM_MEMBER_QUERY_KEYS.root })
    },
  })

  async function save () {
    processing.value = true
    errors.value = {}
    try {
      await saveMember({ form: form.value, userId: props.user?.id })
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
        {{ user ? 'Edit Member' : 'Add Member' }}
      </v-card-title>
      <v-divider />
      <v-card-text class="pa-4">
        <v-form @submit.prevent="save">
          <v-text-field
            v-model="form.name"
            :error-messages="errors.name"
            label="Full Name"
            placeholder="John Doe"
            variant="outlined"
          />
          <v-text-field
            v-model="form.email"
            :error-messages="errors.email"
            label="Email Address"
            placeholder="john@example.com"
            type="email"
            variant="outlined"
          />

          <div class="text-subtitle-1 font-weight-bold mb-2">
            Wallet Access
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
          Cancel
        </v-btn>
        <v-btn
          color="primary"
          :loading="processing"
          variant="flat"
          @click="save"
        >
          {{ user ? 'Update Member' : 'Invite Member' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
