<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { 
  Plus, 
  Search, 
  Edit2, 
  Trash2, 
  Key,
  Shield,
  Users,
  X,
  Loader2,
  AlertCircle,
  Check
} from 'lucide-vue-next'
import { usePermissions } from '@/composables/usePermissions'
import type { Permission } from '@/types'

const {
  permissions,
  roles,
  loading,
  error,
  searchQuery,
  filteredPermissions,
  fetchPermissions,
  submitting,
  createPermission,
  updatePermission,
  deletePermission: deletePermissionAction,
  selectedRoleIds,
  loadingRoles,
  fetchPermissionRoles,
  syncPermissionRoles,
} = usePermissions()

const showModal = ref(false)
const modalMode = ref<'create' | 'edit'>('create')
const form = ref({
  id: null as number | null,
  name: '',
  slug: '',
  description: ''
})

const showAssignModal = ref(false)
const selectedPermission = ref<Permission | null>(null)

const openAssignModal = async (permission: Permission) => {
  selectedPermission.value = permission
  showAssignModal.value = true
  await fetchPermissionRoles(permission.id)
}

const handleAssignRoles = async () => {
  if (!selectedPermission.value) return
  const success = await syncPermissionRoles(selectedPermission.value.id)
  if (success) {
    showAssignModal.value = false
  }
}

const openCreateModal = () => {
  modalMode.value = 'create'
  form.value = { id: null, name: '', slug: '', description: '' }
  showModal.value = true
}

const openEditModal = (permission: Permission) => {
  modalMode.value = 'edit'
  form.value = { ...permission }
  showModal.value = true
}

const handleSubmit = async () => {
  const { id, ...payload } = form.value
  let success = false

  if (modalMode.value === 'create') {
    success = await createPermission(payload)
  } else if (id !== null) {
    success = await updatePermission(id, payload)
  }

  if (success) {
    showModal.value = false
  }
}

const handleDelete = async (id: number) => {
  if (!confirm('Are you sure you want to delete this permission? This may affect user access.')) return

  const result = await deletePermissionAction(id)
  if (!result.success) {
    alert(result.message)
  }
}

onMounted(fetchPermissions)
</script>

