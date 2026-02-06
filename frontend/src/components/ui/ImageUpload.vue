<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useImageUpload } from '@/composables'
import { aiApi } from '@/services/api'

type UploadType = 'avatar' | 'cover' | 'illustration'
type ImageStyle = 'anime' | 'realistic' | 'fantasy' | 'sketch'

interface Props {
  modelValue: string | null
  type: UploadType
  storyId?: string
  enableAI?: boolean
  aspectRatio?: string
  placeholder?: string
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  enableAI: true,
  aspectRatio: '16/9',
  placeholder: 'Натисніть або перетягніть файл',
  disabled: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: string | null]
  'upload-complete': [url: string]
  'upload-error': [error: string]
  'generate-complete': [url: string]
  'generate-error': [error: string]
}>()

// Tabs
type TabType = 'upload' | 'generate'
const activeTab = ref<TabType>('upload')
const showTabs = computed(() => props.enableAI && props.storyId && props.type === 'cover')

// Upload state
const { isUploading, progress, error: uploadError, upload, reset: resetUpload } = useImageUpload({
  type: props.type,
  onSuccess: (url) => {
    emit('update:modelValue', url)
    emit('upload-complete', url)
  },
  onError: (err) => {
    emit('upload-error', err)
  },
})

// Drag & drop state
const isDragging = ref(false)
const fileInputRef = ref<HTMLInputElement | null>(null)

// AI generation state
const isGenerating = ref(false)
const generateError = ref<string | null>(null)
const aiPrompt = ref('')
const aiStyle = ref<ImageStyle>('fantasy')
const generatedPreview = ref<string | null>(null)

const styleOptions = [
  { value: 'fantasy', label: 'Фентезі' },
  { value: 'anime', label: 'Аніме' },
  { value: 'realistic', label: 'Реалістичний' },
  { value: 'sketch', label: 'Скетч' },
]

// Computed
const hasValue = computed(() => !!props.modelValue)
const currentError = computed(() => activeTab.value === 'upload' ? uploadError.value : generateError.value)

const aspectRatioStyle = computed(() => {
  if (!props.aspectRatio) return {}
  const [w, h] = props.aspectRatio.split('/').map(Number)
  return { aspectRatio: `${w} / ${h}` }
})

// File handling
const handleFileSelect = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) {
    await upload(file)
  }
  // Reset input to allow re-selecting same file
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

const handleDragOver = (event: DragEvent) => {
  event.preventDefault()
  isDragging.value = true
}

const handleDragLeave = (event: DragEvent) => {
  event.preventDefault()
  isDragging.value = false
}

const handleDrop = async (event: DragEvent) => {
  event.preventDefault()
  isDragging.value = false

  const file = event.dataTransfer?.files?.[0]
  if (file && file.type.startsWith('image/')) {
    await upload(file)
  }
}

const triggerFileInput = () => {
  fileInputRef.value?.click()
}

// AI generation
const generateCover = async () => {
  if (!props.storyId || !aiPrompt.value.trim()) return

  isGenerating.value = true
  generateError.value = null
  generatedPreview.value = null

  try {
    const response = await aiApi.generateCover(props.storyId, {
      prompt: aiPrompt.value,
      style: aiStyle.value,
    })

    const url = response.data.url
    generatedPreview.value = url
    emit('update:modelValue', url)
    emit('generate-complete', url)
    aiPrompt.value = ''
  } catch (err: unknown) {
    const e = err as { response?: { status?: number; data?: { message?: string } } }
    if (e.response?.status === 503) {
      generateError.value = 'Сервіс генерації недоступний. Потрібна GPU.'
    } else {
      generateError.value = e.response?.data?.message || 'Помилка генерації'
    }
    emit('generate-error', generateError.value)
  } finally {
    isGenerating.value = false
  }
}

// Remove image
const handleRemove = () => {
  emit('update:modelValue', null)
  resetUpload()
  generatedPreview.value = null
}

// Reset on tab change
watch(activeTab, () => {
  uploadError.value = null
  generateError.value = null
})
</script>

