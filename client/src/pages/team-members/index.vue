<script lang="ts" setup>
  import type { TeamMember } from '@/api/team-members'
  import { computed, ref } from 'vue'
  import { useMutation, useQuery, useQueryCache } from '@pinia/colada'
  import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
  import PageHeader from '@/components/layout/PageHeader.vue'
  import Pagination from '@/components/ui/Pagination.vue'
  import TeamMemberModal from '@/components/features/TeamMemberModal.vue'
  import { useConfirmDialog } from '@/composables/useConfirmDialog'
  import { deleteTeamMember } from '@/api/team-members'
  import { teamMembersListQuery, TEAM_MEMBER_QUERY_KEYS } from '@/queries/team-members'
  import { useAuthStore } from '@/stores/auth'
  import { getRoleColors } from '@/utils/colors'

  const authStore = useAuthStore()
  const queryCache = useQueryCache()
  const showModal = ref(false)
  const selectedUser = ref<TeamMember | null>(null)
  const { confirmDialog, openConfirmDialog } = useConfirmDialog()

  const currentPage = ref(1)

  const { data, isPending: processing } = useQuery(
    teamMembersListQuery,
    () => currentPage.value,
  )

  const members = computed<TeamMember[]>(() => data.value?.members ?? [])
  const paginationData = computed(() => ({
    currentPage: data.value?.pagination?.current_page ?? 1,
    lastPage: data.value?.pagination?.last_page ?? 1,
    total: data.value?.pagination?.total ?? 0,
  }))

  function handlePageChange (page: number) {
    currentPage.value = page
  }

  function openCreateModal () {
    selectedUser.value = null
    showModal.value = true
  }

  function openEditModal (member: TeamMember) {
    selectedUser.value = member
    showModal.value = true
  }

  const { mutateAsync: deleteTeamMemberMutation } = useMutation({
    mutation: (id: number) => deleteTeamMember(id),
    onSettled: () => {
      queryCache.invalidateQueries({ key: TEAM_MEMBER_QUERY_KEYS.root })
    },
  })

  function deleteMember (member: TeamMember) {
    openConfirmDialog({
      title: 'Delete Member',
      message: `Are you sure you want to permanently delete ${member.name}? This action cannot be undone.`,
      requiresPin: true,
      onConfirm: async () => {
        try {
          await deleteTeamMemberMutation(member.id)
        } catch (error) {
          console.error('Error deleting member:', error)
        }
      },
    })
  }

  function handleSaved () {
    queryCache.invalidateQueries({ key: TEAM_MEMBER_QUERY_KEYS.root })
  }
</script>

<template>
  <PageHeader title="Team Members">
    <v-btn
      v-if="authStore.isAdmin"
      class="text-none font-weight-bold"
      color="primary"
      prepend-icon="mdi-plus"
      rounded="lg"
      variant="flat"
      @click="openCreateModal"
    >
      Add Member
    </v-btn>
  </PageHeader>

  <v-card border flat :loading="processing" rounded="lg">
    <div class="overflow-x-auto">
      <v-table density="comfortable">
        <thead class="bg-grey-lighten-4">
          <tr>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              Name
            </th>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              Email
            </th>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              Role
            </th>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              Wallet Access
            </th>
            <th
              v-if="authStore.isAdmin"
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-right"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="member in members" :key="member.id">
            <td class="font-weight-bold text-grey-darken-3">
              {{ member.name }}
              <v-chip
                v-if="member.is_current"
                class="ms-2"
                color="grey-lighten-4"
                size="x-small"
                variant="flat"
              >
                YOU
              </v-chip>
              <v-chip
                v-if="member.is_pending"
                class="text-uppercase ms-2"
                color="orange-lighten-5"
                size="x-small"
                variant="flat"
              >
                <span
                  class="text-orange-darken-3 font-weight-bold"
                >Pending Invitation</span>
              </v-chip>
            </td>
            <td class="text-grey-darken-2">{{ member.email }}</td>
            <td>
              <v-chip
                class="font-weight-bold"
                :color="getRoleColors(member.role).bg"
                size="small"
                variant="flat"
              >
                <span
                  class="font-weight-bold"
                  :class="`text-${getRoleColors(member.role).text}`"
                >{{ member.role }}</span>
              </v-chip>
            </td>
            <td>
              <v-chip
                class="font-weight-bold"
                color="green-lighten-5"
                size="small"
                variant="flat"
              >
                <span
                  class="text-green-darken-3 font-weight-bold"
                >{{ member.wallet_access }}</span>
              </v-chip>
            </td>
            <td v-if="authStore.isAdmin" class="text-right">
              <div class="d-flex ga-2 justify-end">
                <v-btn
                  v-if="
                    !member.is_current &&
                      member.role === 'Member'
                  "
                  color="primary"
                  density="comfortable"
                  icon="mdi-pencil"
                  size="small"
                  variant="text"
                  @click="openEditModal(member)"
                />
                <v-btn
                  v-if="
                    !member.is_current &&
                      member.role === 'Member'
                  "
                  color="error"
                  density="comfortable"
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  @click="deleteMember(member)"
                />
              </div>
            </td>
          </tr>
        </tbody>
      </v-table>
    </div>

    <div class="border-t">
      <Pagination
        :meta="{
          current_page: paginationData.currentPage,
          last_page: paginationData.lastPage,
          per_page: 10,
          total: paginationData.total,
          from: (paginationData.currentPage - 1) * 10 + 1,
          to: Math.min(
            paginationData.currentPage * 10,
            paginationData.total,
          ),
        }"
        @update:page="handlePageChange"
      />
    </div>
  </v-card>

  <TeamMemberModal
    v-model="showModal"
    :user="selectedUser"
    @saved="handleSaved"
  />

  <ConfirmDialog
    v-model="confirmDialog.show"
    :message="confirmDialog.message"
    :requires-pin="confirmDialog.requiresPin"
    :title="confirmDialog.title"
    @confirm="confirmDialog.onConfirm"
  />
</template>

<route lang="yaml">
meta:
    layout: App
</route>
