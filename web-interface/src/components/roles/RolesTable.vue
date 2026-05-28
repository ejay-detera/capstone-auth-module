<script setup lang="ts">
import { computed } from 'vue'
import { 
  Loader2, 
  Shield, 
  Users, 
  Edit2, 
  Trash2, 
  Key,
  AlertCircle,
  ChevronLeft, 
  ChevronRight 
} from 'lucide-vue-next'
import type { Role, PaginationMeta } from '@/types'

const props = defineProps<{
  loading: boolean
  error: string | null
  roles: Role[]
  pagination: PaginationMeta | null
  perPage: number
}>()

const emit = defineEmits<{
  'update:perPage': [value: number]
  'fetch': [page: number]
  'open-users-modal': [role: Role]
  'open-perm-modal': [role: Role]
  'open-edit-modal': [role: Role]
  'delete': [id: number]
}>()

const internalPerPage = computed({
  get: () => props.perPage,
  set: (value) => emit('update:perPage', value)
})
</script>

<template>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div v-if="loading" class="p-16 flex flex-col items-center justify-center gap-4 text-slate-500">
      <Loader2 class="animate-spin" :size="32" />
      <p class="font-medium">Loading roles...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error && roles.length === 0" class="p-16 text-center">
      <AlertCircle class="mx-auto mb-3 text-red-400" :size="32" />
      <p class="text-red-600 font-medium">{{ error }}</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="roles.length === 0" class="p-16 text-center text-slate-500">
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
          <tr v-for="role in roles" :key="role.id" class="hover:bg-slate-50/50 transition-colors">
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
                @click="emit('open-users-modal', role)"
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
                  @click="emit('open-perm-modal', role)"
                  class="p-2 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-all"
                  title="Manage Permissions"
                >
                  <Key :size="16" />
                </button>
                <button 
                  @click="emit('open-edit-modal', role)"
                  class="p-2 text-slate-400 hover:text-[#252578] hover:bg-blue-50 rounded-lg transition-all"
                  title="Edit Role"
                >
                  <Edit2 :size="16" />
                </button>
                <button 
                  @click="emit('delete', role.id)"
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

    <!-- Pagination -->
    <div v-if="pagination && pagination.total > 0" class="px-6 py-4 bg-slate-50/50 border-t border-slate-100 flex items-center justify-between">
      <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">
        Showing <span class="text-slate-900 font-bold">{{ roles.length }}</span> of <span class="text-slate-900 font-bold">{{ pagination.total }}</span> roles
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
