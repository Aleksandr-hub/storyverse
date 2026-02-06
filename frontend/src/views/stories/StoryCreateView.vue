<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useStoriesStore } from '@/stores/stories'
import { useErrorHandler, useToast } from '@/composables'
import AppHeader from '@/components/layout/AppHeader.vue'

const router = useRouter()
const storiesStore = useStoriesStore()
const { error, clearError, setError, getFieldError, hasFieldError } = useErrorHandler()
const toast = useToast()

const universes = computed(() => storiesStore.universes)
const tags = computed(() => storiesStore.tags)
const loading = computed(() => storiesStore.loading)

const form = ref({
  title: '',
  description: '',
  universe_id: '',
  mode: 'story',
  is_public: true,
  rating: '0+',
  tags: [] as string[],
})

const ratingOptions = [
  { value: '0+', label: '0+', description: 'Для всіх вікових груп' },
  { value: '6+', label: '6+', description: 'Для дітей від 6 років' },
  { value: '12+', label: '12+', description: 'Для підлітків від 12 років' },
  { value: '16+', label: '16+', description: 'Для підлітків від 16 років' },
  { value: '18+', label: '18+', description: 'Тільки для дорослих' },
]

const handleSubmit = async () => {
  clearError()

  const result = await storiesStore.createStory({
    ...form.value,
    universe_id: form.value.universe_id || undefined,
    tags: form.value.tags.length > 0 ? form.value.tags : undefined,
  })

  if (result.success && result.story) {
    toast.success('Історію створено!')
    router.push(`/stories/${result.story.id}/edit`)
  } else {
    setError(result.message || 'Помилка створення історії', result.errors || {})
  }
}

const toggleTag = (tagId: string) => {
  const index = form.value.tags.indexOf(tagId)
  if (index > -1) {
    form.value.tags.splice(index, 1)
  } else {
    form.value.tags.push(tagId)
  }
}

onMounted(async () => {
  await Promise.all([
    storiesStore.fetchUniverses(),
    storiesStore.fetchTags(),
  ])
})
</script>

<template>
  <div class="create-page">
    <AppHeader />

    <main class="main">
      <!-- Header -->
      <div class="page-header">
        <h1 class="page-title">Нова історія</h1>
        <p class="page-subtitle">Створи нову історію та почни писати</p>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="form-card">
        <!-- Error Alert -->
        <div v-if="error.message" class="error-alert">
          {{ error.message }}
        </div>

        <!-- Title -->
        <div class="form-group">
          <label for="title" class="form-label">Назва історії *</label>
          <input
            id="title"
            v-model="form.title"
            type="text"
            required
            class="form-input"
            :class="{ 'input-error': hasFieldError('title') }"
            placeholder="Введіть назву..."
          />
          <p v-if="getFieldError('title')" class="field-error">{{ getFieldError('title') }}</p>
        </div>

        <!-- Description -->
        <div class="form-group">
          <label for="description" class="form-label">Опис</label>
          <textarea
            id="description"
            v-model="form.description"
            rows="4"
            class="form-input form-textarea"
            :class="{ 'input-error': hasFieldError('description') }"
            placeholder="Коротко опишіть вашу історію..."
          ></textarea>
          <p v-if="getFieldError('description')" class="field-error">{{ getFieldError('description') }}</p>
        </div>

        <!-- Universe -->
        <div class="form-group">
          <label for="universe" class="form-label">Всесвіт</label>
          <select id="universe" v-model="form.universe_id" class="form-input form-select">
            <option value="">Оригінальний світ</option>
            <option v-for="universe in universes" :key="universe.id" :value="universe.id">
              {{ universe.name }}
            </option>
          </select>
        </div>

        <!-- Mode -->
        <div class="form-group">
          <label class="form-label">Тип історії</label>
          <div class="mode-grid">
            <label :class="['mode-card', { active: form.mode === 'story' }]">
              <input type="radio" v-model="form.mode" value="story" class="sr-only" />
              <span class="mode-icon">📝</span>
              <div>
                <p class="mode-title">Story Mode</p>
                <p class="mode-subtitle">Класичне писання</p>
              </div>
            </label>

            <label class="mode-card disabled">
              <input type="radio" disabled class="sr-only" />
              <span class="mode-icon">🤝</span>
              <div>
                <p class="mode-title">Collaborative</p>
                <p class="mode-subtitle">Скоро</p>
              </div>
            </label>

            <label class="mode-card disabled">
              <input type="radio" disabled class="sr-only" />
              <span class="mode-icon">🎮</span>
              <div>
                <p class="mode-title">Adventure</p>
                <p class="mode-subtitle">Скоро</p>
              </div>
            </label>
          </div>
        </div>

        <!-- Rating -->
        <div class="form-group">
          <label class="form-label">Віковий рейтинг</label>
          <div class="rating-group">
            <label
              v-for="option in ratingOptions"
              :key="option.value"
              :class="['rating-btn', { active: form.rating === option.value }]"
              :title="option.description"
            >
              <input type="radio" v-model="form.rating" :value="option.value" class="sr-only" />
              {{ option.label }}
            </label>
          </div>
        </div>

        <!-- Visibility -->
        <div class="form-group">
          <label class="checkbox-label">
            <input type="checkbox" v-model="form.is_public" class="checkbox" />
            <div>
              <p class="checkbox-title">Публічна історія</p>
              <p class="checkbox-subtitle">Інші користувачі зможуть читати вашу історію</p>
            </div>
          </label>
        </div>

        <!-- Tags -->
        <div class="form-group">
          <label class="form-label">Теги</label>
          <div class="tags-group">
            <button
              v-for="tag in tags"
              :key="tag.id"
              type="button"
              @click="toggleTag(tag.id)"
              :class="['tag-btn', { active: form.tags.includes(tag.id) }]"
            >
              {{ tag.name }}
            </button>
          </div>
        </div>

        <!-- Submit -->
        <div class="form-actions">
          <button type="button" @click="router.back()" class="btn-secondary">
            Скасувати
          </button>
          <button type="submit" :disabled="loading || !form.title" class="btn-primary">
            <span v-if="loading">Створення...</span>
            <span v-else>Створити історію</span>
          </button>
        </div>
      </form>
    </main>
  </div>
