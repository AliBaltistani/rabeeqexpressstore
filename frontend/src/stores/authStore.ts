import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<any>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))

  const isAuthenticated = computed(() => !!token.value)

  function login(userData: any, authToken: string) {
    user.value = userData
    token.value = authToken
    localStorage.setItem('auth_token', authToken)
  }

  function logout() {
    user.value = null
    token.value = null
    localStorage.removeItem('auth_token')
  }

  function setUser(userData: any) {
    user.value = userData
  }

  return { user, token, isAuthenticated, login, logout, setUser }
})
