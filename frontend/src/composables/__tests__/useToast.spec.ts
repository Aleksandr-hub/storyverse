import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { useToast } from '../useToast'

describe('useToast', () => {
  let toast: ReturnType<typeof useToast>

  beforeEach(() => {
    vi.useFakeTimers()
    toast = useToast()
    toast.clear()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  describe('show', () => {
    it('should add toast to list', () => {
      toast.show('Test message', 'info')

      expect(toast.toasts.value).toHaveLength(1)
      expect(toast.toasts.value[0]!.message).toBe('Test message')
      expect(toast.toasts.value[0]!.type).toBe('info')
    })

    it('should auto-remove toast after duration', () => {
      toast.show('Test message', 'info', 1000)

      expect(toast.toasts.value).toHaveLength(1)

      vi.advanceTimersByTime(1000)

      expect(toast.toasts.value).toHaveLength(0)
    })

    it('should not auto-remove when duration is 0', () => {
      toast.show('Test message', 'info', 0)

      vi.advanceTimersByTime(10000)

      expect(toast.toasts.value).toHaveLength(1)
    })
  })

  describe('remove', () => {
    it('should remove specific toast', () => {
      const id = toast.show('Test message', 'info', 0)

      toast.remove(id)

      expect(toast.toasts.value).toHaveLength(0)
    })

    it('should not fail when removing non-existent toast', () => {
      toast.show('Test message', 'info', 0)

      expect(() => toast.remove(999)).not.toThrow()
      expect(toast.toasts.value).toHaveLength(1)
    })
  })

  describe('convenience methods', () => {
    it('success should create success toast', () => {
      toast.success('Success!')

      expect(toast.toasts.value[0]!.type).toBe('success')
    })

    it('error should create error toast', () => {
      toast.error('Error!')

      expect(toast.toasts.value[0]!.type).toBe('error')
    })

    it('warning should create warning toast', () => {
      toast.warning('Warning!')

      expect(toast.toasts.value[0]!.type).toBe('warning')
    })

    it('info should create info toast', () => {
      toast.info('Info!')

      expect(toast.toasts.value[0]!.type).toBe('info')
    })
  })

  describe('clear', () => {
    it('should remove all toasts', () => {
      toast.show('Toast 1', 'info', 0)
      toast.show('Toast 2', 'success', 0)
      toast.show('Toast 3', 'error', 0)

      expect(toast.toasts.value).toHaveLength(3)

      toast.clear()

      expect(toast.toasts.value).toHaveLength(0)
    })
  })
})
