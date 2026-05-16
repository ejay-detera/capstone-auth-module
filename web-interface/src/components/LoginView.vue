<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/lib/api'
import { Eye, EyeOff, Loader2 } from 'lucide-vue-next'

const router = useRouter()

const form = reactive({
  email: '',
  password: ''
})

const showPassword = ref(false)
const isLoading = ref(false)
const errors = ref<Record<string, string[]>>({})
const generalError = ref('')

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const handleLogin = async () => {
  isLoading.value = true
  errors.value = {}
  generalError.value = ''

  try {
    const response = await api.post('/api/login', form)
    
    const { access_token, user, session_id } = response.data
    localStorage.setItem('access_token', access_token)
    localStorage.setItem('session_id', session_id)
    localStorage.setItem('user', JSON.stringify(user))
    
    const roleName = user.profile?.role?.name || user.role || ''

    if (roleName === 'IT Admin') {
      router.push('/admin')
    } else if (roleName === 'Admin') {
      window.location.href = '/crms/admin/dashboard'
    } else if (roleName === 'Manager') {
      window.location.href = '/crms/manager/dashboard'
    } else if (roleName === 'Sales' || roleName === 'Employee') {
      window.location.href = '/crms/sales/dashboard'
    } else {
      generalError.value = 'Unrecognized role. Please contact IT Support.'
      localStorage.removeItem('access_token')
      localStorage.removeItem('session_id')
      localStorage.removeItem('user')
    }
  } catch (error: any) {
    if (error.response) {
      if (error.response.status === 422) {
        errors.value = error.response.data.errors
      } else if (error.response.status === 429) {
        generalError.value = error.response.data.message
      } else {
        generalError.value = error.response.data.message || 'An error occurred during login.'
      }
    } else {
      generalError.value = 'Cannot connect to the server.'
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 px-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-lg border border-slate-200 p-8">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Welcome back</h1>
        <p class="text-slate-500 mt-2">Please enter your credentials to log in</p>
      </div>

      <form @submit.prevent="handleLogin" class="space-y-6">
        <!-- Email Field -->
        <div class="space-y-2">
          <label for="email" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
            Email Address
          </label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            placeholder="name@example.com"
            class="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm ring-offset-white file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            :class="{ 'border-red-500': errors.email }"
            required
          />
          <p v-if="errors.email" class="text-sm font-medium text-red-500">
            {{ errors.email[0] }}
          </p>
        </div>

        <!-- Password Field -->
        <div class="space-y-2">
          <label for="password" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
            Password
          </label>
          <div class="relative">
            <input
              id="password"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="••••••••"
              class="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm ring-offset-white file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 pr-10"
              :class="{ 'border-red-500': errors.password }"
              required
            />
            <button
              type="button"
              @click="togglePassword"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-700 transition-colors"
            >
              <Eye v-if="!showPassword" :size="18" />
              <EyeOff v-else :size="18" />
            </button>
          </div>
          <p v-if="errors.password" class="text-sm font-medium text-red-500">
            {{ errors.password[0] }}
          </p>
        </div>

        <!-- General Error -->
        <div v-if="generalError" class="p-4 rounded-xl bg-red-50 border border-red-100 text-sm font-medium text-red-700 animate-in fade-in slide-in-from-top-1">
          {{ generalError }}
        </div>

        <!-- Submit Button -->
        <button
          type="submit"
          :disabled="isLoading"
          class="inline-flex items-center justify-center whitespace-nowrap rounded-xl text-sm font-bold ring-offset-white transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-slate-900 text-slate-50 hover:bg-slate-800 h-12 px-4 py-2 w-full shadow-lg shadow-slate-900/10"
        >
          <Loader2 v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" />
          {{ isLoading ? 'Signing in...' : 'Sign In' }}
        </button>

        <div class="text-center mt-6">
          <router-link to="/forgot-password" class="text-sm font-semibold text-slate-600 hover:text-slate-900 transition-colors">
            Forgot your password?
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>
