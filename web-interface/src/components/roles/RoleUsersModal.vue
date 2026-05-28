<script setup lang="ts">
import { X, Loader2 } from 'lucide-vue-next'
import type { Role, User } from '@/types'

const props = defineProps<{
  modelValue: boolean
  role: Role | null
  users: User[]
  loading: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const closeModal = () => {
  emit('update:modelValue', false)
}
</script>

<template>
  <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal"></div>
    
    <div class="relative bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
      <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
        <div>
          <h2 class="text-xl font-bold text-slate-900">Role Users</h2>
          <p class="text-sm text-slate-500 font-medium">Role: <span class="text-[#252578]">{{ role?.name }}</span></p>
        </div>
        <button @click="closeModal" class="p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-all">
          <X :size="20" />
        </button>
      </div>

      <div class="p-6">
        <div v-if="loading" class="py-12 flex flex-col items-center justify-center gap-4 text-slate-500">
          <Loader2 class="animate-spin" :size="32" />
          <p class="font-medium">Loading users...</p>
        </div>

        <div v-else-if="users.length === 0" class="py-12 text-center text-slate-500">
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
                <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50/50 transition-colors">
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
</template>
