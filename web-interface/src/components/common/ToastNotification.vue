<template>
  <div class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none">
    <TransitionGroup name="toast">
      <div 
        v-for="toast in toasts" 
        :key="toast.id"
        class="toast-item pointer-events-auto flex items-center p-4 rounded-lg shadow-lg text-sm min-w-[300px] max-w-md transform transition-all"
        :class="[
          toast.type === 'error' ? 'bg-red-50 text-red-900 border border-red-200 shadow-red-900/10' : '',
          toast.type === 'success' ? 'bg-green-50 text-green-900 border border-green-200 shadow-green-900/10' : '',
          toast.type === 'info' ? 'bg-blue-50 text-blue-900 border border-blue-200 shadow-blue-900/10' : ''
        ]"
      >
        <div class="flex-1 font-medium">{{ toast.message }}</div>
        <button 
          @click="removeToast(toast.id)"
          class="ml-4 opacity-60 hover:opacity-100 transition-opacity text-xl"
          aria-label="Close"
        >
          &times;
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup lang="ts">
import { useToast } from '@/composables/useToast'

const { toasts, removeToast } = useToast()
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.toast-enter-from {
  opacity: 0;
  transform: translateX(30px) scale(0.95);
}
.toast-leave-to {
  opacity: 0;
  transform: scale(0.95);
}
</style>
