<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { useStoriesStore } from '@/stores/stories'
import { uploadApi } from '@/services/api'
import AppHeader from '@/components/layout/AppHeader.vue'

const route = useRoute()
const router = useRouter()
const storiesStore = useStoriesStore()

const story = computed(() => storiesStore.currentStory)
const loading = computed(() => storiesStore.loading)
const error = computed(() => storiesStore.error)

const newChapterTitle = ref('')

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

  if (file.size > 5 * 1024 * 1024) {
    coverError.value = 'Файл занадто великий (макс. 5MB)'
    return
  }

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
  } else {
    alert('Налаштування збережено')
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
    // Navigate to chapter editor
    router.push(`/stories/${story.value.id}/chapters/${result.chapter.id}/edit`)
  } else {
    alert(result.message || 'Помилка створення глави')
  }
}

const editChapter = (chapterId: string) => {
  if (!story.value) return
  router.push(`/stories/${story.value.id}/chapters/${chapterId}/edit`)
}

const deleteChapter = async (chapterId: string) => {
  if (!confirm('Видалити цю главу?')) return

  const result = await storiesStore.deleteChapter(chapterId)
  if (result.success) {
    if (story.value) {
      await storiesStore.fetchStory(story.value.id)
    }
  } else {
    alert(result.message || 'Помилка видалення')
  }
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

      <!-- Content -->
      <div v-else-if="story">
        <!-- Header -->
        <div class="page-header">
          <div class="header-left">
            <RouterLink to="/dashboard" class="back-link">← Мої історії</RouterLink>
            <h1 class="page-title">{{ story.title }}</h1>
            <div class="story-meta">
              <span :class="['status-badge', story.status === 'draft' ? 'status-draft' : 'status-published']">
                {{ story.status === 'draft' ? 'Чернетка' : 'Опубліковано' }}
              </span>
              <span class="meta-divider">•</span>
              <span>{{ (story.word_count ?? 0).toLocaleString() }} слів</span>
              <span class="meta-divider">•</span>
              <span>{{ story.chapters?.length ?? 0 }} глав</span>
            </div>
          </div>
          <div class="header-actions">
            <RouterLink :to="`/stories/${story.id}`" class="btn-secondary">
              👁 Переглянути
            </RouterLink>
            <button
              v-if="story.status === 'draft'"
              @click="publishStory"
              class="btn-primary"
            >
              🚀 Опублікувати
            </button>
          </div>
        </div>

        <!-- Two Column Layout -->
        <div class="content-grid">
          <!-- Left: Chapters -->
          <div class="card chapters-card">
            <div class="card-header">
              <h2 class="card-title">Глави</h2>
            </div>

            <!-- Add Chapter -->
            <div class="add-chapter">
              <input
                v-model="newChapterTitle"
                type="text"
                placeholder="Назва нової глави..."
                class="chapter-input"
                @keyup.enter="addChapter"
              />
              <button @click="addChapter" class="btn-add">+ Додати</button>
            </div>

            <!-- Chapter List -->
            <div class="chapters-list">
              <div
                v-for="chapter in story.chapters"
                :key="chapter.id"
                class="chapter-item"
              >
                <div class="chapter-main" @click="editChapter(chapter.id)">
                  <span class="chapter-number">{{ chapter.chapter_number }}</span>
                  <div class="chapter-info">
                    <p class="chapter-title">
                      {{ chapter.title || `Глава ${chapter.chapter_number}` }}
                    </p>
                    <p class="chapter-words">{{ chapter.word_count }} слів</p>
                  </div>
                  <span class="chapter-arrow">→</span>
                </div>
                <button
                  @click.stop="deleteChapter(chapter.id)"
                  class="btn-delete"
                  title="Видалити главу"
                >
                  🗑
                </button>
              </div>

              <div v-if="!story.chapters || story.chapters.length === 0" class="empty-chapters">
                <div class="empty-icon">📝</div>
                <p>Ще немає глав</p>
                <p class="empty-hint">Створіть першу главу, щоб почати писати</p>
              </div>
            </div>
          </div>

          <!-- Right: Settings -->
          <div class="card settings-card">
            <div class="card-header">
              <h2 class="card-title">Налаштування</h2>
            </div>

            <div class="settings-content">
              <div class="form-group">
                <label class="form-label">Назва історії</label>
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
                  rows="4"
                  class="form-input form-textarea"
                  placeholder="Коротко опишіть вашу історію..."
                ></textarea>
              </div>

              <div class="form-group">
                <label class="form-label">Обкладинка</label>
                <div class="cover-upload">
                  <div v-if="form.cover_url" class="cover-preview">
                    <img :src="form.cover_url" alt="Обкладинка" />
                    <button @click="removeCover" class="cover-remove" title="Видалити">✕</button>
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
                      Натисніть для вибору
                    </span>
                  </label>
                  <p v-if="coverError" class="cover-error">{{ coverError }}</p>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Віковий рейтинг</label>
                <select v-model="form.rating" class="form-input form-select">
                  <option v-for="option in ratingOptions" :key="option.value" :value="option.value">
                    {{ option.label }}
                  </option>
                </select>
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

              <hr class="divider" />

              <button @click="deleteStory" class="btn-danger">
                🗑 Видалити історію
              </button>
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
  max-width: 1100px;
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

/* Error */
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

/* Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
  gap: 16px;
}

.header-left {
  flex: 1;
}

.back-link {
  color: #6b7280;
  font-size: 0.875rem;
  transition: color 0.2s;
}

.back-link:hover {
  color: #4f46e5;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
  margin: 8px 0;
}

.story-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.875rem;
  color: #6b7280;
}

.meta-divider {
  color: #d1d5db;
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

.header-actions {
  display: flex;
  gap: 8px;
}

.btn-primary {
  padding: 10px 20px;
  background: #4f46e5;
  color: #fff;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-primary:hover {
  background: #4338ca;
}

.btn-secondary {
  padding: 10px 20px;
  background: #f3f4f6;
  color: #374151;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

/* Content Grid */
.content-grid {
  display: grid;
  grid-template-columns: 1fr 360px;
  gap: 24px;
}

@media (max-width: 900px) {
  .content-grid {
    grid-template-columns: 1fr;
  }
}

/* Card */
.card {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}

.card-header {
  padding: 16px 20px;
  border-bottom: 1px solid #e5e7eb;
}

.card-title {
  font-size: 1rem;
  font-weight: 600;
  color: #111827;
}

/* Chapters */
.chapters-card {
  align-self: start;
}

.add-chapter {
  display: flex;
  gap: 8px;
  padding: 16px 20px;
  border-bottom: 1px solid #e5e7eb;
}

.chapter-input {
  flex: 1;
  padding: 10px 14px;
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
  padding: 10px 16px;
  background: #4f46e5;
  color: #fff;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  white-space: nowrap;
  transition: background 0.2s;
}

.btn-add:hover {
  background: #4338ca;
}

.chapters-list {
  padding: 8px;
}

.chapter-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px;
}

