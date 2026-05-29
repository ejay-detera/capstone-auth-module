<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { 
  Loader2, 
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
import type { User, PaginationMeta } from '@/types'

const props = defineProps<{
  users: User[]
  isLoading: boolean
  pagination: PaginationMeta
  perPage: number
}>()

const emit = defineEmits<{
  'update:perPage': [value: number]
  'fetch': [page: number]
  'open-role-modal': [user: User]
  'open-confirm-modal': [user: User]
}>()

const internalPerPage = computed({
  get: () => props.perPage,
  set: (value) => emit('update:perPage', value)
})

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
  window.addEventListener('click', handleWindowClick)
})

onUnmounted(() => {
  window.removeEventListener('click', handleWindowClick)
})

const handleOpenRoleModal = (user: User) => {
  activeDropdownUserId.value = null
  emit('open-role-modal', user)
}

const handleOpenConfirmModal = (user: User) => {
  activeDropdownUserId.value = null
  emit('open-confirm-modal', user)
}
</script>

<template>
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
                    @click="handleOpenRoleModal(user)"
                    class="flex-1 px-2.5 py-1.5 hover:bg-slate-100 rounded-lg text-slate-700 hover:text-slate-900 transition-all flex items-center justify-center gap-1.5 text-xs font-bold"
                  >
                    <Shield :size="14" class="text-slate-400" />
                    Assign Role
                  </button>

                  <!-- Activate/Deactivate Button -->
                  <button 
                    @click="handleOpenConfirmModal(user)"
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
    <div v-if="pagination && pagination.total > 0" class="px-6 py-4 bg-slate-50/50 border-t border-slate-100 flex items-center justify-between">
      <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">
        Showing <span class="text-slate-900 font-bold">{{ users.length }}</span> of <span class="text-slate-900 font-bold">{{ pagination.total }}</span> users
      </p>
      <div class="flex items-center gap-6">
        <div class="flex items-center gap-2">
          <span class="text-xs text-slate-500 font-medium uppercase tracking-wider">Rows per page:</span>
          <select 
            v-model.number="internalPerPage"
            class="h-8 px-2 pr-6 rounded-lg bg-white border border-slate-200 text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#3b82f6]/20 focus:border-[#3b82f6] cursor-pointer shadow-sm transition-all"
          >
            <option :value="10">10</option>
            <option :value="15">15</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
        <div class="flex items-center gap-2" v-if="pagination.last_page > 1">
          <button 
            @click="emit('fetch', pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="p-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-50 transition-all shadow-sm"
          >
            <ChevronLeft :size="16" />
          </button>
          <div class="flex items-center gap-1">
            <button 
              v-for="p in pagination.last_page" 
              :key="p"
              @click="emit('fetch', p)"
              class="w-8 h-8 rounded-lg text-sm font-bold transition-all"
              :class="pagination.current_page === p ? 'bg-slate-900 text-white shadow-md' : 'hover:bg-slate-200 text-slate-600'"
            >
              {{ p }}
            </button>
          </div>
          <button 
            @click="emit('fetch', pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="p-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-50 transition-all shadow-sm"
          >
            <ChevronRight :size="16" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
