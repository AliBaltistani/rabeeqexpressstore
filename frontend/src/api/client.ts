import axios from 'axios'

// ─── Guest Cart UUID ──────────────────────────────────────────────────────────
// Generates and persists a stable UUID in localStorage so the backend can
// identify a guest's cart consistently across all API calls without relying
// on PHP session cookies (which may not persist in stateless API contexts).
function getOrCreateGuestCartId(): string {
  const key = 'guest_cart_id'
  let id = localStorage.getItem(key)
  if (!id) {
    // Simple UUID v4 generator (no external dep needed)
    id = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
      const r = (Math.random() * 16) | 0
      const v = c === 'x' ? r : (r & 0x3) | 0x8
      return v.toString(16)
    })
    localStorage.setItem(key, id)
  }
  return id
}

const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api/v1',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  withCredentials: true,
})

// Request interceptor — attach auth token, language, currency & guest cart ID
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  const lang = localStorage.getItem('language') || 'en'
  const currency = localStorage.getItem('currency') || 'SAR'
  config.params = { ...config.params, lang, currency }

  // Always send the guest cart UUID so the backend can identify the cart
  // for guests. The backend ignores it when user_id is present (logged-in).
  config.headers['X-Guest-Cart-ID'] = getOrCreateGuestCartId()

  return config
})

// Response interceptor — handle errors
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token')
      // Don't redirect if already on login page
      if (!window.location.pathname.includes('/login')) {
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  }
)

export default apiClient
