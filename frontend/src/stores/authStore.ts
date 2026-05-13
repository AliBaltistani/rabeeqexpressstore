import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { loginApi, registerApi, logoutApi, fetchMe } from '@/api/services'
import type { User } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const isLoading = ref(false)

  const isAuthenticated = computed(() => !!token.value)

  async function login(email: string, password: string) {
    isLoading.value = true
    try {
      const result = await loginApi(email, password)
      user.value = result.user
      token.value = result.token
      localStorage.setItem('auth_token', result.token)
      return result
    } finally {
      isLoading.value = false
    }
  }

  async function register(data: { name: string; email: string; password: string; password_confirmation: string }) {
    isLoading.value = true
    try {
      const result = await registerApi(data)
      if (result.token) {
        user.value = result.user
        token.value = result.token
        localStorage.setItem('auth_token', result.token)
      }
      return result
    } finally {
      isLoading.value = false
    }
  }

  async function logout() {
    try {
      if (token.value) {
        await logoutApi()
      }
    } catch {
      // Ignore errors on logout
    } finally {
      user.value = null
      token.value = null
      localStorage.removeItem('auth_token')
    }
  }

  async function fetchUser() {
    if (!token.value) return
    try {
      user.value = await fetchMe()
    } catch {
      // Token might be expired
      user.value = null
      token.value = null
      localStorage.removeItem('auth_token')
    }
  }

  function setUser(userData: User) {
    user.value = userData
  }

  /**
   * Restore session on app init — if token exists, fetch user data.
   */
  async function restoreSession() {
    if (token.value) {
      await fetchUser()
    }
  }

  return {
    user, token, isAuthenticated, isLoading,
    login, register, logout, fetchUser, setUser, restoreSession,
  }
})
