<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { 
  Plus, 
  Search, 
  Edit2, 
  Trash2, 
  Users as UsersIcon, 
  Building2,
  X,
  Loader2,
  AlertCircle,
  ChevronLeft,
  ChevronRight
} from 'lucide-vue-next'
import { useDepartments } from '@/composables/useDepartments'
import type { Department } from '@/types'

const {
  departments,
  loading,
  error,
  searchQuery,
  filteredDepartments,
  fetchDepartments,
  submitting,
  createDepartment,
  updateDepartment,
  deleteDepartment: deleteDepartmentAction,
  departmentUsers,
  loadingUsers,
  userPagination,
  fetchDepartmentUsers,
} = useDepartments()

const showModal = ref(false)
const modalMode = ref<'create' | 'edit'>('create')
const form = ref({
  id: null as number | null,
  name: '',
  description: ''
})

const showUserModal = ref(false)
const selectedDepartment = ref<Department | null>(null)

const openCreateModal = () => {
  modalMode.value = 'create'
  form.value = { id: null, name: '', description: '' }
  showModal.value = true
}

const openEditModal = (dept: Department) => {
  modalMode.value = 'edit'
  form.value = { id: dept.id, name: dept.name, description: dept.description || '' }
  showModal.value = true
}

const handleSubmit = async () => {
  const { id, ...payload } = form.value
  let success = false

  if (modalMode.value === 'create') {
    success = await createDepartment(payload)
  } else if (id !== null) {
    success = await updateDepartment(id, payload)
  }

  if (success) {
    showModal.value = false
  }
}

const handleDelete = async (id: number) => {
  const dept = departments.value.find(d => d.id === id)
  if (!dept) return

  if ((dept.users_count ?? 0) > 0) {
    alert(`Cannot delete department with ${dept.users_count} assigned users.`)
    return
  }

  if (!confirm('Are you sure you want to delete this department?')) return

  const result = await deleteDepartmentAction(id)
  if (!result.success) {
    alert(result.message)
  }
}

const openUserModal = async (dept: Department, page = 1) => {
  selectedDepartment.value = dept
  showUserModal.value = true
  await fetchDepartmentUsers(dept.id, page)
}

const changeUserPage = (page: number) => {
  if (selectedDepartment.value) {
    openUserModal(selectedDepartment.value, page)
  }
}

onMounted(fetchDepartments)
</script>