</template>

<style scoped>
.create-page {
  min-height: 100vh;
  background: #f9fafb;
}

.main {
  max-width: 720px;
  margin: 0 auto;
  padding: 32px 20px;
}

.page-header {
  margin-bottom: 24px;
}

.page-title {
  font-size: 1.875rem;
  font-weight: 700;
  color: #111827;
}

.page-subtitle {
  color: #6b7280;
  margin-top: 4px;
}

.form-card {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.error-alert {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 0.875rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

.form-input {
  padding: 12px 16px;
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

.form-textarea {
  resize: vertical;
  min-height: 100px;
}

.form-select {
  cursor: pointer;
}

.input-error {
  border-color: #dc2626;
}

.field-error {
  color: #dc2626;
  font-size: 0.875rem;
}

/* Mode */
.mode-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 16px;
}

.mode-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
}

.mode-card:hover:not(.disabled) {
  border-color: #a5b4fc;
}

.mode-card.active {
  border-color: #4f46e5;
  background: #eef2ff;
}

.mode-card.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.mode-icon {
  font-size: 1.5rem;
}

.mode-title {
  font-weight: 500;
  color: #111827;
}

.mode-subtitle {
  font-size: 0.75rem;
  color: #6b7280;
}

/* Rating */
.rating-group {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.rating-btn {
  padding: 8px 16px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  color: #374151;
  transition: all 0.2s;
}

.rating-btn:hover {
  border-color: #a5b4fc;
}

.rating-btn.active {
  border-color: #4f46e5;
  background: #eef2ff;
  color: #4338ca;
}

/* Checkbox */
.checkbox-label {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  cursor: pointer;
}

.checkbox {
  width: 20px;
  height: 20px;
  margin-top: 2px;
  accent-color: #4f46e5;
}

.checkbox-title {
  font-weight: 500;
  color: #111827;
}

.checkbox-subtitle {
  font-size: 0.875rem;
  color: #6b7280;
}

/* Tags */
.tags-group {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.tag-btn {
  padding: 6px 12px;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 500;
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #e5e7eb;
  transition: all 0.2s;
}

.tag-btn:hover {
  background: #eef2ff;
}

.tag-btn.active {
  background: #eef2ff;
  color: #4338ca;
  border-color: #a5b4fc;
}

/* Actions */
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 16px;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}

.btn-primary {
  background: #4f46e5;
  color: #fff;
  padding: 12px 24px;
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
  color: #374151;
  padding: 12px 24px;
  font-weight: 500;
  transition: color 0.2s;
}

.btn-secondary:hover {
  color: #111827;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  border: 0;
}
</style>
