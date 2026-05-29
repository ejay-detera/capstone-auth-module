import { createRouter, createWebHistory } from 'vue-router'
import LandingPage from '../views/LandingPage.vue'
import LoginView from '../views/auth/LoginView.vue'

const routes = [
  {
    path: '/',
    name: 'landing',
    component: LandingPage
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView
  },
  {
    path: '/logout',
    name: 'logout',
    component: () => import('../views/auth/LogoutView.vue')
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('../views/auth/ForgotPassword.vue')
  },
  {
    path: '/reset-password',
    name: 'reset-password',
    component: () => import('../views/auth/ResetPassword.vue')
  },
  {
    path: '/verify-email',
    name: 'verify-email',
    component: () => import('../views/auth/EmailVerification.vue')
  },
  {
    path: '/home',
    name: 'home',
    component: () => import('../views/HomePage.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/admin',
    component: () => import('../layouts/AdminLayout.vue'),
    redirect: '/admin/users',
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      {
        path: 'users',
        name: 'admin-user-list',
        component: () => import('../components/users/UserManagement.vue')
      },
      {
        path: 'users/create',
        name: 'admin-user-create',
        component: () => import('../components/users/UserCreate.vue')
      },
      {
        path: 'roles',
        name: 'admin-role-management',
        component: () => import('../components/roles/RoleManagement.vue')
      },
      {
        path: 'permissions',
        name: 'admin-permission-management',
        component: () => import('../components/permissions/PermissionManagement.vue')
      },
      {
        path: 'departments',
        name: 'admin-department-management',
        component: () => import('../components/departments/DepartmentManagement.vue')
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, _from, next) => {
  const userStr = localStorage.getItem('user')
  const user = userStr ? JSON.parse(userStr) : null
  const isAuthenticated = !!user

  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  const requiresAdmin = to.matched.some(record => record.meta.requiresAdmin)

  const guestOnlyRoutes = ['landing', 'login', 'forgot-password', 'reset-password', 'verify-email']

  // Extract role name safely handling nested profile structure
  const roleName = user?.profile?.role?.name || user?.role || ''

  if (guestOnlyRoutes.includes(to.name as string) && isAuthenticated) {
    next({ name: 'home' })
  } else if (requiresAuth && !isAuthenticated) {
    next({ name: 'login' })
  } else if (requiresAdmin && roleName !== 'IT Admin') {
    // Strictly isolate auth-module interface to IT Admin only
    alert('Access Denied: Only IT Admin can access the authentication module interface.')
    localStorage.removeItem('session_id')
    localStorage.removeItem('user')
    next({ name: 'login' })
  } else {
    next()
  }
})

export default router