<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Department Management</h1>
        <p class="text-slate-500 mt-1">Organize users into organizational units</p>
      </div>
      
      <button 
        @click="openCreateModal"
        class="inline-flex items-center gap-2 bg-gradient-to-r from-[#252578] to-[#3b82f6] text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:shadow-lg transition-all active:scale-95"
      >
        <Plus :size="18" />
        Create New Department
      </button>
    </div>

    <!-- Stats/Filters Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
            <Building2 :size="24" />
          </div>
          <div>
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Departments</p>
            <p class="text-2xl font-bold text-slate-900">{{ departments.length }}</p>
          </div>
        </div>
      </div>

      <div class="md:col-span-2 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center">
        <div class="relative flex-1">
          <Search :size="18" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input 
            v-model="searchQuery"
            type="text" 
            placeholder="Search departments by name or description..." 
            class="w-full pl-10 pr-4 py-2 bg-slate-50 border-none rounded-xl text-sm focus:ring-2 focus:ring-slate-900/5 transition-all"
          />
        </div>
      </div>
    </div>

    <!-- Departments Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div v-if="loading" class="p-12 flex flex-col items-center justify-center gap-4 text-slate-500">
        <Loader2 class="animate-spin" :size="32" />
        <p class="font-medium">Loading departments...</p>
      </div>

      <div v-else-if="departments.length === 0" class="p-12 text-center">
        <div class="w-16 h-16 bg-slate-50 text-slate-400 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <Building2 :size="32" />
        </div>
        <h3 class="text-lg font-bold text-slate-900">No Departments Found</h3>
        <p class="text-slate-500 mt-1">Get started by creating your first department.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-100">
              <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Department Name</th>
              <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Description</th>
              <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Assigned Users</th>
              <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr 
              v-for="dept in filteredDepartments" 
              :key="dept.id"
              class="hover:bg-slate-50/50 transition-colors group"
            >
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 bg-slate-100 text-slate-600 rounded-lg flex items-center justify-center group-hover:bg-[#252578] group-hover:text-white transition-colors">
                    <Building2 :size="16" />
                  </div>
                  <span class="font-bold text-slate-900">{{ dept.name }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <p class="text-sm text-slate-500 line-clamp-1 max-w-xs">{{ dept.description || 'No description' }}</p>
              </td>
              <td class="px-6 py-4 text-center">
                <button 
                  @click="openUserModal(dept)"
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold transition-all hover:scale-105 active:scale-95"
                  :class="(dept.users_count ?? 0) > 0 ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'"
                >
                  <UsersIcon :size="12" />
                  {{ dept.users_count }} Users
                </button>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button 
                    @click="openEditModal(dept)"
                    class="p-2 text-slate-400 hover:text-[#252578] hover:bg-blue-50 rounded-lg transition-all"
                    title="Edit Department"
                  >
                    <Edit2 :size="16" />
                  </button>
                  <button 
                    @click="handleDelete(dept.id)"
                    class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
                    title="Delete Department"
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

    <!-- Users Modal -->
    <div v-if="showUserModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showUserModal = false"></div>
      
      <div class="relative bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
          <div>
            <h2 class="text-xl font-bold text-slate-900">Department Users</h2>
            <p class="text-sm text-slate-500 font-medium">Department: <span class="text-[#252578]">{{ selectedDepartment?.name }}</span></p>
          </div>
          <button @click="showUserModal = false" class="p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-all">
            <X :size="20" />
          </button>
        </div>

        <div class="p-6">
          <div v-if="loadingUsers" class="py-12 flex flex-col items-center justify-center gap-4 text-slate-500">
            <Loader2 class="animate-spin" :size="32" />
            <p class="font-medium">Loading users...</p>
          </div>

          <div v-else-if="departmentUsers.length === 0" class="py-12 text-center text-slate-500">
            <p>No users assigned to this department.</p>
          </div>

          <div v-else class="space-y-4">
            <div class="overflow-hidden border border-slate-100 rounded-2xl">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="bg-slate-50">
                    <th class="px-4 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">User</th>
                    <th class="px-4 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Role</th>
                    <th class="px-4 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Email</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                  <tr v-for="user in departmentUsers" :key="user.id" class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-4 py-3">
                      <div class="font-bold text-slate-900">{{ user.profile?.first_name }} {{ user.profile?.last_name }}</div>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-600">
                      {{ user.profile?.role?.name || 'N/A' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-500">
                      {{ user.email }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div v-if="userPagination.last_page > 1" class="flex items-center justify-between pt-2">
              <p class="text-xs text-slate-500 font-medium">
                Showing {{ departmentUsers.length }} of {{ userPagination.total }} users
              </p>
              <div class="flex items-center gap-2">
                <button 
                  @click="changeUserPage(userPagination.current_page - 1)"
                  :disabled="userPagination.current_page === 1"
                  class="p-2 border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-50 transition-all"
                >
                  <ChevronLeft :size="16" />
                </button>
                <span class="text-sm font-bold text-slate-900 px-2">{{ userPagination.current_page }} / {{ userPagination.last_page }}</span>
                <button 
                  @click="changeUserPage(userPagination.current_page + 1)"
                  :disabled="userPagination.current_page === userPagination.last_page"
                  class="p-2 border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-50 transition-all"
                >
                  <ChevronRight :size="16" />
                </button>
              </div>
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
            {{ modalMode === 'create' ? 'Create New Department' : 'Edit Department' }}
          </h2>
          <button @click="showModal = false" class="p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-all">
            <X :size="20" />
          </button>
        </div>

        <form @submit.prevent="handleSubmit" class="p-6 space-y-5">
          <div v-if="error" class="bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-xl flex items-center gap-3 text-sm font-medium animate-shake">
            <AlertCircle :size="18" />
            {{ error }}
          </div>

          <div class="space-y-1.5">
            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-1">Department Name</label>
            <input 
              v-model="form.name"
              type="text" 
              required
              placeholder="e.g. Engineering"
              class="w-full px-4 py-3 bg-slate-50 border-2 border-transparent rounded-2xl text-sm font-medium focus:bg-white focus:border-slate-900 transition-all outline-none"
            />
          </div>

          <div class="space-y-1.5">
            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-1">Description</label>
            <textarea 
              v-model="form.description"
              rows="3"
              placeholder="What does this department do?"
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
              {{ submitting ? 'Saving...' : (modalMode === 'create' ? 'Create Department' : 'Save Changes') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-4px); }
  75% { transform: translateX(4px); }
}
.animate-shake {
  animation: shake 0.2s ease-in-out 0s 2;
}
</style>
