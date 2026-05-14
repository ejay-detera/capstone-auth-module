import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../components/LoginView.vue'

const routes = [
  {
    path: '/',
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

  if (to.meta.requiresAuth && !token) {
    next({ name: 'login' })
  } else {
    next()
  }
})

export default router
