<script setup lang="ts">
import { ref, watch } from 'vue'
import { X, AlertCircle, Loader2 } from 'lucide-vue-next'

const props = defineProps<{
  modelValue: boolean
  mode: 'create' | 'edit'
  initialData: { id: number | null, name: string, description: string }
  submitting: boolean
  error: string | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'submit': [payload: { id: number | null, name: string, description: string }]
}>()

const form = ref({
  id: null as number | null,
  name: '',
  description: ''
})

watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    form.value = { ...props.initialData }
  }
})

const closeModal = () => {
  emit('update:modelValue', false)
}

const handleSubmit = () => {
  emit('submit', { ...form.value })
}
</script>

<template>
  <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal"></div>
    
    <div class="relative bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
      <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
        <div>
          <h2 class="text-xl font-bold text-slate-900">{{ mode === 'create' ? 'Create Role' : 'Edit Role' }}</h2>
          <p class="text-xs text-slate-500 font-medium mt-0.5">{{ mode === 'create' ? 'Add a new role to the system' : 'Update role details' }}</p>
        </div>
        <button @click="closeModal" class="p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-all">
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
            @click="closeModal"
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
            {{ submitting ? 'Saving...' : (mode === 'create' ? 'Create Role' : 'Save Changes') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
