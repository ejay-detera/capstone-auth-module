import axios from 'axios'

const api = axios.create({
  baseURL: '',
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
})

import { encryptPayload } from './encryption'

// Request interceptor for auth tokens and encryption
api.interceptors.request.use((config) => {

  // Payload Encryption for POST, PUT, PATCH
  // Skip encryption for auth endpoints — login and refresh must send plain JSON
  const methodsWithBody = ['post', 'put', 'patch']
  const skipEncryptionUrls = ['/api/login', '/api/refresh']
  const shouldSkipEncryption = skipEncryptionUrls.some(url => config.url?.includes(url))
  if (config.method && methodsWithBody.includes(config.method.toLowerCase()) && config.data && !shouldSkipEncryption) {
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

// Track whether a refresh is already in-flight to avoid parallel refresh calls
let isRefreshing = false
let pendingRequests: Array<(token: string) => void> = []

function clearAuthAndRedirect() {
  localStorage.removeItem('user')
  window.location.href = '/'
}

// Global response interceptor: try token refresh before logging the user out
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config

    // Only attempt refresh on 401s that aren't the refresh/login endpoints themselves
    // and haven't already been retried once.
    if (
      error.response?.status === 401 &&
      !originalRequest._retried &&
      !originalRequest.url?.includes('/api/refresh') &&
      !originalRequest.url?.includes('/api/login') &&
      !originalRequest.url?.includes('/api/logout')
    ) {
      originalRequest._retried = true

      if (isRefreshing) {
        // Queue this request until the refresh completes
        return new Promise((resolve) => {
          pendingRequests.push((newToken: string) => {
            originalRequest.headers.Authorization = `Bearer ${newToken}`
            resolve(api(originalRequest))
          })
        })
      }

      isRefreshing = true

      try {
        // Use a plain axios call (not `api`) to avoid the encryption interceptor
        await axios.post('/api/refresh', {}, { withCredentials: true })

        // Flush all queued requests
        pendingRequests.forEach((cb) => cb('')) // token not needed manually anymore
        pendingRequests = []

        // Retry the original request
        return api(originalRequest)
      } catch {
        // Refresh failed — the session is truly expired; send user to login
        pendingRequests = []
        clearAuthAndRedirect()
      } finally {
        isRefreshing = false
      }
    }

    return Promise.reject(error)
  }
)

export default api
