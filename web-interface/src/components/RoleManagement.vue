<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { 
  Plus, 
  Search, 
  Edit2, 
  Trash2, 
  Shield,
  Key,
  Users,
  X,
  Loader2,
  AlertCircle,
  Check
} from 'lucide-vue-next'
import { useRoles } from '@/composables/useRoles'
import type { Role } from '@/types'

const {
  loading,
  error,
  searchQuery,
  filteredRoles,
  fetchRoles,
  submitting,
  createRole,
  updateRole,
  deleteRole,
  // Permission Sync
  allPermissions,
  selectedPermissionIds,
  loadingPermissions,
  fetchRolePermissions,
  syncRolePermissions,
  // Role Users
  roleUsers,
  loadingUsers,
  fetchRoleUsers,
} = useRoles()

// ── Create / Edit Modal ──────────────────────────────────────────────────────
const showModal = ref(false)
const modalMode = ref<'create' | 'edit'>('create')
const form = ref({
  id: null as number | null,
  name: '',
  description: ''
})

const openCreateModal = () => {
  modalMode.value = 'create'
  form.value = { id: null, name: '', description: '' }
  error.value = ''
  showModal.value = true
}

const openEditModal = (role: Role) => {
  modalMode.value = 'edit'
  form.value = { id: role.id, name: role.name, description: role.description || '' }
  error.value = ''
  showModal.value = true
}

const handleSubmit = async () => {
  const { id, ...payload } = form.value
  let success = false

  if (modalMode.value === 'create') {
    success = await createRole(payload)
  } else if (id !== null) {
    success = await updateRole(id, payload)
  }

  if (success) {
    showModal.value = false
  }
}

const handleDelete = async (id: number) => {
  if (!confirm('Are you sure you want to delete this role? This action cannot be undone.')) return

  const result = await deleteRole(id)
  if (!result.success) {
    alert(result.message)
  }
}

// ── Permission Sync Modal ────────────────────────────────────────────────────
const showPermModal = ref(false)
const selectedRole = ref<Role | null>(null)

const openPermModal = async (role: Role) => {
  selectedRole.value = role
  showPermModal.value = true
  await fetchRolePermissions(role.id)
}

const togglePermission = (permId: number) => {
  const idx = selectedPermissionIds.value.indexOf(permId)
  if (idx >= 0) {
    selectedPermissionIds.value.splice(idx, 1)
  } else {
    selectedPermissionIds.value.push(permId)
  }
}

const handleSavePermissions = async () => {
  if (!selectedRole.value) return
  const success = await syncRolePermissions(selectedRole.value.id)
  if (success) {
    showPermModal.value = false
  }
}

// ── Users Modal ──────────────────────────────────────────────────────────────
const showUsersModal = ref(false)
const viewingRole = ref<Role | null>(null)

const openUsersModal = async (role: Role) => {
  viewingRole.value = role
  showUsersModal.value = true
  await fetchRoleUsers(role.id)
}

onMounted(fetchRoles)
</script>