<template>
  <div class="image-upload" :class="{ disabled, dragging: isDragging }">
    <!-- Preview (shown when has value) -->
    <div v-if="hasValue" class="preview-section">
      <div class="preview-container" :style="aspectRatioStyle">
        <img :src="modelValue!" alt="Preview" class="preview-image" />
        <button
          v-if="!disabled"
          type="button"
          @click="handleRemove"
          class="remove-button"
          title="Видалити"
        >
          X
        </button>
      </div>
      <p class="preview-hint">Щоб замінити, завантажте нове зображення нижче</p>
    </div>

    <!-- Tabs (only for covers with AI enabled) -->
    <div v-if="showTabs" class="tabs">
      <button
        type="button"
        :class="['tab', { active: activeTab === 'upload' }]"
        @click="activeTab = 'upload'"
      >
        Upload
      </button>
      <button
        type="button"
        :class="['tab', { active: activeTab === 'generate' }]"
        @click="activeTab = 'generate'"
      >
        AI
      </button>
    </div>

    <!-- Upload Tab -->
    <template v-if="activeTab === 'upload' || !showTabs">
      <label
        class="dropzone"
        :class="{ uploading: isUploading, dragging: isDragging, compact: hasValue }"
        @dragover="handleDragOver"
        @dragleave="handleDragLeave"
        @drop="handleDrop"
        @click.prevent="triggerFileInput"
      >
        <input
          ref="fileInputRef"
          type="file"
          accept="image/*"
          :disabled="disabled || isUploading"
          class="file-input"
          @change="handleFileSelect"
        />

        <!-- Progress Bar -->
        <div v-if="isUploading" class="upload-progress">
          <div class="progress-bar">
            <div class="progress-fill" :style="{ width: `${progress}%` }"></div>
          </div>
          <span class="progress-text">Завантаження... {{ Math.round(progress ?? 0) }}%</span>
        </div>

        <!-- Placeholder -->
        <div v-else class="placeholder">
          <span class="placeholder-icon">+</span>
          <span class="placeholder-text">{{ hasValue ? 'Замінити зображення' : placeholder }}</span>
          <span v-if="!hasValue" class="placeholder-hint">або перетягніть файл сюди</span>
        </div>
      </label>
    </template>

    <!-- AI Generate Tab -->
    <template v-else-if="activeTab === 'generate'">
      <div class="generate-form">
        <div v-if="!storyId" class="generate-disabled">
          <p>Спочатку збережіть історію</p>
        </div>

        <template v-else>
          <div class="form-group">
            <label class="form-label">Опис обкладинки</label>
            <input
              v-model="aiPrompt"
              type="text"
              class="form-input"
              placeholder="Наприклад: темний ліс з містичним замком"
              :disabled="isGenerating"
            />
          </div>

          <div class="form-group">
            <label class="form-label">Стиль</label>
            <select v-model="aiStyle" class="form-select" :disabled="isGenerating">
              <option v-for="opt in styleOptions" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
          </div>

          <button
            type="button"
            class="generate-button"
            :disabled="isGenerating || !aiPrompt.trim()"
            @click="generateCover"
          >
            <span v-if="isGenerating">Генерація... (30-60 сек)</span>
            <span v-else>{{ hasValue ? 'Згенерувати нову' : 'Згенерувати' }}</span>
          </button>
        </template>
      </div>
    </template>

    <!-- Error Message -->
    <p v-if="currentError" class="error-message">{{ currentError }}</p>
  </div>
</template>

<style scoped>
.image-upload {
  width: 100%;
}

.image-upload.disabled {
  opacity: 0.5;
  pointer-events: none;
}

/* Tabs */
.tabs {
  display: flex;
  gap: 4px;
  margin-bottom: 8px;
}

.tab {
  flex: 1;
  padding: 8px 12px;
  background: #f3f4f6;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.2s;
}

.tab:hover {
  background: #e5e7eb;
}

.tab.active {
  background: #4f46e5;
  color: #fff;
}

/* Preview Section */
.preview-section {
  margin-bottom: 12px;
}

.preview-hint {
  font-size: 0.75rem;
  color: #9ca3af;
  margin-top: 8px;
  text-align: center;
}

/* Preview */
.preview-container {
  position: relative;
  width: 100%;
  border-radius: 8px;
  overflow: hidden;
  background: #f3f4f6;
}

.preview-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.remove-button {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 28px;
  height: 28px;
  background: rgba(0, 0, 0, 0.6);
  color: #fff;
  border: none;
  border-radius: 50%;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.remove-button:hover {
  background: rgba(220, 38, 38, 0.9);
}

/* Dropzone */
.dropzone {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  border: 2px dashed #d1d5db;
  border-radius: 8px;
  background: #f9fafb;
  cursor: pointer;
  transition: all 0.2s;
}

.dropzone:hover,
.dropzone.dragging {
  border-color: #4f46e5;
  background: #f5f3ff;
}

.dropzone.uploading {
  cursor: not-allowed;
  border-style: solid;
  border-color: #4f46e5;
}

.dropzone.compact {
  padding: 16px;
  aspect-ratio: unset;
}

.dropzone.compact .placeholder {
  padding: 0;
}

.dropzone.compact .placeholder-icon {
  display: inline;
  font-size: 1rem;
  margin-right: 8px;
  margin-bottom: 0;
}

.dropzone.compact .placeholder-text {
  display: inline;
}

.file-input {
  display: none;
}

/* Progress */
.upload-progress {
  text-align: center;
  padding: 16px;
}

.progress-bar {
  width: 120px;
  height: 6px;
  background: #e5e7eb;
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 8px;
}

.progress-fill {
  height: 100%;
  background: #4f46e5;
  transition: width 0.2s;
}

.progress-text {
  font-size: 0.75rem;
  color: #4f46e5;
}

/* Placeholder */
.placeholder {
  text-align: center;
  padding: 16px;
}

.placeholder-icon {
  display: block;
  font-size: 2rem;
  color: #9ca3af;
  margin-bottom: 8px;
}

.placeholder-text {
  display: block;
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 4px;
}

.placeholder-hint {
  display: block;
  font-size: 0.75rem;
  color: #9ca3af;
}

/* Generate Form */
.generate-form {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 16px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #f9fafb;
}

.generate-disabled {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #9ca3af;
  font-size: 0.875rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.form-label {
  font-size: 0.75rem;
  font-weight: 500;
  color: #374151;
}

.form-input,
.form-select {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.875rem;
  background: #fff;
  outline: none;
  transition: border-color 0.2s;
}

.form-input:focus,
.form-select:focus {
  border-color: #4f46e5;
  box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.1);
}

.generate-button {
  padding: 10px 16px;
  background: #4f46e5;
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
}

.generate-button:hover:not(:disabled) {
  background: #4338ca;
}

.generate-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Error */
.error-message {
  margin-top: 8px;
  padding: 8px 12px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 6px;
  color: #dc2626;
  font-size: 0.875rem;
}
</style>
