<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { useUsers } from '@/composables/useUsers'
import { 
  Search, 
  User as UserIcon,
} from 'lucide-vue-next'
import ConfirmPasswordModal from '../common/ConfirmPasswordModal.vue'
import UsersTable from './UsersTable.vue'
import AssignRoleModal from './AssignRoleModal.vue'
import type { User } from '@/types'

const {
  users,
  roles,
  departments,
  isLoading,
  pagination,
  filters,
  fetchUsers,
  fetchMetadata,
  isAssigning,
  assignRole,
  isTogglingStatus,
  confirmPasswordError,
  toggleUserStatus,
} = useUsers()

onMounted(() => {
  fetchUsers()
  fetchMetadata()
})

watch(filters, () => {
  fetchUsers(1)
}, { deep: true })

// ── Role Assignment Modal ───────────────────────────────────────────────────
const showRoleModal = ref(false)
const selectedUser = ref<User | null>(null)
const selectedRoleId = ref<number | string>('')

const openRoleModal = (user: User) => {
  selectedUser.value = user
  selectedRoleId.value = user.profile?.role?.id || ''
  showRoleModal.value = true
}

const handleAssignRole = async () => {
  if (!selectedUser.value || !selectedRoleId.value) return
  
  const result = await assignRole(selectedUser.value.id, selectedRoleId.value)
  if (result.success) {
    showRoleModal.value = false
    fetchUsers(pagination.value.current_page)
  } else {
    alert(result.message)
  }
}

// ── Status Toggle Modal ─────────────────────────────────────────────────────
const showConfirmModal = ref(false)
const userToToggleStatus = ref<User | null>(null)

const openConfirmModal = (user: User) => {
  userToToggleStatus.value = user
  confirmPasswordError.value = ''
  showConfirmModal.value = true
}

const handleConfirmStatusToggle = async (password: string) => {
  if (!userToToggleStatus.value) return

  const success = await toggleUserStatus(userToToggleStatus.value.id, password)
  if (success) {
    showConfirmModal.value = false
    fetchUsers(pagination.value.current_page)
  }
}
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold text-slate-900 tracking-tight">User Management</h1>
        <p class="text-slate-500 mt-1">Manage and monitor all system users and their permissions.</p>
      </div>
      <router-link 
        to="/admin/users/create"
        class="inline-flex items-center justify-center h-11 px-6 rounded-xl bg-gradient-to-r from-[#252578] to-[#3b82f6] text-white text-sm font-semibold hover:shadow-lg transition-all"
      >
        Add New User
      </router-link>
    </div>

    <!-- Stats/Filters Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
            <UserIcon :size="24" />
          </div>
          <div>
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Users</p>
            <p class="text-2xl font-bold text-slate-900">{{ pagination?.total || users.length }}</p>
          </div>
        </div>
      </div>

      <div class="md:col-span-2 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col gap-4">
        <!-- Search bar on top -->
        <div class="relative w-full">
          <Search :size="18" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input 
            v-model="filters.search"
            type="text" 
            placeholder="Search by name or email..." 
            class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition-all"
          />
        </div>
        <!-- Filter dropdowns below -->
        <div class="flex flex-wrap items-center gap-3">
          <select 
            v-model="filters.role_id"
            class="flex-1 h-10 px-4 rounded-xl bg-slate-50 border border-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 transition-all cursor-pointer"
          >
            <option value="">All Roles</option>
            <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
          </select>

          <select 
            v-model="filters.department_id"
            class="flex-1 h-10 px-4 rounded-xl bg-slate-50 border border-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 transition-all cursor-pointer"
          >
            <option value="">All Departments</option>
            <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
          </select>

          <select 
            v-model="filters.is_active"
            class="flex-1 h-10 px-4 rounded-xl bg-slate-50 border border-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 transition-all cursor-pointer"
          >
            <option value="">All Status</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Table -->
    <UsersTable
      :users="users"
      :is-loading="isLoading"
      :pagination="pagination"
      v-model:per-page="filters.per_page"
      @fetch="fetchUsers"
      @open-role-modal="openRoleModal"
      @open-confirm-modal="openConfirmModal"
    />

    <!-- Role Assignment Modal -->
    <AssignRoleModal
      v-model="showRoleModal"
      :user="selectedUser"
      :roles="roles"
      v-model:selected-role-id="selectedRoleId"
      :is-assigning="isAssigning"
      @submit="handleAssignRole"
    />
    <!-- Password Confirmation Modal for Status Toggle -->
    <ConfirmPasswordModal
      :show="showConfirmModal"
      :title="userToToggleStatus?.is_active ? 'Deactivate User Account' : 'Activate User Account'"
      :description="userToToggleStatus?.is_active ? 'Are you sure you want to deactivate this user? They will be signed out of all active sessions.' : 'Are you sure you want to activate this user?'"
      :confirmLabel="userToToggleStatus?.is_active ? 'Deactivate' : 'Activate'"
      :isSubmitting="isTogglingStatus"
      :error="confirmPasswordError"
      @close="showConfirmModal = false"
      @confirm="handleConfirmStatusToggle"
    />
  </div>
</template>
