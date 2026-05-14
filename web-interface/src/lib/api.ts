import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
})

// You can add interceptors here later (e.g., for automatic token handling)
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('access_token')
  const sessionId = localStorage.getItem('session_id')

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  if (sessionId) {
    config.headers['X-Session-ID'] = sessionId
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
