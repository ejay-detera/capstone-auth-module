<script setup lang="ts">
import { useRouter } from 'vue-router'
import { 
  Users, 
  LayoutDashboard, 
  LogOut, 
  Settings,
  Bell,
  Search,
  UserPlus,
  Shield,
  Key,
  Building2
} from 'lucide-vue-next'

import { onMounted, onUnmounted } from 'vue'

const router = useRouter()
const user = JSON.parse(localStorage.getItem('user') || '{}')

const handleLogout = () => {
  localStorage.removeItem('access_token')
  localStorage.removeItem('session_id')
  localStorage.removeItem('user')
  router.push('/')
}

// Idle logout logic (2 hours)
const IDLE_TIMEOUT = 2 * 60 * 60 * 1000
let idleTimer: any = null

const resetIdleTimer = () => {
  if (idleTimer) clearTimeout(idleTimer)
  idleTimer = setTimeout(() => {
    console.log('User idle for 2 hours, logging out...')
    handleLogout()
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
  <div class="min-h-screen bg-slate-50 flex font-sans">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-slate-200 flex flex-col sticky top-0 h-screen">
      <div class="p-6 border-b border-slate-100">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-slate-900 rounded-xl flex items-center justify-center">
            <span class="text-white font-bold text-xl">{{ user?.email?.substring(0, 1).toUpperCase() || 'U' }}</span>
          </div>
          <div>
            <h2 class="font-bold text-slate-900 leading-none">Demo Frontend</h2>
            <p class="text-[10px] text-slate-500 font-medium uppercase tracking-wider mt-1">Auth Module</p>
          </div>
        </div>
      </div>

      <nav class="flex-1 p-4 space-y-1">
        <router-link 
          to="/admin" 
          class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-slate-600 rounded-xl hover:bg-slate-50 hover:text-slate-900 transition-all group"
          active-class="bg-slate-900 !text-white shadow-lg shadow-slate-900/10"
        >
          <LayoutDashboard :size="20" class="group-hover:scale-110 transition-transform" />
          Dashboard
        </router-link>
        
        <router-link 
          to="/admin/users/create" 
          class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-slate-600 rounded-xl hover:bg-slate-50 hover:text-slate-900 transition-all group"
          active-class="bg-slate-900 !text-white shadow-lg shadow-slate-900/10"
        >
          <UserPlus :size="20" class="group-hover:scale-110 transition-transform" />
          Create User
        </router-link>

        <router-link 
          to="/admin/users" 
          class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-slate-600 rounded-xl hover:bg-slate-50 hover:text-slate-900 transition-all group"
          active-class="bg-slate-900 !text-white shadow-lg shadow-slate-900/10"
        >
          <Users :size="20" class="group-hover:scale-110 transition-transform" />
          User Management
        </router-link>

        <router-link 
          to="/admin/roles" 
          class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-slate-600 rounded-xl hover:bg-slate-50 hover:text-slate-900 transition-all group"
          active-class="bg-slate-900 !text-white shadow-lg shadow-slate-900/10"
        >
          <Shield :size="20" class="group-hover:scale-110 transition-transform" />
          Role Management
        </router-link>

        <router-link 
          to="/admin/permissions" 
          class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-slate-600 rounded-xl hover:bg-slate-50 hover:text-slate-900 transition-all group"
          active-class="bg-slate-900 !text-white shadow-lg shadow-slate-900/10"
        >
          <Key :size="20" class="group-hover:scale-110 transition-transform" />
          Permission Management
        </router-link>

        <router-link 
          to="/admin/departments" 
          class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-slate-600 rounded-xl hover:bg-slate-50 hover:text-slate-900 transition-all group"
          active-class="bg-slate-900 !text-white shadow-lg shadow-slate-900/10"
        >
          <Building2 :size="20" class="group-hover:scale-110 transition-transform" />
          Department Management
        </router-link>
      </nav>

      <div class="p-4 border-t border-slate-100">
        <button 
          @click="handleLogout"
          class="flex items-center gap-3 w-full px-4 py-3 text-sm font-medium text-red-600 rounded-xl hover:bg-red-50 transition-all group"
        >
          <LogOut :size="20" class="group-hover:translate-x-1 transition-transform" />
          Logout
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-w-0">
      <!-- Header -->
      <header class="h-16 bg-white border-b border-slate-200 px-8 flex items-center justify-between sticky top-0 z-10">
        <div class="flex items-center bg-slate-100 px-4 py-2 rounded-xl w-96 group focus-within:bg-white focus-within:ring-2 focus-within:ring-slate-900/10 border border-transparent focus-within:border-slate-200 transition-all">
          <Search :size="18" class="text-slate-400 mr-2" />
          <input 
            type="text" 
            placeholder="Search users, roles, activities..." 
            class="bg-transparent border-none text-sm focus:outline-none w-full text-slate-900 placeholder:text-slate-500"
          />
        </div>

        <div class="flex items-center gap-4">
          <button class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-slate-100 text-slate-500 relative transition-colors">
            <Bell :size="20" />
            <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
          </button>
          
          <div class="h-8 w-[1px] bg-slate-200 mx-2"></div>
          
          <div class="flex items-center gap-3">
            <div class="text-right hidden sm:block">
              <p class="text-sm font-bold text-slate-900 leading-none">{{ user?.email || 'User' }}</p>
              <p class="text-[11px] text-slate-500 font-medium mt-1">{{ user?.profile?.role?.name || user?.role || 'Unknown Role' }}</p>
            </div>
            <div class="w-10 h-10 bg-gradient-to-tr from-slate-200 to-slate-100 rounded-xl border border-slate-200 flex items-center justify-center font-bold text-slate-700 shadow-sm">
              {{ user?.email?.substring(0, 2).toUpperCase() || 'U' }}
            </div>
          </div>
        </div>
      </header>

      <!-- Content Area -->
      <div class="p-8 max-w-7xl w-full mx-auto">
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
