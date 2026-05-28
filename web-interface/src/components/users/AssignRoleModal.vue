<script setup lang="ts">
import { X, Loader2, Shield } from 'lucide-vue-next'
import type { User, Role } from '@/types'

const props = defineProps<{
  modelValue: boolean
  user: User | null
  roles: Role[]
  selectedRoleId: number | string
  isAssigning: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'update:selectedRoleId': [value: number | string]
  'submit': []
}>()

const closeModal = () => {
  emit('update:modelValue', false)
}

const handleRoleSelect = (roleId: number) => {
  emit('update:selectedRoleId', roleId)
}
</script>

<template>
  <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal"></div>
    
    <div class="relative bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
      <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
        <div>
          <h2 class="text-xl font-bold text-slate-900">Assign Role</h2>
          <p class="text-xs text-slate-500 font-medium uppercase tracking-wider mt-0.5">Updating {{ user?.email }}</p>
        </div>
        <button @click="closeModal" class="p-2 text-slate-400 hover:text-slate-900 hover:bg-white rounded-xl transition-all shadow-sm">
          <X :size="20" />
        </button>
      </div>

      <div class="p-6 space-y-6">
        <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
          <div class="w-12 h-12 rounded-xl bg-white flex items-center justify-center font-bold text-slate-700 shadow-sm border border-slate-200">
            {{ (user?.profile?.first_name || 'U').substring(0, 1).toUpperCase() }}{{ (user?.profile?.last_name || '').substring(0, 1).toUpperCase() }}
          </div>
          <div>
            <p class="font-bold text-slate-900">{{ user?.profile?.first_name }} {{ user?.profile?.last_name }}</p>
            <p class="text-xs text-slate-500 font-medium">Current: {{ user?.profile?.role?.name || 'No Role' }}</p>
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-1">Select New Role</label>
          <div class="grid grid-cols-1 gap-2">
            <button 
              v-for="role in roles" 
              :key="role.id"
              @click="handleRoleSelect(role.id)"
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
            @click="closeModal"
            class="flex-1 px-6 py-3 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors"
          >
            Cancel
          </button>
          <button 
            @click="emit('submit')"
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
</template>
