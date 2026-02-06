<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useStoriesStore } from '@/stores/stories'
import { uploadApi, aiApi } from '@/services/api'
import AppHeader from '@/components/layout/AppHeader.vue'

const route = useRoute()
const router = useRouter()
const storiesStore = useStoriesStore()

const story = computed(() => storiesStore.currentStory)
const loading = computed(() => storiesStore.loading)
const error = computed(() => storiesStore.error)

const activeTab = ref<'chapters' | 'settings'>('chapters')
const newChapterTitle = ref('')
const editingChapter = ref<string | null>(null)
const chapterContent = ref('')

const form = ref({
  title: '',
  description: '',
  is_public: true,
  rating: '0+',
  cover_url: '' as string | null,
})

const coverUploading = ref(false)
const coverError = ref('')

const handleCoverUpload = async (event: Event) => {
  const input = event.target as HTMLInputElement
  if (!input.files || !input.files[0]) return

  const file = input.files[0]

  // Validate file size (5MB max)
  if (file.size > 5 * 1024 * 1024) {
    coverError.value = 'Файл занадто великий (макс. 5MB)'
    return
  }

  // Validate file type
  if (!file.type.startsWith('image/')) {
    coverError.value = 'Дозволені лише зображення'
    return
  }

  coverError.value = ''
  coverUploading.value = true

  try {
    const res = await uploadApi.upload(file, 'cover')
    form.value.cover_url = res.data.url
  } catch (err: unknown) {
    const e = err as { response?: { data?: { message?: string } } }
    coverError.value = e.response?.data?.message || 'Помилка завантаження'
  } finally {
    coverUploading.value = false
    input.value = ''
  }
}

const removeCover = () => {
  form.value.cover_url = null
}

const ratingOptions = [
  { value: '0+', label: '0+ — Для всіх' },
  { value: '6+', label: '6+ — Від 6 років' },
  { value: '12+', label: '12+ — Від 12 років' },
  { value: '16+', label: '16+ — Від 16 років' },
  { value: '18+', label: '18+ — Для дорослих' },
]

const saveSettings = async () => {
  if (!story.value) return

  const result = await storiesStore.updateStory(story.value.id, {
    ...form.value,
    cover_url: form.value.cover_url ?? undefined,
  })
  if (!result.success) {
    alert(result.message || 'Помилка збереження')
  }
}

const publishStory = async () => {
  if (!story.value) return

  if (!story.value.chapters || story.value.chapters.length === 0) {
    alert('Додайте хоча б одну главу перед публікацією')
    return
  }

  const result = await storiesStore.publishStory(story.value.id)
  if (result.success) {
    alert('Історію опубліковано!')
  } else {
    alert(result.message || 'Помилка публікації')
  }
}

const deleteStory = async () => {
  if (!story.value) return

  if (!confirm('Ви впевнені, що хочете видалити цю історію? Цю дію не можна скасувати.')) {
    return
  }

  const result = await storiesStore.deleteStory(story.value.id)
  if (result.success) {
    router.push('/dashboard')
  } else {
    alert(result.message || 'Помилка видалення')
  }
}

const addChapter = async () => {
  if (!story.value) return

  const result = await storiesStore.createChapter(story.value.id, {
    title: newChapterTitle.value || undefined,
  })

  if (result.success && result.chapter) {
    newChapterTitle.value = ''
    editingChapter.value = result.chapter.id
    chapterContent.value = ''
  } else {
    alert(result.message || 'Помилка створення глави')
  }
}

const selectChapter = async (chapterId: string) => {
  if (!story.value) return

  const chapter = await storiesStore.fetchChapter(story.value.id, chapterId)
  if (chapter) {
    editingChapter.value = chapterId
    chapterContent.value = chapter.content || ''
  }
}

const saveChapter = async () => {
  if (!editingChapter.value) return

  const result = await storiesStore.updateChapter(editingChapter.value, {
    content: chapterContent.value,
  })

  if (!result.success) {
    alert(result.message || 'Помилка збереження')
  }
}

const deleteChapter = async (chapterId: string) => {
  if (!confirm('Видалити цю главу?')) return

  const result = await storiesStore.deleteChapter(chapterId)
  if (result.success) {
    if (editingChapter.value === chapterId) {
      editingChapter.value = null
      chapterContent.value = ''
    }
    // Refresh story
    if (story.value) {
      await storiesStore.fetchStory(story.value.id)
    }
  } else {
    alert(result.message || 'Помилка видалення')
  }
}