.chapter-main {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s;
}

.chapter-main:hover {
  background: #f3f4f6;
}

.chapter-number {
  width: 28px;
  height: 28px;
  background: #eef2ff;
  color: #4f46e5;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 600;
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
  margin-top: 2px;
}

.chapter-arrow {
  color: #9ca3af;
  font-size: 1rem;
}

.btn-delete {
  padding: 8px;
  color: #9ca3af;
  border-radius: 6px;
  transition: all 0.2s;
}

.btn-delete:hover {
  color: #dc2626;
  background: #fef2f2;
}

.empty-chapters {
  text-align: center;
  padding: 40px 20px;
  color: #6b7280;
}

.empty-icon {
  font-size: 2.5rem;
  margin-bottom: 12px;
}

.empty-hint {
  font-size: 0.875rem;
  margin-top: 4px;
}

/* Settings */
.settings-card {
  align-self: start;
}

.settings-content {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

.form-input {
  padding: 10px 14px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  outline: none;
  transition: border-color 0.2s;
}

.form-input:focus {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.form-select {
  cursor: pointer;
}

/* Cover */
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

/* Checkbox */
.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 0.875rem;
  color: #374151;
}

.checkbox {
  width: 18px;
  height: 18px;
  accent-color: #4f46e5;
}

/* Buttons */
.btn-save {
  width: 100%;
  padding: 12px 16px;
  background: #4f46e5;
  color: #fff;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-save:hover {
  background: #4338ca;
}

.divider {
  border: none;
  border-top: 1px solid #e5e7eb;
  margin: 8px 0;
}

.btn-danger {
  width: 100%;
  padding: 12px 16px;
  color: #dc2626;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-danger:hover {
  background: #fef2f2;
}

@media (max-width: 640px) {
  .page-header {
    flex-direction: column;
  }

  .header-actions {
    width: 100%;
  }

  .header-actions > * {
    flex: 1;
    text-align: center;
  }
}
</style>
