import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import {
  loginApi, registerApi, logoutApi, fetchMe,
  sendOtpApi, verifyOtpApi, resendOtpApi,
  sendProfilePhoneOtpApi, verifyProfilePhoneApi,
  socialLoginApi,
} from '@/api/services'
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
      if (token.value) await logoutApi()
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
      user.value = null
      token.value = null
      localStorage.removeItem('auth_token')
    }
  }

  function setUser(userData: User) {
    user.value = userData
  }

  // ─── Email OTP (Passwordless Login) ─────────────────────────────────────
  async function sendOtp(email: string) {
    isLoading.value = true
    try {
      const result = await sendOtpApi({ email })
      return { success: true, ...result }
    } catch (e: any) {
      return { success: false, message: e.response?.data?.message || 'Failed to send OTP' }
    } finally {
      isLoading.value = false
    }
  }

  async function verifyOtp(email: string, code: string) {
    isLoading.value = true
    try {
      const result = await verifyOtpApi({ email, code })
      user.value = result.user
      token.value = result.token
      localStorage.setItem('auth_token', result.token)
      return { success: true, isNewUser: result.isNewUser }
    } catch (e: any) {
      return { success: false, message: e.response?.data?.message || 'Invalid or expired code' }
    } finally {
      isLoading.value = false
    }
  }

  async function resendOtp(email: string) {
    try {
      const result = await resendOtpApi({ email })
      return { success: true, ...result }
    } catch (e: any) {
      return { success: false, message: e.response?.data?.message || 'Failed to resend OTP' }
    }
  }

  // ─── Phone OTP (SMS Passwordless Login) ─────────────────────────────────
  async function sendPhoneOtp(phone: string) {
    isLoading.value = true
    try {
      const result = await sendOtpApi({ phone })
      return { success: true, ...result }
    } catch (e: any) {
      return { success: false, message: e.response?.data?.message || 'Failed to send OTP' }
    } finally {
      isLoading.value = false
    }
  }

  async function verifyPhoneOtp(phone: string, code: string) {
    isLoading.value = true
    try {
      const result = await verifyOtpApi({ phone, code })
      user.value = result.user
      token.value = result.token
      localStorage.setItem('auth_token', result.token)
      return { success: true, isNewUser: result.isNewUser }
    } catch (e: any) {
      return { success: false, message: e.response?.data?.message || 'Invalid or expired code' }
    } finally {
      isLoading.value = false
    }
  }

  async function resendPhoneOtp(phone: string) {
    try {
      const result = await resendOtpApi({ phone })
      return { success: true, ...result }
    } catch (e: any) {
      return { success: false, message: e.response?.data?.message || 'Failed to resend OTP' }
    }
  }

  // ─── Profile Phone Verification (authenticated) ──────────────────────────
  async function sendProfilePhoneOtp(phone?: string) {
    try {
      const result = await sendProfilePhoneOtpApi(phone)
      return { success: true, ...result }
    } catch (e: any) {
      return { success: false, message: e.response?.data?.message || 'Failed to send OTP' }
    }
  }

  async function verifyProfilePhone(phone: string, code: string) {
    try {
      const updatedUser = await verifyProfilePhoneApi(phone, code)
      user.value = updatedUser
      return { success: true }
    } catch (e: any) {
      return { success: false, message: e.response?.data?.message || 'Invalid or expired code' }
    }
  }

  // ─── Social Login (Google / Facebook / Apple) ─────────────────────────────
  async function socialLogin(provider: 'google' | 'facebook' | 'apple', accessToken: string, name?: string) {
    isLoading.value = true
    try {
      const result = await socialLoginApi(provider, accessToken, name)
      user.value = result.user
      token.value = result.token
      localStorage.setItem('auth_token', result.token)
      return { success: true }
    } catch (e: any) {
      return { success: false, message: e.response?.data?.message || 'Social login failed' }
    } finally {
      isLoading.value = false
    }
  }

  /** Restore session on app init — if token exists, fetch user data. */
  async function restoreSession() {
    if (token.value) await fetchUser()
  }

  return {
    user, token, isAuthenticated, isLoading,
    login, register, logout, fetchUser, setUser, restoreSession,
    // Email OTP
    sendOtp, verifyOtp, resendOtp,
    // Phone OTP (login)
    sendPhoneOtp, verifyPhoneOtp, resendPhoneOtp,
    // Profile phone verification
    sendProfilePhoneOtp, verifyProfilePhone,
    // Social Login
    socialLogin,
  }
})