// AI functionality
const aiLoading = ref(false)
const aiError = ref('')
const aiSuggestions = ref('')
const showAiPanel = ref(false)
const aiPrompt = ref('')
const adultMode = ref(false)

// Check if story has adult rating
const isAdultStory = computed(() => {
  return story.value && ['R', 'NC-17', '18+'].includes(story.value.rating)
})

const continueWithAI = async () => {
  if (!story.value) return

  aiLoading.value = true
  aiError.value = ''

  try {
    const res = await aiApi.continueWriting(story.value.id, {
      chapter_id: editingChapter.value || undefined,
      prompt: aiPrompt.value || undefined,
    })

    // Append AI text to current content
    if (res.data.text) {
      if (chapterContent.value) {
        chapterContent.value += '\n\n' + res.data.text
      } else {
        chapterContent.value = res.data.text
      }
    }
    aiPrompt.value = ''
    showAiPanel.value = false
  } catch (err: unknown) {
    const e = err as { response?: { status?: number; data?: { message?: string } } }
    if (e.response?.status === 429) {
      aiError.value = 'Ліміт AI запитів вичерпано. Спробуйте пізніше.'
    } else {
      aiError.value = e.response?.data?.message || 'Помилка AI сервісу'
    }
  } finally {
    aiLoading.value = false
  }
}