<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Permission Management</h1>
        <p class="text-slate-500 mt-1">Manage system-wide permissions and access rules</p>
      </div>
      
      <button 
        @click="openCreateModal"
        class="inline-flex items-center gap-2 bg-gradient-to-r from-[#252578] to-[#3b82f6] text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:shadow-lg transition-all active:scale-95"
      >
        <Plus :size="18" />
        Add New Permission
      </button>
    </div>

    <!-- Stats/Filters Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
            <Key :size="24" />
          </div>
          <div>
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Permissions</p>
            <p class="text-2xl font-bold text-slate-900">{{ permissions.length }}</p>
          </div>
        </div>
      </div>

      <div class="md:col-span-2 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center">
        <div class="relative flex-1">
          <Search :size="18" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input 
            v-model="searchQuery"
            type="text" 
            placeholder="Search permissions by name, slug, or description..." 
            class="w-full pl-10 pr-4 py-2 bg-slate-50 border-none rounded-xl text-sm focus:ring-2 focus:ring-slate-900/5 transition-all"
          />
        </div>
      </div>
    </div>

    <!-- Permissions Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div v-if="loading" class="p-12 flex flex-col items-center justify-center gap-4 text-slate-500">
        <Loader2 class="animate-spin" :size="32" />
        <p class="font-medium">Loading permissions...</p>
      </div>

      <div v-else-if="permissions.length === 0" class="p-12 text-center">
        <div class="w-16 h-16 bg-slate-50 text-slate-400 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <Key :size="32" />
        </div>
        <h3 class="text-lg font-bold text-slate-900">No Permissions Found</h3>
        <p class="text-slate-500 mt-1">Get started by creating your first system permission.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-100">
              <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Permission</th>
              <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Slug</th>
              <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Description</th>
              <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr 
              v-for="permission in filteredPermissions" 
              :key="permission.id"
              class="hover:bg-slate-50/50 transition-colors group"
            >
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 bg-slate-100 text-slate-600 rounded-lg flex items-center justify-center group-hover:bg-[#252578] group-hover:text-white transition-colors">
                    <Shield :size="16" />
                  </div>
                  <span class="font-bold text-slate-900">{{ permission.name }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <code class="px-2 py-1 bg-slate-100 text-slate-700 rounded text-xs font-mono">{{ permission.slug }}</code>
              </td>
              <td class="px-6 py-4">
                <p class="text-sm text-slate-500 line-clamp-1 max-w-xs">{{ permission.description || 'No description' }}</p>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button 
                    @click="openAssignModal(permission)"
                    class="p-2 text-slate-400 hover:text-[#252578] hover:bg-blue-50 rounded-lg transition-all"
                    title="Assign to Roles"
                  >
                    <Users :size="16" />
                  </button>
                  <button 
                    @click="openEditModal(permission)"
                    class="p-2 text-slate-400 hover:text-[#252578] hover:bg-blue-50 rounded-lg transition-all"
                    title="Edit Permission"
                  >
                    <Edit2 :size="16" />
                  </button>
                  <button 
                    @click="handleDelete(permission.id)"
                    class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
                    title="Delete Permission"
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

    <!-- Assign to Roles Modal -->
    <div v-if="showAssignModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showAssignModal = false"></div>
      
      <div class="relative bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
          <div>
            <h2 class="text-xl font-bold text-slate-900">Assign to Roles</h2>
            <p class="text-sm text-slate-500 font-medium">Permission: <span class="text-indigo-600">{{ selectedPermission?.name }}</span></p>
          </div>
          <button @click="showAssignModal = false" class="p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-all">
            <X :size="20" />
          </button>
        </div>

        <div class="p-6">
          <div v-if="loadingRoles" class="py-12 flex flex-col items-center justify-center gap-4 text-slate-500">
            <Loader2 class="animate-spin" :size="32" />
            <p class="font-medium">Loading roles...</p>
          </div>

          <div v-else class="space-y-6">
            <div v-if="error" class="bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-xl flex items-center gap-3 text-sm font-medium">
              <AlertCircle :size="18" />
              {{ error }}
            </div>

            <p class="text-sm text-slate-600">Select the roles that should have this permission:</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[40vh] overflow-y-auto pr-2 custom-scrollbar">
              <label 
                v-for="role in roles" 
                :key="role.id"
                class="relative flex items-center gap-3 p-4 rounded-2xl border-2 transition-all cursor-pointer group"
                :class="selectedRoleIds.includes(role.id) 
                  ? 'border-[#252578] bg-blue-50/30' 
                  : 'border-slate-100 hover:border-slate-200 bg-white'"
              >
                <div 
                  class="w-5 h-5 rounded-md border-2 flex items-center justify-center transition-all"
                  :class="selectedRoleIds.includes(role.id)
                    ? 'bg-[#252578] border-[#252578] text-white'
                    : 'border-slate-300 bg-white group-hover:border-slate-400'"
                >
                  <Check v-if="selectedRoleIds.includes(role.id)" :size="14" stroke-width="3" />
                </div>
                <input 
                  type="checkbox" 
                  :value="role.id" 
                  v-model="selectedRoleIds"
                  class="sr-only"
                />
                <span class="text-sm font-bold text-slate-900">{{ role.name }}</span>
              </label>
            </div>

            <div class="pt-4 flex gap-3">
              <button 
                type="button" 
                @click="showAssignModal = false"
                class="flex-1 px-6 py-3 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors"
              >
                Cancel
              </button>
              <button 
                @click="handleAssignRoles"
                :disabled="submitting"
                class="flex-1 px-6 py-3 bg-gradient-to-r from-[#252578] to-[#3b82f6] text-white text-sm font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                <Loader2 v-if="submitting" class="animate-spin" :size="18" />
                {{ submitting ? 'Updating...' : 'Save Assignments' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showModal = false"></div>
      
      <div class="relative bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
          <h2 class="text-xl font-bold text-slate-900">
            {{ modalMode === 'create' ? 'Add New Permission' : 'Edit Permission' }}
          </h2>
          <button @click="showModal = false" class="p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-all">
            <X :size="20" />
          </button>
        </div>

        <form @submit.prevent="handleSubmit" class="p-6 space-y-5">
          <div v-if="error" class="bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-xl flex items-center gap-3 text-sm font-medium">
            <AlertCircle :size="18" />
            {{ error }}
          </div>

          <div class="space-y-1.5">
            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-1">Permission Name</label>
            <input 
              v-model="form.name"
              type="text" 
              required
              placeholder="e.g. Manage Users"
              class="w-full px-4 py-3 bg-slate-50 border-2 border-transparent rounded-2xl text-sm font-medium focus:bg-white focus:border-slate-900 transition-all outline-none"
            />
          </div>

          <div class="space-y-1.5">
            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-1">Slug</label>
            <input 
              v-model="form.slug"
              type="text" 
              required
              placeholder="e.g. manage-users"
              class="w-full px-4 py-3 bg-slate-50 border-2 border-transparent rounded-2xl text-sm font-mono focus:bg-white focus:border-slate-900 transition-all outline-none"
            />
          </div>

          <div class="space-y-1.5">
            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-1">Description</label>
            <textarea 
              v-model="form.description"
              rows="3"
              placeholder="What does this permission allow?"
              class="w-full px-4 py-3 bg-slate-50 border-2 border-transparent rounded-2xl text-sm font-medium focus:bg-white focus:border-slate-900 transition-all outline-none resize-none"
            ></textarea>
          </div>

          <div class="pt-4 flex gap-3">
            <button 
              type="button" 
              @click="showModal = false"
              class="flex-1 px-6 py-3 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors"
            >
              Cancel
            </button>
            <button 
              type="submit"
              :disabled="submitting"
              class="flex-1 px-6 py-3 bg-gradient-to-r from-[#252578] to-[#3b82f6] text-white text-sm font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
              <Loader2 v-if="submitting" class="animate-spin" :size="18" />
              {{ submitting ? 'Saving...' : (modalMode === 'create' ? 'Add Permission' : 'Save Changes') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
