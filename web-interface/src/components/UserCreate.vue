<script setup lang="ts">
import { reactive, onMounted } from 'vue'
import { useUsers } from '@/composables/useUsers'
import { Loader2, UserPlus, CheckCircle2, AlertCircle } from 'lucide-vue-next'

const {
  roles,
  departments,
  isLoadingData,
  isSubmitting,
  isSuccess,
  errors,
  generalError,
  fetchCreateFormData,
  createUser,
} = useUsers()

const form = reactive({
  first_name: '',
  last_name: '',
  email: '',
  role_id: '',
  department_id: ''
})

onMounted(fetchCreateFormData)

const handleSubmit = async () => {
  const success = await createUser(form)
  if (success) {
    // Reset form
    Object.keys(form).forEach(key => (form[key as keyof typeof form] = ''))
  }
}
</script>

<template>
  <div class="space-y-8 animate-in fade-in duration-500">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Create New User</h1>
        <p class="text-slate-500 mt-1">Onboard a new employee by creating their account.</p>
      </div>
      <div class="w-12 h-12 bg-gradient-to-r from-[#252578] to-[#3b82f6] rounded-2xl flex items-center justify-center text-white shadow-lg">
        <UserPlus :size="24" />
      </div>
    </div>

    <div v-if="isLoadingData" class="flex items-center justify-center h-64 bg-white rounded-3xl border border-slate-200">
      <Loader2 class="h-8 w-8 text-slate-400 animate-spin" />
    </div>

    <form v-else @submit.prevent="handleSubmit" class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <!-- Left Column: Personal Info -->
      <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm space-y-6">
        <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
          <span class="w-1 h-5 bg-slate-900 rounded-full"></span>
          Personal Information
        </h3>
        
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-700">First Name</label>
            <input 
              v-model="form.first_name" 
              type="text" 
              placeholder="John"
              class="flex h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition-all"
              :class="{ 'border-red-300 bg-red-50': errors.first_name }"
              required
            />
            <p v-if="errors.first_name" class="text-[10px] font-bold text-red-500 uppercase tracking-wider">{{ errors.first_name[0] }}</p>
          </div>
          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-700">Last Name</label>
            <input 
              v-model="form.last_name" 
              type="text" 
              placeholder="Doe"
              class="flex h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition-all"
              :class="{ 'border-red-300 bg-red-50': errors.last_name }"
              required
            />
            <p v-if="errors.last_name" class="text-[10px] font-bold text-red-500 uppercase tracking-wider">{{ errors.last_name[0] }}</p>
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-semibold text-slate-700">Email Address</label>
          <input 
            v-model="form.email" 
            type="email" 
            placeholder="john.doe@company.com"
            class="flex h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition-all"
            :class="{ 'border-red-300 bg-red-50': errors.email }"
            required
          />
          <p v-if="errors.email" class="text-[10px] font-bold text-red-500 uppercase tracking-wider">{{ errors.email[0] }}</p>
        </div>

      </div>

      <!-- Right Column: Role & Dept -->
      <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm space-y-6">
        <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
          <span class="w-1 h-5 bg-slate-900 rounded-full"></span>
          Organization Access
        </h3>

        <div class="space-y-2">
          <label class="text-sm font-semibold text-slate-700">Role</label>
          <select 
            v-model="form.role_id"
            class="flex h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition-all appearance-none cursor-pointer"
            :class="{ 'border-red-300 bg-red-50': errors.role_id }"
            required
          >
            <option value="" disabled>Select a role</option>
            <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
          </select>
          <p v-if="errors.role_id" class="text-[10px] font-bold text-red-500 uppercase tracking-wider">{{ errors.role_id[0] }}</p>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-semibold text-slate-700">Department</label>
          <select 
            v-model="form.department_id"
            class="flex h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition-all appearance-none cursor-pointer"
            :class="{ 'border-red-300 bg-red-50': errors.department_id }"
            required
          >
            <option value="" disabled>Select a department</option>
            <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
          </select>
          <p v-if="errors.department_id" class="text-[10px] font-bold text-red-500 uppercase tracking-wider">{{ errors.department_id[0] }}</p>
        </div>

        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 mt-4">
          <div class="flex gap-3">
            <AlertCircle class="text-slate-400 h-5 w-5 shrink-0 mt-0.5" />
            <p class="text-xs text-slate-500 leading-relaxed">
              Creating this account will automatically generate a temporary password and send a welcome email to the user. They will be required to change their password on first login.
            </p>
          </div>
        </div>

        <!-- Feedback & Action -->
        <div class="pt-4">
          <div v-if="isSuccess" class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center gap-3 text-emerald-700 animate-in slide-in-from-bottom-2">
            <CheckCircle2 :size="20" />
            <span class="text-sm font-semibold">User created successfully! Welcome email queued.</span>
          </div>

          <div v-if="generalError" class="mb-4 p-4 rounded-xl bg-red-50 border border-red-100 flex items-center gap-3 text-red-700 animate-in slide-in-from-bottom-2">
            <AlertCircle :size="20" />
            <span class="text-sm font-semibold">{{ generalError }}</span>
          </div>

          <button 
            type="submit" 
            :disabled="isSubmitting"
            class="w-full h-12 bg-gradient-to-r from-[#252578] to-[#3b82f6] text-white rounded-xl text-sm font-semibold hover:shadow-lg transition-all flex items-center justify-center gap-2 disabled:opacity-50"
          >
            <Loader2 v-if="isSubmitting" class="animate-spin h-5 w-5" />
            {{ isSubmitting ? 'Creating Account...' : 'Create Account' }}
          </button>
        </div>
      </div>
    </form>
  </div>
</template>
