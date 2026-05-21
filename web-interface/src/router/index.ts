import { createRouter, createWebHistory } from 'vue-router'
import LandingPage from '../components/LandingPage.vue'
import LoginView from '../components/LoginView.vue'

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
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('../components/ForgotPassword.vue')
  },
  {
    path: '/reset-password',
    name: 'reset-password',
    component: () => import('../components/ResetPassword.vue')
  },
  {
    path: '/verify-email',
    name: 'verify-email',
    component: () => import('../components/EmailVerification.vue')
  },
  {
    path: '/admin',
    component: () => import('../components/AdminLayout.vue'),
    redirect: '/admin/users',
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      {
        path: 'users',
        name: 'admin-user-list',
        component: () => import('../components/UserList.vue')
      },
      {
        path: 'users/create',
        name: 'admin-user-create',
        component: () => import('../components/UserCreate.vue')
      },
      {
        path: 'roles',
        name: 'admin-role-management',
        component: () => import('../components/RoleManagement.vue')
      },
      {
        path: 'permissions',
        name: 'admin-permission-management',
        component: () => import('../components/PermissionManagement.vue')
      },
      {
        path: 'departments',
        name: 'admin-department-management',
        component: () => import('../components/DepartmentManagement.vue')
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('access_token')
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  const requiresAdmin = to.matched.some(record => record.meta.requiresAdmin)

  // Extract role name safely handling nested profile structure
  const roleName = user?.profile?.role?.name || user?.role || ''

  if (requiresAuth && !token) {
    next({ name: 'login' })
  } else if (requiresAdmin && roleName !== 'IT Admin') {
    // Strictly isolate auth-module interface to IT Admin only
    alert('Access Denied: Only IT Admin can access the authentication module interface.')
    localStorage.removeItem('access_token')
    localStorage.removeItem('user')
    next({ name: 'login' })
  } else {
    next()
  }
})

export default router
