<script setup lang="ts">
import { computed } from 'vue'
import { X, Loader2, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import type { Department } from '@/types'

const props = defineProps<{
  modelValue: boolean
  department: Department | null
  loading: boolean
  users: any[]
  pagination: any
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'page-change', page: number): void
}>()

const isVisible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const close = () => {
  isVisible.value = false
}
</script>

<template>
  <div v-if="isVisible" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="close"></div>
    
    <div class="relative bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
      <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
        <div>
          <h2 class="text-xl font-bold text-slate-900">Department Users</h2>
          <p class="text-sm text-slate-500 font-medium">Department: <span class="text-[#252578]">{{ department?.name }}</span></p>
        </div>
        <button @click="close" class="p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-all">
          <X :size="20" />
        </button>
      </div>

      <div class="p-6">
        <div v-if="loading" class="py-12 flex flex-col items-center justify-center gap-4 text-slate-500">
          <Loader2 class="animate-spin" :size="32" />
          <p class="font-medium">Loading users...</p>
        </div>

        <div v-else-if="users.length === 0" class="py-12 text-center text-slate-500">
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
                <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50/50 transition-colors">
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
          <div v-if="pagination.last_page > 1" class="flex items-center justify-between pt-2">
            <p class="text-xs text-slate-500 font-medium">
              Showing {{ users.length }} of {{ pagination.total }} users
            </p>
            <div class="flex items-center gap-2">
              <button 
                @click="emit('page-change', pagination.current_page - 1)"
                :disabled="pagination.current_page === 1"
                class="p-2 border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-50 transition-all"
              >
                <ChevronLeft :size="16" />
              </button>
              <span class="text-sm font-bold text-slate-900 px-2">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
              <button 
                @click="emit('page-change', pagination.current_page + 1)"
                :disabled="pagination.current_page === pagination.last_page"
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
</template>
