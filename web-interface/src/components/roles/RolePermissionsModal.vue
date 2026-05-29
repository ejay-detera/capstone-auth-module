<script setup lang="ts">
import { computed } from 'vue'
import { X, Loader2, AlertCircle, Check } from 'lucide-vue-next'
import type { Role, Permission } from '@/types'

const props = defineProps<{
  modelValue: boolean
  role: Role | null
  allPermissions: Permission[]
  selectedPermissionIds: number[]
  loading: boolean
  submitting: boolean
  error: string | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'update:selectedPermissionIds': [value: number[]]
  'submit': []
}>()

const internalSelectedIds = computed({
  get: () => props.selectedPermissionIds,
  set: (value) => emit('update:selectedPermissionIds', value)
})

const closeModal = () => {
  emit('update:modelValue', false)
}

const handleSubmit = () => {
  emit('submit')
}

const togglePermission = (permId: number) => {
  const current = [...internalSelectedIds.value]
  const idx = current.indexOf(permId)
  if (idx >= 0) {
    current.splice(idx, 1)
  } else {
    current.push(permId)
  }
  internalSelectedIds.value = current
}
</script>

<template>
  <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal"></div>
    
    <div class="relative bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
      <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
        <div>
          <h2 class="text-xl font-bold text-slate-900">Manage Permissions</h2>
          <p class="text-xs text-slate-500 font-medium mt-0.5">Role: <span class="text-[#252578]">{{ role?.name }}</span></p>
        </div>
        <button @click="closeModal" class="p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-all">
          <X :size="20" />
        </button>
      </div>

      <div class="p-6">
        <div v-if="loading" class="py-12 flex flex-col items-center justify-center gap-4 text-slate-500">
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
              :class="internalSelectedIds.includes(perm.id) ? 'bg-blue-50/50 border-blue-100' : ''"
            >
              <div 
                class="w-5 h-5 rounded-md border-2 flex items-center justify-center flex-shrink-0 transition-all"
                :class="internalSelectedIds.includes(perm.id) ? 'bg-[#252578] border-[#252578]' : 'border-slate-300'"
                @click.prevent="togglePermission(perm.id)"
              >
                <Check v-if="internalSelectedIds.includes(perm.id)" :size="12" class="text-white" />
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
              @click="closeModal"
              class="flex-1 h-11 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-all border border-slate-200"
            >
              Cancel
            </button>
            <button 
              @click="handleSubmit"
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
</template>
