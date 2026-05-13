<script setup lang="ts">
import { ref, reactive } from 'vue'
import axios from 'axios'
import { Eye, EyeOff, Loader2 } from 'lucide-vue-next'

const form = reactive({
  username: '',
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
    const response = await axios.post('http://localhost:8000/api/login', form, {
        withCredentials: true
    })
    
    const { access_token, user } = response.data
    localStorage.setItem('access_token', access_token)
    localStorage.setItem('user', JSON.stringify(user))
    
    // Redirect or update state
    alert('Login successful!')
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
        <!-- Username Field -->
        <div class="space-y-2">
          <label for="username" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
            Username
          </label>
          <input
            id="username"
            v-model="form.username"
            type="text"
            placeholder="johndoe"
            class="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm ring-offset-white file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            :class="{ 'border-red-500': errors.username }"
            required
          />
          <p v-if="errors.username" class="text-sm font-medium text-red-500">
            {{ errors.username[0] }}
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
        <div v-if="generalError" class="p-3 rounded-md bg-red-50 border border-red-200 text-sm font-medium text-red-600">
          {{ generalError }}
        </div>

        <!-- Submit Button -->
        <button
          type="submit"
          :disabled="isLoading"
          class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-white transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-slate-900 text-slate-50 hover:bg-slate-900/90 h-10 px-4 py-2 w-full"
        >
          <Loader2 v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" />
          {{ isLoading ? 'Logging in...' : 'Sign In' }}
        </button>
      </form>
    </div>
  </div>
</template>
