<script lang="ts" setup>
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import TeamMemberModal from '@/components/features/TeamMemberModal.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import DataTable from '@/components/ui/DataTable.vue'
import { useRefreshData } from '@/composables/useRefreshData'
import { useUrlPagination } from '@/composables/useUrlPagination'
import { useTeamMemberList } from '@/queries/team-members'
import { useAuthStore } from '@/stores/auth'
import { getRoleColors } from '@/utils/colors'

const { t } = useI18n()
const router = useRouter()
const authStore = useAuthStore()
const { page: urlPage, handlePageChange } = useUrlPagination()

const { members, meta, isPending: processing, refetch, page } = useTeamMemberList()

watch(urlPage, val => {
  page.value = val
}, { immediate: true })

const { refreshing, refresh } = useRefreshData(async () => {
  await refetch()
})

const showModal = ref(false)

function openAdd() {
  showModal.value = true
}

function navigateToMember(id: number) {
  router.push(`/team-members/${id}`)
}
</script>

<template>
  <PageHeader :title="$t('teamMembers.title')">
    <div class="d-flex ga-2 align-center">
      <v-btn v-if="authStore.isAdmin" class="text-none font-weight-bold" color="primary" data-testid="add-member-btn"
        prepend-icon="mdi-plus" rounded="lg" variant="flat" @click="openAdd">
        {{ $t('teamMembers.addMember') }}
      </v-btn>
    </div>
  </PageHeader>

  <DataTable :loading="processing" :meta="meta" :refreshing="refreshing" :title="$t('teamMembers.title')"
    @refresh="refresh" @update:page="handlePageChange">
    <template #columns>
      <th>{{ $t('teamMembers.tableHeaders.name') }}</th>
      <th>{{ $t('teamMembers.tableHeaders.email') }}</th>
      <th>{{ $t('teamMembers.tableHeaders.role') }}</th>
      <th>{{ $t('teamMembers.tableHeaders.walletAccess') }}</th>
    </template>

    <template #body>
      <tr v-for="m in members" :key="m.id" class="cursor-pointer" :data-testid="`member-row-${m.id}`"
        @click="navigateToMember(m.id)">
        <td class="font-weight-bold text-grey-darken-3">
          <div class="d-flex align-center ga-2">
            {{ m.name }}
            <v-chip v-if="m.id === authStore.user?.id" class="font-weight-bold" color="primary" size="x-small"
              variant="flat">{{ $t('teamMembers.you') }}</v-chip>
            <v-chip v-if="m.is_pending" class="font-weight-bold" color="warning" size="x-small" variant="flat">{{
              $t('teamMembers.pendingInvitation') }}</v-chip>
          </div>
        </td>
        <td class="text-grey-darken-2">{{ m.email }}</td>
        <td>
          <v-chip class="text-uppercase font-weight-bold" :color="getRoleColors(m.role).bg" size="small" variant="flat">
            <span :class="`text-${getRoleColors(m.role).text}`">{{ m.role }}</span>
          </v-chip>
        </td>
        <td>
          <span class="text-caption text-grey-darken-2">{{ m.wallet_access }}</span>
        </td>
      </tr>
    </template>
  </DataTable>

  <TeamMemberModal v-model="showModal" @saved="showModal = false; refetch()" />
</template>

<route lang="yaml">
meta:
    layout: App
</route>
