<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { useUsers } from '@/composables/useUsers'
import { 
  Loader2, 
  Search, 
  ChevronLeft, 
  ChevronRight,
  Shield,
  Building2,
  Mail,
  User as UserIcon,
  X,
  MoreVertical,
  Check
} from 'lucide-vue-next'
import ConfirmPasswordModal from './ConfirmPasswordModal.vue'
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

// ── Dropdown State ──────────────────────────────────────────────────────────
const activeDropdownUserId = ref<number | null>(null)

const toggleDropdown = (userId: number) => {
  activeDropdownUserId.value = activeDropdownUserId.value === userId ? null : userId
}

const handleWindowClick = (event: MouseEvent) => {
  const target = event.target as HTMLElement
  if (!target.closest('.dropdown-trigger') && !target.closest('.dropdown-menu')) {
    activeDropdownUserId.value = null
  }
}

onMounted(() => {
  fetchUsers()
  fetchMetadata()
  window.addEventListener('click', handleWindowClick)
})

onUnmounted(() => {
  window.removeEventListener('click', handleWindowClick)
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
  activeDropdownUserId.value = null
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

    <!-- Filters Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-wrap items-center gap-4">
      <div class="flex-1 min-w-[240px] relative">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" :size="18" />
        <input 
          v-model="filters.search"
          type="text" 
          placeholder="Search by name or email..." 
          class="w-full h-10 pl-10 pr-4 rounded-xl bg-slate-50 border border-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition-all"
        />
      </div>

      <div class="flex items-center gap-3">
        <select 
          v-model="filters.role_id"
          class="h-10 px-4 rounded-xl bg-slate-50 border border-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 transition-all cursor-pointer"
        >
          <option value="">All Roles</option>
          <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
        </select>

        <select 
          v-model="filters.department_id"
          class="h-10 px-4 rounded-xl bg-slate-50 border border-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 transition-all cursor-pointer"
        >
          <option value="">All Departments</option>
          <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
        </select>

        <select 
          v-model="filters.is_active"
          class="h-10 px-4 rounded-xl bg-slate-50 border border-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 transition-all cursor-pointer"
        >
          <option value="">All Status</option>
          <option value="1">Active</option>
          <option value="0">Inactive</option>
        </select>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-100">
              <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">User</th>
              <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Role</th>
              <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Department</th>
              <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
              <th class="px-8 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="isLoading">
              <td colspan="5" class="px-6 py-12 text-center">
                <Loader2 class="h-8 w-8 text-slate-300 animate-spin mx-auto" />
                <p class="text-slate-400 text-sm mt-2">Loading users...</p>
              </td>
            </tr>
            <tr v-else-if="users.length === 0">
              <td colspan="5" class="px-6 py-12 text-center">
                <UserIcon class="h-12 w-12 text-slate-200 mx-auto mb-3" />
                <p class="text-slate-500 font-medium">No users found match your criteria.</p>
              </td>
            </tr>
            <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50/50 transition-colors group">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center font-bold text-slate-600 border border-slate-200">
                    {{ (user.profile?.first_name || 'U').substring(0, 1).toUpperCase() }}{{ (user.profile?.last_name || '').substring(0, 1).toUpperCase() }}
                  </div>
                  <div>
                    <p class="text-sm font-bold text-slate-900 leading-tight">
                      {{ user.profile?.first_name }} {{ user.profile?.last_name }}
                    </p>
                    <p class="text-xs text-slate-500 mt-0.5 flex items-center gap-1">
                      <Mail :size="12" />
                      {{ user.email }}
                    </p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-bold">
                  <Shield :size="14" />
                  {{ user.profile?.role?.name || 'No Role' }}
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-slate-600">
                <div class="flex items-center gap-2">
                  <Building2 :size="16" class="text-slate-400" />
                  {{ user.profile?.department?.name || 'N/A' }}
                </div>
              </td>
              <td class="px-6 py-4">
                <span 
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                  :class="user.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'"
                >
                  {{ user.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-6 py-4 relative">
                <div class="flex justify-end items-center relative">
                  <!-- Ellipses Trigger Button -->
                  <button 
                    @click.stop="toggleDropdown(user.id)"
                    class="dropdown-trigger p-2 hover:bg-slate-100 rounded-lg text-slate-400 hover:text-slate-900 transition-all"
                    title="Actions"
                  >
                    <MoreVertical :size="18" />
                  </button>

                  <!-- Dropdown expanded container -->
                  <div 
                    v-if="activeDropdownUserId === user.id"
                    class="dropdown-menu absolute right-0 top-full mt-1 z-30 bg-white border border-slate-200 rounded-xl shadow-lg p-2 flex flex-col items-center gap-1.5 whitespace-nowrap animate-in fade-in slide-in-from-top-1 duration-150"
                  >
                    <!-- Assign Role Button -->
                    <button 
                      @click="openRoleModal(user); activeDropdownUserId = null"
                      class="flex-1 px-2.5 py-1.5 hover:bg-slate-100 rounded-lg text-slate-700 hover:text-slate-900 transition-all flex items-center justify-center gap-1.5 text-xs font-bold"
                    >
                      <Shield :size="14" class="text-slate-400" />
                      Assign Role
                    </button>

                    <!-- Activate/Deactivate Button -->
                    <button 
                      @click="openConfirmModal(user)"
                      class="flex-1 px-2.5 py-1.5 rounded-lg transition-all flex items-center justify-center gap-1.5 text-xs font-bold border"
                      :class="user.is_active 
                        ? 'bg-rose-50 border-rose-100 hover:bg-rose-100/70 text-rose-700' 
                        : 'bg-emerald-50 border-emerald-100 hover:bg-emerald-100/70 text-emerald-700'"
                    >
                      <component :is="user.is_active ? X : Check" :size="14" />
                      {{ user.is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="px-6 py-4 bg-slate-50/50 border-t border-slate-100 flex items-center justify-between">
        <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">
          Showing <span class="text-slate-900 font-bold">{{ users.length }}</span> of <span class="text-slate-900 font-bold">{{ pagination.total }}</span> users
        </p>
        <div class="flex items-center gap-2">
          <button 
            @click="fetchUsers(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="p-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-50 transition-all shadow-sm"
          >
            <ChevronLeft :size="16" />
          </button>
          <div class="flex items-center gap-1">
            <button 
              v-for="p in pagination.last_page" 
              :key="p"
              @click="fetchUsers(p)"
              class="w-8 h-8 rounded-lg text-sm font-bold transition-all"
              :class="pagination.current_page === p ? 'bg-slate-900 text-white shadow-md' : 'hover:bg-slate-200 text-slate-600'"
            >
              {{ p }}
            </button>
          </div>
          <button 
            @click="fetchUsers(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="p-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-50 transition-all shadow-sm"
          >
            <ChevronRight :size="16" />
          </button>
        </div>
      </div>
    </div>

    <!-- Role Assignment Modal -->
    <div v-if="showRoleModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showRoleModal = false"></div>
      
      <div class="relative bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
          <div>
            <h2 class="text-xl font-bold text-slate-900">Assign Role</h2>
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wider mt-0.5">Updating {{ selectedUser?.email }}</p>
          </div>
          <button @click="showRoleModal = false" class="p-2 text-slate-400 hover:text-slate-900 hover:bg-white rounded-xl transition-all shadow-sm">
            <X :size="20" />
          </button>
        </div>

        <div class="p-6 space-y-6">
          <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
            <div class="w-12 h-12 rounded-xl bg-white flex items-center justify-center font-bold text-slate-700 shadow-sm border border-slate-200">
              {{ (selectedUser?.profile?.first_name || 'U').substring(0, 1).toUpperCase() }}{{ (selectedUser?.profile?.last_name || '').substring(0, 1).toUpperCase() }}
            </div>
            <div>
              <p class="font-bold text-slate-900">{{ selectedUser?.profile?.first_name }} {{ selectedUser?.profile?.last_name }}</p>
              <p class="text-xs text-slate-500 font-medium">Current: {{ selectedUser?.profile?.role?.name || 'No Role' }}</p>
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-1">Select New Role</label>
            <div class="grid grid-cols-1 gap-2">
              <button 
                v-for="role in roles" 
                :key="role.id"
                @click="selectedRoleId = role.id"
                class="flex items-center justify-between px-4 py-3 rounded-2xl border-2 transition-all group"
                :class="selectedRoleId === role.id 
                  ? 'border-slate-900 bg-slate-900 text-white shadow-lg shadow-slate-900/10' 
                  : 'border-slate-100 bg-slate-50 text-slate-600 hover:border-slate-200'"
              >
                <div class="flex items-center gap-3">
                  <Shield :size="18" :class="selectedRoleId === role.id ? 'text-white' : 'text-slate-400 group-hover:text-slate-900'" />
                  <span class="font-bold text-sm">{{ role.name }}</span>
                </div>
                <div v-if="selectedRoleId === role.id" class="w-2 h-2 bg-white rounded-full"></div>
              </button>
            </div>
          </div>

          <div class="pt-2 flex gap-3">
            <button 
              @click="showRoleModal = false"
              class="flex-1 px-6 py-3 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors"
            >
              Cancel
            </button>
            <button 
              @click="handleAssignRole"
              :disabled="isAssigning || !selectedRoleId"
              class="flex-1 px-6 py-3 bg-gradient-to-r from-[#252578] to-[#3b82f6] text-white text-sm font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
              <Loader2 v-if="isAssigning" class="animate-spin" :size="18" />
              {{ isAssigning ? 'Updating...' : 'Confirm Assignment' }}
            </button>
          </div>
        </div>
      </div>
    </div>
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
