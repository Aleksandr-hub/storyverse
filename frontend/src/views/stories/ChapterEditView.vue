<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { useStoriesStore } from '@/stores/stories'
import { aiApi } from '@/services/api'
import AppHeader from '@/components/layout/AppHeader.vue'

const route = useRoute()
const router = useRouter()
const storiesStore = useStoriesStore()

// Check if story has adult rating
const isAdultStory = computed(() => {
  return story.value && ['R', 'NC-17', '18+'].includes(story.value.rating)
})

const story = computed(() => storiesStore.currentStory)
const chapter = computed(() => storiesStore.currentChapter)
const loading = computed(() => storiesStore.loading)

const form = ref({
  title: '',
  content: '',
})

// Count words (supports Ukrainian/Cyrillic)
const countWords = (text: string): number => {
  if (!text) return 0
  const cleaned = text.replace(/[*_~`#\[\]()>-]+/g, ' ')
  const matches = cleaned.match(/[\p{L}\p{N}']+/gu)
  return matches ? matches.length : 0
}

const wordCount = computed(() => countWords(form.value.content))

// AI functionality
const aiLoading = ref(false)
const aiError = ref('')
const aiSuggestions = ref('')
const showAiPanel = ref(false)
const aiPrompt = ref('')

const continueWithAI = async () => {
  if (!story.value || !chapter.value) return

  aiLoading.value = true
  aiError.value = ''

  try {
    const res = await aiApi.continueWriting(story.value.id, {
      chapter_id: chapter.value.id,
      prompt: aiPrompt.value || undefined,
    })

    if (res.data.text) {
      if (form.value.content) {
        form.value.content += '\n\n' + res.data.text
      } else {
        form.value.content = res.data.text
      }
    }
    aiPrompt.value = ''
    showAiPanel.value = false
  } catch (err: any) {
    if (err.response?.status === 429) {
      aiError.value = 'Ліміт AI запитів вичерпано. Спробуйте пізніше.'
    } else {
      aiError.value = err.response?.data?.message || 'Помилка AI сервісу'
    }
  } finally {
    aiLoading.value = false
  }
}

const getAiSuggestions = async () => {
  if (!story.value || !chapter.value) return

  aiLoading.value = true
  aiError.value = ''
  aiSuggestions.value = ''

  try {
    const res = await aiApi.getSuggestions(story.value.id, {
      chapter_id: chapter.value.id,
    })
    aiSuggestions.value = res.data.suggestions
  } catch (err: any) {
    if (err.response?.status === 429) {
      aiError.value = 'Ліміт AI запитів вичерпано. Спробуйте пізніше.'
    } else {
      aiError.value = err.response?.data?.message || 'Помилка AI сервісу'
    }
  } finally {
    aiLoading.value = false
  }
}

const toggleAiPanel = () => {
  showAiPanel.value = !showAiPanel.value
  if (!showAiPanel.value) {
    aiError.value = ''
    aiSuggestions.value = ''
  }
}

// Adult content mode (uses Ollama - uncensored)
const adultMode = ref(false)

const continueWithAdultAI = async () => {
  if (!story.value || !chapter.value) return

  aiLoading.value = true
  aiError.value = ''

  try {
    const res = await aiApi.continueWritingAdult(story.value.id, {
      chapter_id: chapter.value.id,
      prompt: aiPrompt.value || undefined,
    })

    if (res.data.text) {
      if (form.value.content) {
        form.value.content += '\n\n' + res.data.text
      } else {
        form.value.content = res.data.text
      }
    }
    aiPrompt.value = ''
    showAiPanel.value = false
  } catch (err: any) {
    if (err.response?.status === 503) {
      aiError.value = 'Ollama не запущено. Запустіть: docker compose up ollama'
    } else if (err.response?.status === 403) {
      aiError.value = 'Цей режим доступний лише для історій з рейтингом 18+'
    } else {
      aiError.value = err.response?.data?.message || 'Помилка AI сервісу'
    }
  } finally {
    aiLoading.value = false
  }
}

// Image generation (uses Stable Diffusion)
const showImagePanel = ref(false)
const imageLoading = ref(false)
const imageError = ref('')
const imagePrompt = ref('')
const imageStyle = ref('fantasy')
const generatedImages = ref<Array<{ url: string; prompt: string }>>([])

const generateImage = async () => {
  if (!story.value || !chapter.value || !imagePrompt.value) return

  imageLoading.value = true
  imageError.value = ''

  try {
    const res = await aiApi.generateIllustration(story.value.id, {
      chapter_id: chapter.value.id,
      prompt: imagePrompt.value,
      style: imageStyle.value,
    })

    if (res.data.url) {
      generatedImages.value.unshift({
        url: res.data.url,
        prompt: imagePrompt.value,
      })
      imagePrompt.value = ''
    }
  } catch (err: any) {
    if (err.response?.status === 503) {
      imageError.value = 'Stable Diffusion не запущено. Потрібна GPU.'
    } else {
      imageError.value = err.response?.data?.message || 'Помилка генерації'
    }
  } finally {
    imageLoading.value = false
  }
}

const toggleImagePanel = () => {
  showImagePanel.value = !showImagePanel.value
  if (!showImagePanel.value) {
    imageError.value = ''
  }
}

const save = async () => {
  if (!chapter.value) return

  const result = await storiesStore.updateChapter(chapter.value.id, form.value)
  if (result.success) {
    // Update local state
  } else {
    alert(result.message || 'Помилка збереження')
  }
}

const saveAndClose = async () => {
  await save()
  if (story.value && chapter.value) {
    router.push(`/stories/${story.value.id}/chapters/${chapter.value.id}`)
  }
}

watch(chapter, (newChapter) => {
  if (newChapter) {
    form.value = {
      title: newChapter.title || '',
      content: newChapter.content || '',
    }
  }
}, { immediate: true })

onMounted(async () => {
  const storyId = route.params.storyId as string
  const chapterId = route.params.chapterId as string

  // Load story if not already loaded
  if (!story.value || story.value.id !== storyId) {
    await storiesStore.fetchStory(storyId)
  }

  // Load chapter
  await storiesStore.fetchChapter(storyId, chapterId)
})
</script>

<template>
  <div class="edit-page">
    <AppHeader />

    <main class="main">
      <!-- Loading State -->
      <div v-if="loading && !chapter" class="loading">
        <div class="spinner"></div>
      </div>

      <!-- Editor -->
      <div v-else-if="story && chapter">
        <!-- Header -->
        <div class="page-header">
          <div class="header-left">
            <RouterLink :to="`/stories/${story.id}/edit`" class="back-link">
              ← Назад
            </RouterLink>
            <h1 class="page-title">
              Редагування: Глава {{ chapter.chapter_number }}
            </h1>
          </div>
          <div class="header-actions">
            <button
              @click="toggleImagePanel"
              :class="['btn-image', { active: showImagePanel }]"
            >
              🎨 Ілюстрація
            </button>
            <button
              @click="toggleAiPanel"
              :class="['btn-ai', { active: showAiPanel }]"
            >
              🤖 AI
            </button>
            <button
              @click="save"
              :disabled="loading"
              class="btn-secondary"
            >
              Зберегти
            </button>
            <button
              @click="saveAndClose"
              :disabled="loading"
              class="btn-primary"
            >
              Зберегти і вийти
            </button>
          </div>
        </div>

        <!-- Image Generation Panel -->
        <div v-if="showImagePanel" class="card image-panel">
          <div class="image-panel-header">
            <h3 class="image-panel-title">🎨 Генерація ілюстрації</h3>
            <button @click="showImagePanel = false" class="image-panel-close">×</button>
          </div>

          <div class="image-panel-content">
            <div class="image-prompt-group">
              <label class="image-label">Опис зображення</label>
              <input
                v-model="imagePrompt"
                type="text"
                class="image-input"
                placeholder="Наприклад: персонаж стоїть на горі на заході сонця"
              />
            </div>

            <div class="image-style-group">
              <label class="image-label">Стиль</label>
              <select v-model="imageStyle" class="image-select">
                <option value="fantasy">Фентезі</option>
                <option value="anime">Аніме</option>
                <option value="realistic">Реалістичний</option>
                <option value="sketch">Скетч</option>
              </select>
            </div>

            <button
              @click="generateImage"
              :disabled="imageLoading || !imagePrompt"
              class="btn-image-generate"
            >
              {{ imageLoading ? 'Генерація...' : '🎨 Згенерувати' }}
            </button>

            <div v-if="imageError" class="image-error">
              {{ imageError }}
            </div>

            <div v-if="generatedImages.length > 0" class="generated-images">
              <h5 class="generated-images-title">Згенеровані ілюстрації:</h5>
              <div class="images-grid">
                <div v-for="(img, idx) in generatedImages" :key="idx" class="image-item">
                  <img :src="img.url" :alt="img.prompt" />
                  <p class="image-caption">{{ img.prompt }}</p>
                </div>
              </div>
            </div>

            <div v-if="imageLoading" class="image-loading">
              <div class="image-loading-spinner"></div>
              <p>Генерація може зайняти 30-60 секунд...</p>
            </div>
          </div>
        </div>

        <!-- AI Panel -->
        <div v-if="showAiPanel" class="card ai-panel">
          <div class="ai-panel-header">
            <h3 class="ai-panel-title">🤖 AI Помічник</h3>
            <div class="ai-panel-header-right">
              <label v-if="isAdultStory" class="adult-toggle">
                <input type="checkbox" v-model="adultMode" />
                <span class="adult-toggle-label">🔞 18+ режим</span>
              </label>
              <button @click="showAiPanel = false" class="ai-panel-close">×</button>
            </div>
          </div>

          <div v-if="adultMode && isAdultStory" class="adult-warning">
            ⚠️ Увімкнено режим для дорослих. AI використовує Ollama без цензури.
          </div>

          <div class="ai-panel-content">
            <div class="ai-prompt-group">
              <label class="ai-label">Інструкція для AI (необов'язково)</label>
              <input
                v-model="aiPrompt"
                type="text"
                class="ai-input"
                :placeholder="adultMode ? 'Наприклад: напиши еротичну сцену між...' : 'Наприклад: додай діалог між персонажами'"
              />
            </div>

            <div class="ai-actions">
              <button
                v-if="adultMode && isAdultStory"
                @click="continueWithAdultAI"
                :disabled="aiLoading"
                class="btn-ai-action btn-ai-adult"
              >
                {{ aiLoading ? 'Генерація...' : '🔞 Продовжити (18+)' }}
              </button>
              <button
                v-else
                @click="continueWithAI"
                :disabled="aiLoading"
                class="btn-ai-action btn-ai-primary"
              >
                {{ aiLoading ? 'Генерація...' : '✨ Продовжити текст' }}
              </button>
              <button
                @click="getAiSuggestions"
                :disabled="aiLoading"
                class="btn-ai-action btn-ai-secondary"
              >
                {{ aiLoading ? '...' : '💡 Ідеї для сюжету' }}
              </button>
            </div>

            <div v-if="aiError" class="ai-error">
              {{ aiError }}
            </div>

            <div v-if="aiSuggestions" class="ai-suggestions">
              <h5 class="ai-suggestions-title">Ідеї:</h5>
              <pre class="ai-suggestions-text">{{ aiSuggestions }}</pre>
            </div>
          </div>
        </div>

        <!-- Editor Card -->
        <div class="card editor-card">
          <!-- Title -->
          <div class="form-group">
            <label class="form-label">Назва глави</label>
            <input
              v-model="form.title"
              type="text"
              class="form-input"
              :placeholder="`Глава ${chapter.chapter_number}`"
            />
          </div>

          <!-- Content -->
          <div class="form-group content-group">
            <label class="form-label">Текст</label>
            <div class="editor-wrapper">
              <textarea
                v-model="form.content"
                rows="25"
                class="text-editor"
                placeholder="Почніть писати тут..."
                :disabled="aiLoading"
              ></textarea>

              <!-- AI Loading Overlay -->
              <div v-if="aiLoading" class="ai-loading-overlay">
                <div class="ai-loading-content">
                  <div class="ai-loading-spinner"></div>
                  <p class="ai-loading-text">AI генерує текст...</p>
                  <p class="ai-loading-hint">Це може зайняти 10-30 секунд</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="editor-footer">
            <span class="word-count">{{ wordCount.toLocaleString() }} слів</span>
            <span v-if="loading" class="saving-indicator">Збереження...</span>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
.edit-page {
  min-height: 100vh;
  background: #f9fafb;
}

.main {
  max-width: 896px;
  margin: 0 auto;
  padding: 32px 20px;
}

.loading {
  display: flex;
  justify-content: center;
  padding: 80px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e5e7eb;
  border-top-color: #4f46e5;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Header */
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
  gap: 16px;
  flex-wrap: wrap;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.back-link {
  color: #4f46e5;
}

.back-link:hover {
  color: #4338ca;
}

.page-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #111827;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-primary {
  padding: 8px 16px;
  background: #4f46e5;
  color: #fff;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-primary:hover:not(:disabled) {
  background: #4338ca;
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  padding: 8px 16px;
  background: #f3f4f6;
  color: #374151;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-secondary:hover:not(:disabled) {
  background: #e5e7eb;
}

.btn-secondary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-ai {
  padding: 8px 16px;
  background: #fef3c7;
  color: #92400e;
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-ai:hover {
  background: #fde68a;
}

.btn-ai.active {
  background: #f59e0b;
  color: #fff;
}

.btn-image {
  padding: 8px 16px;
  background: #dbeafe;
  color: #1e40af;
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-image:hover {
  background: #bfdbfe;
}

.btn-image.active {
  background: #3b82f6;
  color: #fff;
}

/* Card */
.card {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}

.editor-card {
  display: flex;
  flex-direction: column;
}

/* Form */
.form-group {
  padding: 16px;
  border-bottom: 1px solid #e5e7eb;
}

.form-group:last-of-type {
  border-bottom: none;
}

.content-group {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.form-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 4px;
}

.form-input {
  width: 100%;
  padding: 8px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
  outline: none;
  transition: border-color 0.2s;
}

.form-input:focus {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

/* Text Editor */
.text-editor {
  flex: 1;
  width: 100%;
  padding: 12px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
  line-height: 1.75;
  color: #111827;
  resize: none;
  outline: none;
  min-height: 500px;
}

.text-editor:focus {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.text-editor::placeholder {
  color: #9ca3af;
}

.text-editor:disabled {
  background: #f9fafb;
}

/* Editor Wrapper */
.editor-wrapper {
  position: relative;
  flex: 1;
  display: flex;
}

/* AI Panel */
.ai-panel {
  margin-bottom: 16px;
  background: #fffbeb;
  border-color: #fde68a;
}

.ai-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  border-bottom: 1px solid #fde68a;
}

.ai-panel-title {
  font-weight: 600;
  color: #92400e;
}

.ai-panel-close {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #92400e;
  font-size: 1.25rem;
  border-radius: 4px;
  transition: background 0.2s;
}

.ai-panel-close:hover {
  background: #fde68a;
}

.ai-panel-content {
  padding: 16px;
}

.ai-prompt-group {
  margin-bottom: 12px;
}

.ai-label {
  display: block;
  font-size: 0.75rem;
  color: #92400e;
  margin-bottom: 4px;
}

.ai-input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #fde68a;
  border-radius: 8px;
  font-size: 0.875rem;
  background: #fff;
  outline: none;
}

.ai-input:focus {
  border-color: #f59e0b;
  box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
}

.ai-actions {
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
}

.btn-ai-action {
  padding: 10px 16px;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-ai-action:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-ai-primary {
  background: #f59e0b;
  color: #fff;
}

.btn-ai-primary:hover:not(:disabled) {
  background: #d97706;
}

.btn-ai-secondary {
  background: #fff;
  color: #92400e;
  border: 1px solid #fde68a;
}

.btn-ai-secondary:hover:not(:disabled) {
  background: #fef3c7;
}

.ai-error {
  padding: 8px 12px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  color: #dc2626;
  font-size: 0.875rem;
  margin-bottom: 12px;
}

.ai-suggestions {
  background: #fff;
  border: 1px solid #fde68a;
  border-radius: 8px;
  padding: 12px;
}

.ai-suggestions-title {
  font-size: 0.75rem;
  color: #92400e;
  margin-bottom: 8px;
}

.ai-suggestions-text {
  font-size: 0.875rem;
  color: #374151;
  white-space: pre-wrap;
  font-family: inherit;
  margin: 0;
  line-height: 1.5;
}

/* Adult Mode */
.ai-panel-header-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.adult-toggle {
  display: flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
}

.adult-toggle input {
  width: 16px;
  height: 16px;
  cursor: pointer;
}

.adult-toggle-label {
  font-size: 0.875rem;
  color: #dc2626;
  font-weight: 500;
}

.adult-warning {
  background: #fef2f2;
  border-bottom: 1px solid #fecaca;
  padding: 8px 16px;
  font-size: 0.875rem;
  color: #dc2626;
}

.btn-ai-adult {
  background: #dc2626;
  color: #fff;
}

.btn-ai-adult:hover:not(:disabled) {
  background: #b91c1c;
}

/* Image Generation Panel */
.image-panel {
  margin-bottom: 16px;
  background: #eff6ff;
  border-color: #bfdbfe;
}

.image-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  border-bottom: 1px solid #bfdbfe;
}

.image-panel-title {
  font-weight: 600;
  color: #1e40af;
}

.image-panel-close {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #1e40af;
  font-size: 1.25rem;
  border-radius: 4px;
  transition: background 0.2s;
}

.image-panel-close:hover {
  background: #bfdbfe;
}

.image-panel-content {
  padding: 16px;
}

.image-prompt-group,
.image-style-group {
  margin-bottom: 12px;
}

.image-label {
  display: block;
  font-size: 0.75rem;
  color: #1e40af;
  margin-bottom: 4px;
}

.image-input,
.image-select {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #bfdbfe;
  border-radius: 8px;
  font-size: 0.875rem;
  background: #fff;
  outline: none;
}

.image-input:focus,
.image-select:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.btn-image-generate {
  width: 100%;
  padding: 12px 16px;
  background: #3b82f6;
  color: #fff;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
  margin-bottom: 12px;
}

.btn-image-generate:hover:not(:disabled) {
  background: #2563eb;
}

.btn-image-generate:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.image-error {
  padding: 8px 12px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  color: #dc2626;
  font-size: 0.875rem;
  margin-bottom: 12px;
}

.image-loading {
  text-align: center;
  padding: 24px;
}

.image-loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #bfdbfe;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 12px;
}

.image-loading p {
  color: #1e40af;
  font-size: 0.875rem;
}

.generated-images {
  margin-top: 16px;
}

.generated-images-title {
  font-size: 0.875rem;
  color: #1e40af;
  margin-bottom: 12px;
}

.images-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 12px;
}

.image-item {
  border: 1px solid #bfdbfe;
  border-radius: 8px;
  overflow: hidden;
  background: #fff;
}

.image-item img {
  width: 100%;
  height: 150px;
  object-fit: cover;
}

.image-caption {
  padding: 8px;
  font-size: 0.75rem;
  color: #6b7280;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* AI Loading Overlay */
.ai-loading-overlay {
  position: absolute;
  inset: 0;
  background: rgba(255, 255, 255, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
  border-radius: 8px;
}

.ai-loading-content {
  text-align: center;
}

.ai-loading-spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #fde68a;
  border-top-color: #f59e0b;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

.ai-loading-text {
  font-weight: 600;
  color: #92400e;
  font-size: 1.125rem;
  margin-bottom: 4px;
}

.ai-loading-hint {
  color: #a16207;
  font-size: 0.875rem;
}

/* Footer */
.editor-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
  border-radius: 0 0 12px 12px;
}

.word-count {
  font-size: 0.875rem;
  color: #6b7280;
}

.saving-indicator {
  font-size: 0.875rem;
  color: #4f46e5;
}

@media (max-width: 640px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .header-left {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .header-actions {
    width: 100%;
    justify-content: flex-end;
  }
}
</style>
