import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/services/api'
import type { AxiosError } from 'axios'

export interface User {
  id: string
  email: string
  username: string
  avatar_url: string | null
  bio: string | null
  oauth_provider: string | null
  is_premium: boolean
  is_active: boolean
  email_verified_at: string | null
  created_at: string
  updated_at: string
}

interface ApiError {
  message: string
  errors?: Record<string, string[]>
}

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Getters
  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isOAuthUser = computed(() => !!user.value?.oauth_provider)

  // Initialize from localStorage
  const init = () => {
    const storedToken = localStorage.getItem('token')
    const storedUser = localStorage.getItem('user')

    if (storedToken) {
      token.value = storedToken
    }
    if (storedUser) {
      try {
        user.value = JSON.parse(storedUser)
      } catch {
        localStorage.removeItem('user')
      }
    }
  }

  // Actions
  const setAuth = (userData: User, authToken: string) => {
    user.value = userData
    token.value = authToken
    localStorage.setItem('token', authToken)
    localStorage.setItem('user', JSON.stringify(userData))
  }

  const clearAuth = () => {
    user.value = null
    token.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }

  const register = async (data: {
    email: string
    username: string
    password: string
    password_confirmation: string
  }) => {
    loading.value = true
    error.value = null

    try {
      const response = await authApi.register(data)
      setAuth(response.data.user, response.data.token)
      return { success: true }
    } catch (err) {
      const axiosError = err as AxiosError<ApiError>
      error.value = axiosError.response?.data?.message || 'Помилка реєстрації'
      return {
        success: false,
        errors: axiosError.response?.data?.errors,
        message: error.value,
      }
    } finally {
      loading.value = false
    }
  }

  const login = async (data: { email: string; password: string }) => {
    loading.value = true
    error.value = null

    try {
      const response = await authApi.login(data)
      setAuth(response.data.user, response.data.token)
      return { success: true }
    } catch (err) {
      const axiosError = err as AxiosError<ApiError>
      error.value = axiosError.response?.data?.message || 'Помилка входу'
      return {
        success: false,
        errors: axiosError.response?.data?.errors,
        message: error.value,
      }
    } finally {
      loading.value = false
    }
  }

  const logout = async () => {
    loading.value = true

    try {
      await authApi.logout()
    } catch {
      // Ignore errors on logout
    } finally {
      clearAuth()
      loading.value = false
    }
  }

  const fetchUser = async () => {
    if (!token.value) return

    loading.value = true
    try {
      const response = await authApi.me()
      user.value = response.data.user
      localStorage.setItem('user', JSON.stringify(response.data.user))
    } catch {
      clearAuth()
    } finally {
      loading.value = false
    }
  }

  const updateProfile = async (data: { username?: string; bio?: string; avatar_url?: string }) => {
    loading.value = true
    error.value = null

    try {
      const response = await authApi.updateProfile(data)
      user.value = response.data.user
      localStorage.setItem('user', JSON.stringify(response.data.user))
      return { success: true }
    } catch (err) {
      const axiosError = err as AxiosError<ApiError>
      error.value = axiosError.response?.data?.message || 'Помилка оновлення профілю'
      return {
        success: false,
        errors: axiosError.response?.data?.errors,
        message: error.value,
      }
    } finally {
      loading.value = false
    }
  }

  // OAuth login redirect
  const loginWithOAuth = (provider: 'google' | 'facebook') => {
    window.location.href = authApi.getOAuthUrl(provider)
  }

  // Handle OAuth callback
  const handleOAuthCallback = async (token: string, user: User) => {
    setAuth(user, token)
  }

  return {
    // State
    user,
    token,
    loading,
    error,
    // Getters
    isAuthenticated,
    isOAuthUser,
    // Actions
    init,
    register,
    login,
    logout,
    fetchUser,
    updateProfile,
    loginWithOAuth,
    handleOAuthCallback,
    clearAuth,
  }
})
