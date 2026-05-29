<script setup lang="ts">
import { ref, watch } from 'vue'
import { X, AlertCircle, Loader2 } from 'lucide-vue-next'

const props = defineProps<{
  modelValue: boolean
  mode: 'create' | 'edit'
  initialData: { id: number | null, name: string, slug: string, description: string }
  submitting: boolean
  error: string | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'submit': [payload: { id: number | null, name: string, slug: string, description: string }]
}>()

const form = ref({
  id: null as number | null,
  name: '',
  slug: '',
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
    
    <div class="relative bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
      <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-xl font-bold text-slate-900">
          {{ mode === 'create' ? 'Add New Permission' : 'Edit Permission' }}
        </h2>
        <button @click="closeModal" class="p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-all">
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
            @click="closeModal"
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
            {{ submitting ? 'Saving...' : (mode === 'create' ? 'Add Permission' : 'Save Changes') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
