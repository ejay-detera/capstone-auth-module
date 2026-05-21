import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.DEV ? '' : (import.meta.env.VITE_API_URL || ''),
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
})

import { encryptPayload } from './encryption'

// Request interceptor for auth tokens and encryption
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('access_token')
  const sessionId = localStorage.getItem('session_id')

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  if (sessionId) {
    config.headers['X-Session-ID'] = sessionId
  }

  // Payload Encryption for POST, PUT, PATCH
  const methodsWithBody = ['post', 'put', 'patch']
  if (config.method && methodsWithBody.includes(config.method.toLowerCase()) && config.data) {
    try {
      config.data = encryptPayload(config.data)
      config.headers['X-Encrypted'] = 'true'
      config.headers['Content-Type'] = 'text/plain' // Send as raw string
    } catch (err) {
      console.error('Encryption failed, sending as plaintext', err)
    }
  }

  return config
})

// Global response interceptor for session expiration
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Clear all auth data
      localStorage.removeItem('access_token')
      localStorage.removeItem('session_id')
      localStorage.removeItem('user')
      
      // Redirect to login
      window.location.href = '/'
    }
    return Promise.reject(error)
  }
)

export default api
