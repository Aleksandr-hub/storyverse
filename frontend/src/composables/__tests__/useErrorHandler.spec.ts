import { describe, it, expect, beforeEach } from 'vitest'
import { useErrorHandler } from '../useErrorHandler'

describe('useErrorHandler', () => {
  let errorHandler: ReturnType<typeof useErrorHandler>

  beforeEach(() => {
    errorHandler = useErrorHandler()
  })

  describe('initial state', () => {
    it('should have empty error message', () => {
      expect(errorHandler.error.value.message).toBe('')
    })

    it('should have empty field errors', () => {
      expect(errorHandler.error.value.fieldErrors).toEqual({})
    })

    it('should not have network error', () => {
      expect(errorHandler.error.value.isNetworkError).toBe(false)
    })

    it('should not have error', () => {
      expect(errorHandler.hasError.value).toBe(false)
    })
  })

  describe('setError', () => {
    it('should set error message', () => {
      errorHandler.setError('Test error')

      expect(errorHandler.error.value.message).toBe('Test error')
      expect(errorHandler.hasError.value).toBe(true)
    })

    it('should set field errors', () => {
      const fieldErrors = { email: ['Invalid email'], password: ['Too short'] }

      errorHandler.setError('Validation failed', fieldErrors)

      expect(errorHandler.error.value.fieldErrors).toEqual(fieldErrors)
    })
  })

  describe('clearError', () => {
    it('should clear all error state', () => {
      errorHandler.setError('Test error', { field: ['error'] })

      errorHandler.clearError()

      expect(errorHandler.error.value.message).toBe('')
      expect(errorHandler.error.value.fieldErrors).toEqual({})
      expect(errorHandler.hasError.value).toBe(false)
    })
  })

  describe('handleAxiosError', () => {
    it('should handle network error (no response)', () => {
      const axiosError = { response: undefined }

      errorHandler.handleAxiosError(axiosError)

      expect(errorHandler.error.value.isNetworkError).toBe(true)
      expect(errorHandler.error.value.message).toContain('Помилка мережі')
      expect(errorHandler.hasError.value).toBe(true)
    })

    it('should handle server error with message', () => {
      const axiosError = {
        response: {
          data: {
            message: 'Server error message',
            errors: { field: ['Field error'] },
          },
        },
      }

      errorHandler.handleAxiosError(axiosError)

      expect(errorHandler.error.value.message).toBe('Server error message')
      expect(errorHandler.error.value.fieldErrors).toEqual({ field: ['Field error'] })
      expect(errorHandler.hasError.value).toBe(true)
    })

    it('should use default message when server does not provide one', () => {
      const axiosError = {
        response: {
          data: {},
        },
      }

      errorHandler.handleAxiosError(axiosError, 'Default error')

      expect(errorHandler.error.value.message).toBe('Default error')
    })
  })

  describe('getFieldError', () => {
    it('should return first error for field', () => {
      errorHandler.setError('Error', { email: ['Error 1', 'Error 2'] })

      expect(errorHandler.getFieldError('email')).toBe('Error 1')
    })

    it('should return undefined for non-existent field', () => {
      errorHandler.setError('Error', { email: ['Error'] })

      expect(errorHandler.getFieldError('password')).toBeUndefined()
    })
  })

  describe('hasFieldError', () => {
    it('should return true for field with errors', () => {
      errorHandler.setError('Error', { email: ['Error'] })

      expect(errorHandler.hasFieldError('email')).toBe(true)
    })

    it('should return false for field without errors', () => {
      errorHandler.setError('Error', { email: ['Error'] })

      expect(errorHandler.hasFieldError('password')).toBe(false)
    })
  })
})
