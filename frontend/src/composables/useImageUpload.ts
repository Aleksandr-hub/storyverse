import { ref } from 'vue'
import { uploadApi } from '@/services/api'

export interface UseImageUploadOptions {
  type: 'avatar' | 'cover' | 'illustration'
  maxSize?: number // in bytes, default 5MB
  onSuccess?: (url: string, path: string) => void
  onError?: (error: string) => void
}

export interface UseImageUploadReturn {
  isUploading: ReturnType<typeof ref<boolean>>
  progress: ReturnType<typeof ref<number>>
  error: ReturnType<typeof ref<string | null>>
  upload: (file: File) => Promise<{ url: string; path: string } | null>
  validateFile: (file: File) => string | null
  reset: () => void
}

const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
const DEFAULT_MAX_SIZE = 5 * 1024 * 1024 // 5MB

export function useImageUpload(options: UseImageUploadOptions): UseImageUploadReturn {
  const { type, maxSize = DEFAULT_MAX_SIZE, onSuccess, onError } = options

  const isUploading = ref(false)
  const progress = ref(0)
  const error = ref<string | null>(null)

  // Progress simulation interval
  let progressInterval: ReturnType<typeof setInterval> | null = null

  const startProgressSimulation = () => {
    progress.value = 0
    progressInterval = setInterval(() => {
      if (progress.value < 90) {
        progress.value += Math.random() * 15
        if (progress.value > 90) progress.value = 90
      }
    }, 200)
  }

  const stopProgressSimulation = () => {
    if (progressInterval) {
      clearInterval(progressInterval)
      progressInterval = null
    }
    progress.value = 100
  }

  const validateFile = (file: File): string | null => {
    // Check file type
    if (!ALLOWED_TYPES.includes(file.type)) {
      return 'Дозволені лише зображення (JPEG, PNG, GIF, WebP)'
    }

    // Check file size
    if (file.size > maxSize) {
      const maxMB = Math.round(maxSize / 1024 / 1024)
      return `Файл занадто великий (макс. ${maxMB}MB)`
    }

    return null
  }

  const upload = async (file: File): Promise<{ url: string; path: string } | null> => {
    // Validate
    const validationError = validateFile(file)
    if (validationError) {
      error.value = validationError
      onError?.(validationError)
      return null
    }

    error.value = null
    isUploading.value = true
    startProgressSimulation()

    try {
      const response = await uploadApi.upload(file, type)
      stopProgressSimulation()

      const { url, path } = response.data
      onSuccess?.(url, path)

      return { url, path }
    } catch (err: unknown) {
      stopProgressSimulation()

      const e = err as { response?: { data?: { message?: string } } }
      const errorMessage = e.response?.data?.message || 'Помилка завантаження файлу'

      error.value = errorMessage
      onError?.(errorMessage)

      return null
    } finally {
      isUploading.value = false
    }
  }

  const reset = () => {
    isUploading.value = false
    progress.value = 0
    error.value = null
    if (progressInterval) {
      clearInterval(progressInterval)
      progressInterval = null
    }
  }

  return {
    isUploading,
    progress,
    error,
    upload,
    validateFile,
    reset,
  }
}
