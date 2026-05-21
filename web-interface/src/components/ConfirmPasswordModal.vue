<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import { Loader2, X, Eye, EyeOff, Lock } from 'lucide-vue-next'

const props = withDefaults(
  defineProps<{
    show: boolean
    title: string
    description?: string
    confirmLabel?: string
    isSubmitting?: boolean
    error?: string
  }>(),
  {
    description: 'Please confirm your identity by entering your password.',
    confirmLabel: 'Confirm',
    isSubmitting: false,
    error: ''
  }
)

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'confirm', password: string): void
}>()

const password = ref('')
const showPassword = ref(false)
const inputRef = ref<HTMLInputElement | null>(null)

// Focus input on open
watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      password.value = ''
      showPassword.value = false
      nextTick(() => {
        inputRef.value?.focus()
      })
    }
  }
)

const handleCancel = () => {
  if (props.isSubmitting) return
  emit('close')
}

const handleConfirm = () => {
  if (!password.value || props.isSubmitting) return
  emit('confirm', password.value)
}

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
    <!-- Backdrop with blur -->
    <div 
      class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity duration-300"
      @click="handleCancel"
    ></div>

    <!-- Modal Card -->
    <div 
      class="relative bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden border border-slate-100 animate-in fade-in zoom-in duration-200"
      role="dialog"
      aria-modal="true"
    >
      <!-- Header -->
      <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
        <div>
          <h3 class="text-lg font-bold text-slate-900">{{ title }}</h3>
          <p class="text-xs text-slate-500 font-medium mt-0.5">{{ description }}</p>
        </div>
        <button 
          @click="handleCancel"
          :disabled="isSubmitting"
          class="p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-all disabled:opacity-50"
        >
          <X :size="18" />
        </button>
      </div>

      <!-- Content / Form -->
      <form @submit.prevent="handleConfirm" class="p-6 space-y-4">
        <div class="space-y-2">
          <label for="verification-password" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-1">
            Admin Password
          </label>
          <div class="relative">
            <Lock class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" :size="18" />
            <input 
              ref="inputRef"
              id="verification-password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Enter your password..." 
              required
              :disabled="isSubmitting"
              class="w-full h-11 pl-11 pr-12 rounded-xl bg-slate-50 border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition-all"
            />
            <!-- Toggle password visibility -->
            <button 
              type="button"
              @click="togglePasswordVisibility"
              :disabled="isSubmitting"
              class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-slate-400 hover:text-slate-900 transition-colors"
            >
              <EyeOff v-if="showPassword" :size="18" />
              <Eye v-else :size="18" />
            </button>
          </div>
        </div>

        <!-- Error feedback -->
        <div v-if="error" class="text-xs font-bold text-red-600 bg-red-50 border border-red-100 px-4 py-3 rounded-xl flex items-start gap-2">
          <span>{{ error }}</span>
        </div>

        <!-- Actions -->
        <div class="pt-2 flex gap-3">
          <button 
            type="button"
            @click="handleCancel"
            :disabled="isSubmitting"
            class="flex-1 h-11 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-all border border-slate-200 disabled:opacity-50"
          >
            Cancel
          </button>
          <button 
            type="submit"
            :disabled="isSubmitting || !password"
            class="flex-1 h-11 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            <Loader2 v-if="isSubmitting" class="animate-spin" :size="18" />
            <span>{{ isSubmitting ? 'Confirming...' : confirmLabel }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
