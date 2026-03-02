<script lang="ts" setup>
import type { TeamMember } from '@/api/team-members'
import type { Wallet } from '@/api/wallets'
import { useQuery } from '@pinia/colada'
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import RecentTransactions from '@/components/features/RecentTransactions.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import { useConfirmDialog } from '@/composables/useConfirmDialog'
import { useFormValidation } from '@/composables/useFormValidation'
import { teamMemberByIdQuery } from '@/queries/team-members'
import { walletsListQuery } from '@/queries/wallets'
import { useAuthStore } from '@/stores/auth'
import { useTeamMemberStore } from '@/stores/team-member'
import { getRoleColors } from '@/utils/colors'
import { getValidationErrors, isApiError } from '@/utils/errors'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const teamMemberStore = useTeamMemberStore()
const { confirmDialog, openConfirmDialog, executeConfirm } = useConfirmDialog()
const { formRef, formValid, validate, resetValidation } = useFormValidation()

const memberId = Number((route.params as Record<string, string>).memberId)

const form = ref({
    name: '',
    email: '',
    wallets: [] as number[],
})
const errors = ref<Record<string, string[]>>({})
const processing = ref(false)
const snackbar = ref({ show: false, text: '', color: 'success' })

const requiredRule = (v: unknown) => !!v || t('validation.required')

const { data: queryData, isPending: loading } = useQuery(
    teamMemberByIdQuery,
    () => memberId,
)

const member = ref<TeamMember | null>(null)

watch(queryData, newData => {
    if (newData) {
        member.value = newData
        form.value = {
            name: newData.name,
            email: newData.email,
            wallets: newData.assigned_wallets || [],
        }
        resetValidation()
    }
})

const { data: walletsData } = useQuery(
    walletsListQuery,
    () => ({ page: 1, perPage: 500 }),
)
const wallets = computed<Wallet[]>(() => walletsData.value?.data ?? [])

// Management visibility
const canManage = computed(() =>
    !!member.value
    && authStore.isAdmin
    && member.value.role !== 'Admin'
    && member.value.id !== authStore.user?.id,
)

