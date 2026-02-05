import { ref, readonly } from 'vue'
import type { AxiosError } from 'axios'

interface ApiError {
  message: string
  errors?: Record<string, string[]>
}

interface ErrorState {
  message: string
  fieldErrors: Record<string, string[]>
  isNetworkError: boolean
}

const defaultErrorState: ErrorState = {
  message: '',
  fieldErrors: {},
  isNetworkError: false,
}

export function useErrorHandler() {
  const errorState = ref<ErrorState>({ ...defaultErrorState })
  const hasError = ref(false)

  const clearError = () => {
    errorState.value = { ...defaultErrorState }
    hasError.value = false
  }

  const setError = (message: string, fieldErrors: Record<string, string[]> = {}) => {
    errorState.value = {
      message,
      fieldErrors,
      isNetworkError: false,
    }
    hasError.value = true
  }

  const handleAxiosError = (error: unknown, defaultMessage = 'Сталася помилка') => {
    const axiosError = error as AxiosError<ApiError>

    // Network error (no response)
    if (!axiosError.response) {
      errorState.value = {
        message: 'Помилка мережі. Перевірте підключення до інтернету.',
        fieldErrors: {},
        isNetworkError: true,
      }
      hasError.value = true
      return
    }

    // Server error with message
    const serverMessage = axiosError.response?.data?.message
    const fieldErrors = axiosError.response?.data?.errors || {}

    errorState.value = {
      message: serverMessage || defaultMessage,
      fieldErrors,
      isNetworkError: false,
    }
    hasError.value = true
  }

  const getFieldError = (field: string): string | undefined => {
    return errorState.value.fieldErrors[field]?.[0]
  }

  const hasFieldError = (field: string): boolean => {
    return !!errorState.value.fieldErrors[field]?.length
  }

  return {
    error: readonly(errorState),
    hasError: readonly(hasError),
    clearError,
    setError,
    handleAxiosError,
    getFieldError,
    hasFieldError,
  }
}