const getAiSuggestions = async () => {
  if (!story.value) return

  aiLoading.value = true
  aiError.value = ''
  aiSuggestions.value = ''

  try {
    const res = await aiApi.getSuggestions(story.value.id, {
      chapter_id: editingChapter.value || undefined,
    })
    aiSuggestions.value = res.data.suggestions
  } catch (err: unknown) {
    const e = err as { response?: { status?: number; data?: { message?: string } } }
    if (e.response?.status === 429) {
      aiError.value = 'Ліміт AI запитів вичерпано. Спробуйте пізніше.'
    } else {
      aiError.value = e.response?.data?.message || 'Помилка AI сервісу'
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

// Adult content AI (Ollama - uncensored)
const continueWithAdultAI = async () => {
  if (!story.value) return

  aiLoading.value = true
  aiError.value = ''

  try {
    const res = await aiApi.continueWritingAdult(story.value.id, {
      chapter_id: editingChapter.value || undefined,
      prompt: aiPrompt.value || undefined,
    })

    if (res.data.text) {
      if (chapterContent.value) {
        chapterContent.value += '\n\n' + res.data.text
      } else {
        chapterContent.value = res.data.text
      }
    }
    aiPrompt.value = ''
    showAiPanel.value = false
  } catch (err: unknown) {
    const e = err as { response?: { status?: number; data?: { message?: string } } }
    if (e.response?.status === 503) {
      aiError.value = 'Ollama не запущено. Запустіть: docker compose up ollama'
    } else if (e.response?.status === 403) {
      aiError.value = 'Цей режим доступний лише для історій з рейтингом 18+'
    } else {
      aiError.value = e.response?.data?.message || 'Помилка AI сервісу'
    }
  } finally {
    aiLoading.value = false
  }
}

// Count words (supports Ukrainian/Cyrillic)
const countWords = (text: string): number => {
  if (!text) return 0
  // Remove markdown formatting
  const cleaned = text.replace(/[*_~`#[\]()>-]+/g, ' ')
  // Match words (Latin + Cyrillic + numbers + apostrophes)
  const matches = cleaned.match(/[\p{L}\p{N}']+/gu)
  return matches ? matches.length : 0
}

watch(story, (newStory) => {
  if (newStory) {
    form.value = {
      title: newStory.title,
      description: newStory.description || '',
      is_public: newStory.is_public,
      rating: newStory.rating,
      cover_url: newStory.cover_url || null,
    }
  }
}, { immediate: true })

onMounted(async () => {
  const storyId = route.params.id as string
  await storiesStore.fetchStory(storyId)
})
</script>

<template>
  <div class="edit-page">
    <AppHeader />

    <main class="main">
      <!-- Loading State -->
      <div v-if="loading && !story" class="loading">
        <div class="spinner"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error && !story" class="card error-card">
        <div class="error-icon">😔</div>
        <h3 class="error-title">{{ error }}</h3>
      </div>

      <!-- Editor -->
      <div v-else-if="story" class="editor-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
          <!-- Story Info -->
          <div class="card story-info">
            <h2 class="story-title">{{ story.title }}</h2>
            <div class="story-meta">
              <span :class="['status-badge', story.status === 'draft' ? 'status-draft' : 'status-published']">
                {{ story.status === 'draft' ? 'Чернетка' : 'Опубліковано' }}
              </span>
              <span>{{ (story.word_count ?? 0).toLocaleString() }} слів</span>
            </div>

            <button
              v-if="story.status === 'draft'"
              @click="publishStory"
              class="btn-publish"
            >
              🚀 Опублікувати
            </button>
            <router-link
              :to="`/stories/${story.id}`"
              class="btn-view"
            >
              👁 Переглянути
            </router-link>
          </div>

          <!-- Tabs -->
          <div class="card tabs-card">
            <div class="tabs">
              <button
                @click="activeTab = 'chapters'"
                :class="['tab', { active: activeTab === 'chapters' }]"
              >
                Глави
              </button>
              <button
                @click="activeTab = 'settings'"
                :class="['tab', { active: activeTab === 'settings' }]"
              >
                Налаштування
              </button>
            </div>

            <!-- Chapters Tab -->
            <div v-if="activeTab === 'chapters'" class="tab-content">
              <!-- Add Chapter -->
              <div class="add-chapter">
                <input
                  v-model="newChapterTitle"
                  type="text"
                  placeholder="Назва глави..."
                  class="chapter-input"
                  @keyup.enter="addChapter"
                />
                <button @click="addChapter" class="btn-add">+</button>
              </div>

              <!-- Chapter List -->
              <div class="chapters-list">
                <div
                  v-for="chapter in story.chapters"
                  :key="chapter.id"
                  :class="['chapter-item', { active: editingChapter === chapter.id }]"
                  @click="selectChapter(chapter.id)"
                >
                  <span class="chapter-number">{{ chapter.chapter_number }}</span>
                  <div class="chapter-info">
                    <p class="chapter-title">
                      {{ chapter.title || `Глава ${chapter.chapter_number}` }}
                    </p>
                    <p class="chapter-words">{{ chapter.word_count }} слів</p>
                  </div>
                  <button
                    @click.stop="deleteChapter(chapter.id)"
                    class="btn-delete"
                  >
                    🗑
                  </button>
                </div>

                <p v-if="!story.chapters || story.chapters.length === 0" class="empty-chapters">
                  Ще немає глав
                </p>
              </div>
            </div>

            <!-- Settings Tab -->
            <div v-if="activeTab === 'settings'" class="tab-content settings">
              <div class="form-group">
                <label class="form-label">Назва</label>
                <input
                  v-model="form.title"
                  type="text"
                  class="form-input"
                />
              </div>

              <div class="form-group">
                <label class="form-label">Опис</label>
                <textarea
                  v-model="form.description"
                  rows="3"
                  class="form-input form-textarea"
                ></textarea>
              </div>

              <div class="form-group">
                <label class="form-label">Віковий рейтинг</label>
                <select v-model="form.rating" class="form-input form-select">
                  <option v-for="option in ratingOptions" :key="option.value" :value="option.value">
                    {{ option.label }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Обкладинка</label>
                <div class="cover-upload">
                  <div v-if="form.cover_url" class="cover-preview">
                    <img :src="form.cover_url" alt="Обкладинка" />
                    <button @click="removeCover" class="cover-remove" title="Видалити обкладинку">✕</button>
                  </div>
                  <label v-else class="cover-dropzone" :class="{ uploading: coverUploading }">
                    <input
                      type="file"
                      accept="image/*"
                      @change="handleCoverUpload"
                      :disabled="coverUploading"
                      class="cover-input"
                    />
                    <span v-if="coverUploading" class="cover-text">Завантаження...</span>
                    <span v-else class="cover-text">
                      <span class="cover-icon">📷</span>
                      Натисніть або перетягніть
                    </span>
                  </label>
                  <p v-if="coverError" class="cover-error">{{ coverError }}</p>
                </div>
              </div>

              <label class="checkbox-label">
                <input
                  type="checkbox"
                  v-model="form.is_public"
                  class="checkbox"
                />
                <span>Публічна історія</span>
              </label>

              <button @click="saveSettings" class="btn-save">
                Зберегти налаштування
              </button>

              <button @click="deleteStory" class="btn-danger">
                Видалити історію
              </button>
            </div>
          </div>
        </aside>

        <!-- Editor Area -->
        <div class="editor-area">
          <div class="editor-card">
            <div v-if="editingChapter" class="editor-content">
              <!-- Chapter Header -->
              <div class="editor-header">
                <h3 class="editor-title">
                  {{ story.chapters?.find(c => c.id === editingChapter)?.title || 'Редагування глави' }}
                </h3>
                <div class="editor-actions">
                  <button
                    @click="toggleAiPanel"
                    :class="['btn-ai', { active: showAiPanel }]"
                  >
                    🤖 AI
                  </button>
                  <button
                    @click="saveChapter"
                    :disabled="loading"
                    class="btn-save-chapter"
                  >
                    {{ loading ? 'Збереження...' : 'Зберегти' }}
                  </button>
                </div>
              </div>

              <!-- AI Panel -->
              <div v-if="showAiPanel" class="ai-panel">
                <div class="ai-panel-header">
                  <h4 class="ai-panel-title">🤖 AI Помічник</h4>
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
                  <!-- AI Prompt -->
                  <div class="ai-prompt-group">
                    <label class="ai-label">Інструкція для AI (необов'язково)</label>
                    <input
                      v-model="aiPrompt"
                      type="text"
                      class="ai-input"
                      :placeholder="adultMode ? 'Наприклад: напиши еротичну сцену між...' : 'Наприклад: додай діалог між персонажами'"
                    />
                  </div>

                  <!-- AI Actions -->
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

                  <!-- AI Error -->
                  <div v-if="aiError" class="ai-error">
                    {{ aiError }}
                  </div>

                  <!-- AI Suggestions -->
                  <div v-if="aiSuggestions" class="ai-suggestions">
                    <h5 class="ai-suggestions-title">Ідеї:</h5>
                    <pre class="ai-suggestions-text">{{ aiSuggestions }}</pre>
                  </div>
                </div>
              </div>

              <!-- Text Editor -->
              <div class="editor-wrapper">
                <textarea
                  v-model="chapterContent"
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

              <!-- Word Count -->
              <div class="editor-footer">
                {{ countWords(chapterContent) }} слів
              </div>
            </div>

            <!-- No Chapter Selected -->
            <div v-else class="no-chapter">
              <div class="no-chapter-icon">📝</div>
              <p>Оберіть главу для редагування або створіть нову</p>
            </div>
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
  max-width: 1280px;
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

.card {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  padding: 16px;
  margin-bottom: 16px;
}

/* Error State */
.error-card {
  text-align: center;
  padding: 48px;
}

.error-icon {
  font-size: 4rem;
  margin-bottom: 16px;
}

.error-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #111827;
}

/* Editor Layout */
.editor-layout {
  display: flex;
  gap: 24px;
}

/* Sidebar */
.sidebar {
  width: 320px;
  flex-shrink: 0;
}

.story-info {
  margin-bottom: 16px;
}

.story-title {
  font-weight: 600;
  color: #111827;
  margin-bottom: 8px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.story-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 16px;
}

.status-badge {
  padding: 2px 8px;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
}

.status-draft {
  background: #fef3c7;
  color: #92400e;
}

.status-published {
  background: #dcfce7;
  color: #166534;
}

.btn-publish {
  width: 100%;
  padding: 8px 16px;
  background: #16a34a;
  color: #fff;
  border-radius: 8px;
  font-weight: 500;
  margin-bottom: 8px;
  transition: background 0.2s;
}

.btn-publish:hover {
  background: #15803d;
}

.btn-view {
  display: block;
  width: 100%;
  text-align: center;
  padding: 8px 16px;
  background: #f3f4f6;
  color: #374151;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-view:hover {
  background: #e5e7eb;
}

/* Tabs */
.tabs-card {
  padding: 0;
  overflow: hidden;
}

.tabs {
  display: flex;
  border-bottom: 1px solid #e5e7eb;
}

.tab {
  flex: 1;
  padding: 12px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #6b7280;
  border-bottom: 2px solid transparent;
  transition: all 0.2s;
}

.tab:hover {
  color: #4f46e5;
}

.tab.active {
  color: #4f46e5;
  border-bottom-color: #4f46e5;
}

.tab-content {
  padding: 16px;
}

/* Add Chapter */
.add-chapter {
  display: flex;
  gap: 8px;
  margin-bottom: 16px;
}

.chapter-input {
  flex: 1;
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  outline: none;
}

.chapter-input:focus {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.btn-add {
  padding: 8px 16px;
  background: #4f46e5;
  color: #fff;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-add:hover {
  background: #4338ca;
}

/* Chapter List */
.chapters-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.chapter-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.chapter-item:hover {
  background: #f9fafb;
}

.chapter-item.active {
  background: #eef2ff;
  border-color: #a5b4fc;
}

.chapter-number {
  width: 24px;
  height: 24px;
  background: #f3f4f6;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  flex-shrink: 0;
}

.chapter-info {
  flex: 1;
  min-width: 0;
}

.chapter-title {
  font-size: 0.875rem;
  font-weight: 500;
  color: #111827;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.chapter-words {
  font-size: 0.75rem;
  color: #6b7280;
}

.btn-delete {
  padding: 4px;
  color: #9ca3af;
  transition: color 0.2s;
}

.btn-delete:hover {
  color: #dc2626;
}

.empty-chapters {
  font-size: 0.875rem;
  color: #6b7280;
  text-align: center;
  padding: 16px;
}

/* Settings */
.settings {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

.form-input {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  outline: none;
}

.form-input:focus {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.form-textarea {
  resize: none;
}

.form-select {
  cursor: pointer;
}

/* Cover Upload */
.cover-upload {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.cover-preview {
  position: relative;
  width: 100%;
  aspect-ratio: 16 / 9;
  border-radius: 8px;
  overflow: hidden;
}

.cover-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.cover-remove {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 28px;
  height: 28px;
  background: rgba(0, 0, 0, 0.6);
  color: #fff;
  border-radius: 50%;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.cover-remove:hover {
  background: #dc2626;
}

.cover-dropzone {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 24px;
  border: 2px dashed #d1d5db;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.cover-dropzone:hover {
  border-color: #4f46e5;
  background: #f9fafb;
}

.cover-dropzone.uploading {
  opacity: 0.5;
  cursor: not-allowed;
}

.cover-input {
  display: none;
}

.cover-text {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  font-size: 0.875rem;
  color: #6b7280;
}

.cover-icon {
  font-size: 1.5rem;
}

.cover-error {
  font-size: 0.75rem;
  color: #dc2626;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 0.875rem;
  color: #374151;
}

.checkbox {
  width: 16px;
  height: 16px;
  accent-color: #4f46e5;
}

.btn-save {
  width: 100%;
  padding: 8px 16px;
  background: #4f46e5;
  color: #fff;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-save:hover {
  background: #4338ca;
}

.btn-danger {
  width: 100%;
  padding: 8px 16px;
  color: #dc2626;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-danger:hover {
  background: #fef2f2;
}

/* Editor Area */
.editor-area {
  flex: 1;
  min-width: 0;
}

.editor-card {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  min-height: 600px;
  display: flex;
  flex-direction: column;
}

.editor-content {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.editor-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  border-bottom: 1px solid #e5e7eb;
}

.editor-title {
  font-weight: 500;
  color: #111827;
}

.editor-actions {
  display: flex;
  gap: 8px;
}

.btn-ai {
  padding: 8px 16px;
  background: #fef3c7;
  color: #92400e;
  border-radius: 8px;
  font-size: 0.875rem;
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

/* AI Panel */
.ai-panel {
  border-bottom: 1px solid #e5e7eb;
  background: #fffbeb;
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

/* Adult mode styles */
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

.btn-save-chapter {
  padding: 8px 16px;
  background: #4f46e5;
  color: #fff;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-save-chapter:hover:not(:disabled) {
  background: #4338ca;
}

.btn-save-chapter:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.editor-wrapper {
  flex: 1;
  position: relative;
  display: flex;
}

.text-editor {
  flex: 1;
  padding: 24px;
  border: none;
  resize: none;
  font-size: 1rem;
  line-height: 1.75;
  color: #111827;
  outline: none;
}

.text-editor::placeholder {
  color: #9ca3af;
}

.text-editor:disabled {
  background: #f9fafb;
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

.editor-footer {
  padding: 8px 16px;
  border-top: 1px solid #e5e7eb;
  font-size: 0.875rem;
  color: #6b7280;
}

/* No Chapter */
.no-chapter {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #6b7280;
}

.no-chapter-icon {
  font-size: 3rem;
  margin-bottom: 16px;
}

@media (max-width: 1024px) {
  .editor-layout {
    flex-direction: column;
  }

  .sidebar {
    width: 100%;
  }
}
</style>