async function save() {
    const valid = await validate()
    if (!valid) return

    processing.value = true
    errors.value = {}
    try {
        await teamMemberStore.updateMember({ id: memberId, form: form.value })
        snackbar.value = { show: true, text: t('common.saved'), color: 'success' }
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

function confirmPromote() {
    if (!member.value) return
    const isManager = member.value.role === 'Manager'
    const newRole = isManager ? 'member' : 'manager'
    const current = member.value

    openConfirmDialog({
        title: isManager ? t('teamMembers.demoteToMember') : t('teamMembers.promoteToManager'),
        message: isManager
            ? t('teamMembers.confirmDemote', { name: current.name })
            : t('teamMembers.confirmPromote', { name: current.name }),
        requiresPin: false,
        onConfirm: async () => {
            await teamMemberStore.promoteMember({ id: current.id, role: newRole })
            router.push('/team-members/')
        },
    })
}

function confirmDelete() {
    if (!member.value) return
    const current = member.value

    openConfirmDialog({
        title: t('teamMembers.deleteMember'),
        message: t('teamMembers.confirmDelete', { name: current.name }),
        requiresPin: true,
        onConfirm: async () => {
            await teamMemberStore.deleteMember(current.id)
            router.push('/team-members/')
        },
    })
}
</script>

<template>
    <div class="mb-6">
        <v-btn class="text-none mb-4 px-0" color="primary" prepend-icon="mdi-arrow-left" to="/team-members/"
            variant="text">
            {{ $t('teamMembers.backToTeamMembers') }}
        </v-btn>
        <div class="d-flex align-center ga-3">
            <h1 class="text-h5 font-weight-bold text-grey-darken-2" data-testid="page-heading">
                {{ $t('teamMembers.memberDetails') }}
            </h1>
            <v-chip v-if="member" class="text-uppercase font-weight-bold" :color="getRoleColors(member.role).bg"
                size="small" variant="flat">
                <span :class="`text-${getRoleColors(member.role).text}`">{{ member.role }}</span>
            </v-chip>
        </div>
    </div>

    <v-card v-if="loading" border class="pa-8 text-center" flat rounded="lg">
        <v-progress-circular color="primary" indeterminate />
    </v-card>

    <template v-else-if="member">
        <v-row>
            <!-- Edit form + management actions -->
            <v-col cols="12" lg="7">
                <v-card border class="pa-6" flat rounded="lg">
                    <v-form ref="formRef" v-model="formValid" @submit.prevent="save">
                        <div class="d-flex flex-column ga-4">
                            <v-text-field v-model="form.name" data-testid="member-name-input"
                                :error-messages="errors.name" :label="$t('common.fullName')"
                                :placeholder="$t('teamMembers.fullNamePlaceholder')" :rules="[requiredRule]"
                                variant="outlined" />

                            <v-text-field v-model="form.email" data-testid="member-email-input"
                                :error-messages="errors.email" :label="$t('common.emailAddress')"
                                :placeholder="$t('teamMembers.emailPlaceholder')" :rules="[requiredRule]" type="email"
                                variant="outlined" />

                            <div>
                                <div class="text-subtitle-1 font-weight-bold mb-2">
                                    {{ $t('teamMembers.walletAccess') }}
                                </div>
                                <div v-if="errors.wallets" class="text-caption text-error mb-2">
                                    {{ errors.wallets[0] }}
                                </div>
                                <v-row dense>
                                    <v-col v-for="wallet in wallets" :key="wallet.id" cols="12" sm="6">
                                        <v-checkbox v-model="form.wallets" density="compact" hide-details
                                            :label="`${wallet.name} (${wallet.currency})`" :value="wallet.id" />
                                    </v-col>
                                </v-row>
                            </div>

                            <div class="d-flex ga-4 mt-2">
                                <v-btn class="text-none font-weight-bold px-8" color="primary"
                                    data-testid="member-save-btn" :disabled="!formValid" height="48"
                                    :loading="processing" rounded="lg" type="submit">
                                    {{ $t('teamMembers.updateMember') }}
                                </v-btn>
                                <v-btn class="text-none font-weight-bold px-8" color="grey-darken-1"
                                    :disabled="processing" height="48" rounded="lg" to="/team-members/"
                                    variant="outlined">
                                    {{ $t('common.cancel') }}
                                </v-btn>
                            </div>
                        </div>
                    </v-form>

                    <!-- Management actions (admin only) -->
                    <template v-if="canManage">
                        <v-divider class="my-4" />

                        <div class="pa-6 bg-grey-lighten-4 rounded-lg border">
                            <div class="text-subtitle-1 font-weight-bold text-grey-darken-3 mb-1">
                                {{ $t('teamMembers.managementActions') }}
                            </div>
                            <p class="text-caption text-grey-darken-1 mb-6">
                                {{ $t('teamMembers.managementDescription') }}
                            </p>

                            <div class="d-flex flex-column flex-sm-row ga-3">
                                <v-btn class="flex-grow-1 text-none font-weight-bold"
                                    :color="member.role === 'Manager' ? 'grey-darken-1' : 'info'"
                                    data-testid="promote-demote-btn"
                                    :prepend-icon="member.role === 'Manager' ? 'mdi-arrow-down' : 'mdi-arrow-up'"
                                    rounded="lg" variant="flat" @click="confirmPromote">
                                    {{ member.role === 'Manager' ? $t('teamMembers.demoteToMember') :
                                        $t('teamMembers.promoteToManager') }}
                                </v-btn>

                                <v-btn class="flex-grow-1 text-none font-weight-bold" color="error"
                                    data-testid="delete-member-btn" prepend-icon="mdi-delete" rounded="lg"
                                    variant="tonal" @click="confirmDelete">
                                    {{ $t('teamMembers.deleteMember') }}
                                </v-btn>
                            </div>
                        </div>
                    </template>
                </v-card>
            </v-col>

            <!-- Recent transactions (right column, no wrapping card) -->
            <v-col v-if="authStore.isManagerOrAdmin" cols="12" lg="5">
                <RecentTransactions :filter-params="{ initiator_user_id: memberId }"
                    :title="$t('teamMembers.recentTransactions')"
                    :view-all-query="{ initiator_user_id: String(memberId) }" />
            </v-col>
        </v-row>
    </template>

    <ConfirmDialog v-model="confirmDialog.show" confirm-color="error" :message="confirmDialog.message"
        :processing="confirmDialog.processing" :requires-pin="confirmDialog.requiresPin" :title="confirmDialog.title"
        @confirm="executeConfirm" />

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">
        {{ snackbar.text }}
    </v-snackbar>
</template>

<route lang="yaml">
meta:
    layout: App
</route>