<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Role Management</h1>
        <p class="text-slate-500 mt-1">Create and manage user roles and their permissions</p>
      </div>
      
      <button 
        @click="openCreateModal"
        class="inline-flex items-center gap-2 bg-gradient-to-r from-[#252578] to-[#3b82f6] text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:shadow-lg transition-all active:scale-95"
      >
        <Plus :size="18" />
        Create Role
      </button>
    </div>

    <!-- Search -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm">
      <div class="px-6 py-4 border-b border-slate-100">
        <div class="relative max-w-sm">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" :size="16" />
          <input 
            v-model="searchQuery"
            type="text" 
            placeholder="Search roles..." 
            class="w-full h-10 pl-10 pr-4 rounded-xl bg-slate-50 border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition-all"
          />
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="p-16 flex flex-col items-center justify-center gap-4 text-slate-500">
        <Loader2 class="animate-spin" :size="32" />
        <p class="font-medium">Loading roles...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error && filteredRoles.length === 0" class="p-16 text-center">
        <AlertCircle class="mx-auto mb-3 text-red-400" :size="32" />
        <p class="text-red-600 font-medium">{{ error }}</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredRoles.length === 0" class="p-16 text-center text-slate-500">
        <Shield class="mx-auto mb-3 text-slate-400" :size="32" />
        <p class="font-medium">No roles found.</p>
      </div>

      <!-- Table -->
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50/50">
              <th class="px-6 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Role</th>
              <th class="px-6 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Description</th>
              <th class="px-6 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Users</th>
              <th class="px-6 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="role in filteredRoles" :key="role.id" class="hover:bg-slate-50/50 transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                    <Shield :size="16" class="text-indigo-600" />
                  </div>
                  <span class="font-bold text-slate-900">{{ role.name }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <p class="text-sm text-slate-500 line-clamp-1 max-w-xs">{{ role.description || 'No description' }}</p>
              </td>
              <td class="px-6 py-4 text-center">
                <button 
                  @click="openUsersModal(role)"
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold transition-all hover:scale-105 active:scale-95"
                  :class="(role.users_count ?? 0) > 0 ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'"
                >
                  <Users :size="12" />
                  {{ role.users_count ?? 0 }} Users
                </button>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button 
                    @click="openPermModal(role)"
                    class="p-2 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-all"
                    title="Manage Permissions"
                  >
                    <Key :size="16" />
                  </button>
                  <button 
                    @click="openEditModal(role)"
                    class="p-2 text-slate-400 hover:text-[#252578] hover:bg-blue-50 rounded-lg transition-all"
                    title="Edit Role"
                  >
                    <Edit2 :size="16" />
                  </button>
                  <button 
                    @click="handleDelete(role.id)"
                    class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
                    title="Delete Role"
                  >
                    <Trash2 :size="16" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create/Edit Role Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showModal = false"></div>
      
      <div class="relative bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
          <div>
            <h2 class="text-xl font-bold text-slate-900">{{ modalMode === 'create' ? 'Create Role' : 'Edit Role' }}</h2>
            <p class="text-xs text-slate-500 font-medium mt-0.5">{{ modalMode === 'create' ? 'Add a new role to the system' : 'Update role details' }}</p>
          </div>
          <button @click="showModal = false" class="p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-all">
            <X :size="20" />
          </button>
        </div>

        <form @submit.prevent="handleSubmit" class="p-6 space-y-5">
          <div class="space-y-2">
            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-1">Role Name</label>
            <input 
              v-model="form.name"
              type="text" 
              placeholder="e.g. Manager"
              required
              class="w-full h-11 px-4 rounded-xl bg-slate-50 border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition-all"
            />
          </div>

          <div class="space-y-2">
            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-1">Description</label>
            <textarea 
              v-model="form.description"
              placeholder="Optional description..."
              rows="3"
              class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition-all resize-none"
            ></textarea>
          </div>

          <div v-if="error" class="flex items-center gap-2 text-sm text-red-600 bg-red-50 border border-red-100 px-4 py-3 rounded-xl">
            <AlertCircle :size="16" />
            <span>{{ error }}</span>
          </div>

          <div class="pt-2 flex gap-3">
            <button 
              type="button"
              @click="showModal = false"
              class="flex-1 h-11 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-all border border-slate-200"
            >
              Cancel
            </button>
            <button 
              type="submit"
              :disabled="submitting"
              class="flex-1 h-11 bg-gradient-to-r from-[#252578] to-[#3b82f6] text-white text-sm font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 flex items-center justify-center gap-2"
            >
              <Loader2 v-if="submitting" class="animate-spin" :size="18" />
              {{ submitting ? 'Saving...' : (modalMode === 'create' ? 'Create Role' : 'Save Changes') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Permission Sync Modal -->
    <div v-if="showPermModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showPermModal = false"></div>
      
      <div class="relative bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
          <div>
            <h2 class="text-xl font-bold text-slate-900">Manage Permissions</h2>
            <p class="text-xs text-slate-500 font-medium mt-0.5">Role: <span class="text-[#252578]">{{ selectedRole?.name }}</span></p>
          </div>
          <button @click="showPermModal = false" class="p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-all">
            <X :size="20" />
          </button>
        </div>

        <div class="p-6">
          <div v-if="loadingPermissions" class="py-12 flex flex-col items-center justify-center gap-4 text-slate-500">
            <Loader2 class="animate-spin" :size="32" />
            <p class="font-medium">Loading permissions...</p>
          </div>

          <div v-else class="space-y-4">
            <div v-if="allPermissions.length === 0" class="py-8 text-center text-slate-500">
              <p>No permissions available.</p>
            </div>

            <div v-else class="max-h-[360px] overflow-y-auto space-y-2 pr-1">
              <label 
                v-for="perm in allPermissions" 
                :key="perm.id"
                class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-50 cursor-pointer transition-colors border border-transparent"
                :class="selectedPermissionIds.includes(perm.id) ? 'bg-blue-50/50 border-blue-100' : ''"
              >
                <div 
                  class="w-5 h-5 rounded-md border-2 flex items-center justify-center flex-shrink-0 transition-all"
                  :class="selectedPermissionIds.includes(perm.id) ? 'bg-[#252578] border-[#252578]' : 'border-slate-300'"
                  @click.prevent="togglePermission(perm.id)"
                >
                  <Check v-if="selectedPermissionIds.includes(perm.id)" :size="12" class="text-white" />
                </div>
                <div @click.prevent="togglePermission(perm.id)">
                  <div class="text-sm font-bold text-slate-900">{{ perm.name }}</div>
                  <div class="text-xs text-slate-500">{{ perm.slug }}</div>
                </div>
              </label>
            </div>

            <div v-if="error" class="flex items-center gap-2 text-sm text-red-600 bg-red-50 border border-red-100 px-4 py-3 rounded-xl">
              <AlertCircle :size="16" />
              <span>{{ error }}</span>
            </div>

            <div class="pt-2 flex gap-3">
              <button 
                type="button"
                @click="showPermModal = false"
                class="flex-1 h-11 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-all border border-slate-200"
              >
                Cancel
              </button>
              <button 
                @click="handleSavePermissions"
                :disabled="submitting"
                class="flex-1 h-11 bg-gradient-to-r from-[#252578] to-[#3b82f6] text-white text-sm font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 flex items-center justify-center gap-2"
              >
                <Loader2 v-if="submitting" class="animate-spin" :size="18" />
                {{ submitting ? 'Saving...' : 'Save Permissions' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Users Modal -->
    <div v-if="showUsersModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showUsersModal = false"></div>
      
      <div class="relative bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
          <div>
            <h2 class="text-xl font-bold text-slate-900">Role Users</h2>
            <p class="text-sm text-slate-500 font-medium">Role: <span class="text-[#252578]">{{ viewingRole?.name }}</span></p>
          </div>
          <button @click="showUsersModal = false" class="p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-all">
            <X :size="20" />
          </button>
        </div>

        <div class="p-6">
          <div v-if="loadingUsers" class="py-12 flex flex-col items-center justify-center gap-4 text-slate-500">
            <Loader2 class="animate-spin" :size="32" />
            <p class="font-medium">Loading users...</p>
          </div>

          <div v-else-if="roleUsers.length === 0" class="py-12 text-center text-slate-500">
            <p>No users assigned to this role.</p>
          </div>

          <div v-else class="space-y-4">
            <div class="overflow-hidden border border-slate-100 rounded-2xl">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="bg-slate-50">
                    <th class="px-4 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">User</th>
                    <th class="px-4 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Department</th>
                    <th class="px-4 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Email</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                  <tr v-for="user in roleUsers" :key="user.id" class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-4 py-3">
                      <div class="font-bold text-slate-900">{{ user.profile?.first_name }} {{ user.profile?.last_name }}</div>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-600">
                      {{ user.profile?.department?.name || 'N/A' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-500">
                      {{ user.email }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
