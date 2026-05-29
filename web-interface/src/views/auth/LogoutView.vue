<template>
  <div class="flex items-center justify-center min-h-screen bg-slate-50">
    <div class="text-center">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto mb-4"></div>
      <p class="text-slate-600 font-medium">Logging out...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const route = useRoute()
const router = useRouter()
const { logout } = useAuth()

onMounted(async () => {
  await logout()
  
  const redirectUri = route.query.redirect_uri as string
  if (redirectUri) {
    window.location.href = redirectUri
  } else {
    router.push({ name: 'login', query: { message: 'Successfully logged out.' } })
  }
})
</script>
