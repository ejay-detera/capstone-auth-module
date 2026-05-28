<script setup lang="ts">
import { computed } from 'vue'
import { X, Loader2, AlertCircle, Check } from 'lucide-vue-next'
import type { Permission, Role } from '@/types'

const props = defineProps<{
  modelValue: boolean
  permission: Permission | null
  roles: Role[]
  selectedRoleIds: number[]
  loading: boolean
  submitting: boolean
  error: string | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'update:selectedRoleIds': [value: number[]]
  'submit': []
}>()

const internalSelectedRoleIds = computed({
  get: () => props.selectedRoleIds,
  set: (value) => emit('update:selectedRoleIds', value)
})

const closeModal = () => {
  emit('update:modelValue', false)
}

const handleSubmit = () => {
  emit('submit')
}
</script>

<template>
  <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal"></div>
    
    <div class="relative bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
      <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
        <div>
          <h2 class="text-xl font-bold text-slate-900">Assign to Roles</h2>
          <p class="text-sm text-slate-500 font-medium">Permission: <span class="text-indigo-600">{{ permission?.name }}</span></p>
        </div>
        <button @click="closeModal" class="p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-all">
          <X :size="20" />
        </button>
      </div>

      <div class="p-6">
        <div v-if="loading" class="py-12 flex flex-col items-center justify-center gap-4 text-slate-500">
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
              :class="internalSelectedRoleIds.includes(role.id) 
                ? 'border-[#252578] bg-blue-50/30' 
                : 'border-slate-100 hover:border-slate-200 bg-white'"
            >
              <div 
                class="w-5 h-5 rounded-md border-2 flex items-center justify-center transition-all"
                :class="internalSelectedRoleIds.includes(role.id)
                  ? 'bg-[#252578] border-[#252578] text-white'
                  : 'border-slate-300 bg-white group-hover:border-slate-400'"
              >
                <Check v-if="internalSelectedRoleIds.includes(role.id)" :size="14" stroke-width="3" />
              </div>
              <input 
                type="checkbox" 
                :value="role.id" 
                v-model="internalSelectedRoleIds"
                class="sr-only"
              />
              <span class="text-sm font-bold text-slate-900">{{ role.name }}</span>
            </label>
          </div>

          <div class="pt-4 flex gap-3">
            <button 
              type="button" 
              @click="closeModal"
              class="flex-1 px-6 py-3 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors"
            >
              Cancel
            </button>
            <button 
              @click="handleSubmit"
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
</template>
