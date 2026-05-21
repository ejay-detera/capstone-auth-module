<script setup lang="ts">
import { useRouter } from 'vue-router'
import { 
  Users, 
  LayoutDashboard, 
  UserPlus,
  Shield,
  Key,
  Building2,
  LogOut
} from 'lucide-vue-next'

import { onMounted, onUnmounted } from 'vue'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const { logout, clearLocalAuth } = useAuth()

const handleLogout = async () => {
  await logout()
  router.push('/')
}

// Idle logout logic (2 hours)
const IDLE_TIMEOUT = 2 * 60 * 60 * 1000
let idleTimer: any = null

const resetIdleTimer = () => {
  if (idleTimer) clearTimeout(idleTimer)
  idleTimer = setTimeout(() => {
    console.log('User idle for 2 hours, logging out...')
    clearLocalAuth()
    router.push('/')
  }, IDLE_TIMEOUT)
}

onMounted(() => {
  const events = ['mousemove', 'keydown', 'click', 'scroll', 'mousedown', 'touchstart']
  events.forEach(event => window.addEventListener(event, resetIdleTimer))
  resetIdleTimer()
})

onUnmounted(() => {
  const events = ['mousemove', 'keydown', 'click', 'scroll', 'mousedown', 'touchstart']
  events.forEach(event => window.removeEventListener(event, resetIdleTimer))
  if (idleTimer) clearTimeout(idleTimer)
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 font-sans">
    <!-- Sidebar (Fixed overlay) -->
    <aside class="fixed top-0 left-0 h-screen w-[88px] hover:w-64 bg-[#252578] text-white transition-all duration-300 ease-in-out rounded-r-3xl shadow-[0_8px_30px_rgba(37,37,120,0.2)] flex flex-col overflow-hidden group z-20">
      <nav class="flex flex-col gap-4 mt-24 px-3">
        <router-link 
          to="/admin" 
          class="flex items-center gap-4 px-5 py-3 rounded-2xl transition-all duration-200 w-full hover:bg-white/10 whitespace-nowrap"
          active-class="bg-[#3b82f6] shadow-[0_0_15px_rgba(59,130,246,0.5)] !hover:bg-[#3b82f6]"
        >
          <LayoutDashboard :size="24" class="flex-shrink-0" />
          <span class="font-semibold text-sm opacity-0 group-hover:opacity-100 transition-opacity duration-300 delay-100">
            Dashboard
          </span>
        </router-link>
        
        <router-link 
          to="/admin/users/create" 
          class="flex items-center gap-4 px-5 py-3 rounded-2xl transition-all duration-200 w-full hover:bg-white/10 whitespace-nowrap"
          active-class="bg-[#3b82f6] shadow-[0_0_15px_rgba(59,130,246,0.5)] !hover:bg-[#3b82f6]"
        >
          <UserPlus :size="24" class="flex-shrink-0" />
          <span class="font-semibold text-sm opacity-0 group-hover:opacity-100 transition-opacity duration-300 delay-100">
            Create User
          </span>
        </router-link>

        <router-link 
          to="/admin/users" 
          class="flex items-center gap-4 px-5 py-3 rounded-2xl transition-all duration-200 w-full hover:bg-white/10 whitespace-nowrap"
          active-class="bg-[#3b82f6] shadow-[0_0_15px_rgba(59,130,246,0.5)] !hover:bg-[#3b82f6]"
        >
          <Users :size="24" class="flex-shrink-0" />
          <span class="font-semibold text-sm opacity-0 group-hover:opacity-100 transition-opacity duration-300 delay-100">
            User Management
          </span>
        </router-link>

        <router-link 
          to="/admin/roles" 
          class="flex items-center gap-4 px-5 py-3 rounded-2xl transition-all duration-200 w-full hover:bg-white/10 whitespace-nowrap"
          active-class="bg-[#3b82f6] shadow-[0_0_15px_rgba(59,130,246,0.5)] !hover:bg-[#3b82f6]"
        >
          <Shield :size="24" class="flex-shrink-0" />
          <span class="font-semibold text-sm opacity-0 group-hover:opacity-100 transition-opacity duration-300 delay-100">
            Role Management
          </span>
        </router-link>

        <router-link 
          to="/admin/permissions" 
          class="flex items-center gap-4 px-5 py-3 rounded-2xl transition-all duration-200 w-full hover:bg-white/10 whitespace-nowrap"
          active-class="bg-[#3b82f6] shadow-[0_0_15px_rgba(59,130,246,0.5)] !hover:bg-[#3b82f6]"
        >
          <Key :size="24" class="flex-shrink-0" />
          <span class="font-semibold text-sm opacity-0 group-hover:opacity-100 transition-opacity duration-300 delay-100">
            Permissions
          </span>
        </router-link>

        <router-link 
          to="/admin/departments" 
          class="flex items-center gap-4 px-5 py-3 rounded-2xl transition-all duration-200 w-full hover:bg-white/10 whitespace-nowrap"
          active-class="bg-[#3b82f6] shadow-[0_0_15px_rgba(59,130,246,0.5)] !hover:bg-[#3b82f6]"
        >
          <Building2 :size="24" class="flex-shrink-0" />
          <span class="font-semibold text-sm opacity-0 group-hover:opacity-100 transition-opacity duration-300 delay-100">
            Departments
          </span>
        </router-link>
      </nav>
    </aside>

    <!-- Header (Fixed overlay) -->
    <header class="fixed top-0 left-0 w-full bg-white/80 backdrop-blur-md border-b border-gray-200 z-10 px-6 py-4 flex items-center justify-between shadow-sm">
      <!-- Logo & Title -->
      <div class="flex items-center gap-4 pl-24">
         <div class="flex items-center gap-3">
           <span class="px-3 py-1 bg-gray-100 rounded-lg text-sm border border-gray-300 font-bold text-slate-700">[ LOGO ]</span>
           <div class="hidden md:flex flex-col text-sm leading-tight">
             <span class="text-[#252578] font-semibold">SCIENTIFIC BIOTECH</span>
             <span class="text-xs text-gray-500 font-normal">SPECIALTIES, INC.</span>
           </div>
         </div>
      </div>

      <!-- Right Actions -->
      <div class="flex items-center gap-4">
        <button class="relative p-2 text-[#252578] hover:bg-gray-100 rounded-full transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
             <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
          <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white"></span>
        </button>

        <button 
          @click="handleLogout"
          class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-sm font-semibold transition-all hover:shadow-sm"
          title="Logout"
        >
          <LogOut :size="16" class="text-slate-500" />
          <span>Logout</span>
        </button>
      </div>
    </header>

    <!-- Main Content -->
    <!-- ml-[88px] accounts for the collapsed sidebar width. pt-24 accounts for the fixed header height. -->
    <main class="ml-[88px] pt-24 p-8">
      <div class="max-w-7xl w-full mx-auto">
        <router-view v-slot="{ Component }">
          <transition 
            name="fade" 
            mode="out-in"
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
          >
            <component :is="Component" />
          </transition>
        </router-view>
      </div>
    </main>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
