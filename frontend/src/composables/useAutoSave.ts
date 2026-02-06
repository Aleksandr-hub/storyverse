import { ref, watch, onUnmounted, type Ref } from 'vue'

interface AutoSaveOptions {
  /** Interval in milliseconds (default: 30000 = 30 seconds) */
  interval?: number
  /** Callback to execute on save */
  onSave: () => Promise<void>
  /** Data to watch for changes */
  data: Ref<unknown>
  /** Whether auto-save is enabled (default: true) */
  enabled?: Ref<boolean> | boolean
}

export function useAutoSave(options: AutoSaveOptions) {
  const {
    interval = 30000,
    onSave,
    data,
    enabled = true
  } = options

  const isSaving = ref(false)
  const lastSavedAt = ref<Date | null>(null)
  const hasUnsavedChanges = ref(false)
  const error = ref<string | null>(null)

  let saveTimer: ReturnType<typeof setInterval> | null = null
  let initialValue: string = ''

  // Track initial value
  const setInitialValue = () => {
    initialValue = JSON.stringify(data.value)
    hasUnsavedChanges.value = false
  }

  // Check if data has changed
  const checkForChanges = () => {
    const currentValue = JSON.stringify(data.value)
    hasUnsavedChanges.value = currentValue !== initialValue
  }

  // Watch for data changes
  watch(data, () => {
    checkForChanges()
  }, { deep: true })

  // Save function
  const save = async () => {
    if (isSaving.value || !hasUnsavedChanges.value) return

    isSaving.value = true
    error.value = null

    try {
      await onSave()
      lastSavedAt.value = new Date()
      setInitialValue()
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Помилка збереження'
      console.error('Auto-save error:', e)
    } finally {
      isSaving.value = false
    }
  }

  // Start auto-save timer
  const startAutoSave = () => {
    stopAutoSave()

    const isEnabled = typeof enabled === 'boolean' ? enabled : enabled.value
    if (!isEnabled) return

    saveTimer = setInterval(() => {
      const isCurrentlyEnabled = typeof enabled === 'boolean' ? enabled : enabled.value
      if (isCurrentlyEnabled && hasUnsavedChanges.value) {
        save()
      }
    }, interval)
  }

  // Stop auto-save timer
  const stopAutoSave = () => {
    if (saveTimer) {
      clearInterval(saveTimer)
      saveTimer = null
    }
  }

  // Initialize
  setInitialValue()
  startAutoSave()

  // Cleanup
  onUnmounted(() => {
    stopAutoSave()
  })

  // Warn user about unsaved changes when leaving
  const handleBeforeUnload = (e: BeforeUnloadEvent) => {
    if (hasUnsavedChanges.value) {
      e.preventDefault()
      e.returnValue = ''
    }
  }

  window.addEventListener('beforeunload', handleBeforeUnload)

  onUnmounted(() => {
    window.removeEventListener('beforeunload', handleBeforeUnload)
  })

  return {
    isSaving,
    lastSavedAt,
    hasUnsavedChanges,
    error,
    save,
    setInitialValue,
    startAutoSave,
    stopAutoSave
  }
}
