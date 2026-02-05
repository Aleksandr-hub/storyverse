<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useStoriesStore } from '@/stores/stories'
import { uploadApi, aiApi } from '@/services/api'
import AppHeader from '@/components/layout/AppHeader.vue'

const authStore = useAuthStore()
const storiesStore = useStoriesStore()

const user = computed(() => authStore.user)
const myStories = computed(() => storiesStore.myStories)
const loading = computed(() => storiesStore.loading)
const totalStories = computed(() => storiesStore.pagination.total)
const totalViews = computed(() => myStories.value.reduce((sum, s) => sum + s.view_count, 0))

const avatarUploading = ref(false)
const avatarError = ref('')

// AI Status
interface AIProvider {
  available: boolean
  disabled: boolean
  cost_per_1k_tokens: number
}

const aiStatus = ref<{
  available: boolean
  primary_provider: string | null
  providers: Record<string, AIProvider>
} | null>(null)

const fetchAIStatus = async () => {
  try {
    const res = await aiApi.status()
    aiStatus.value = res.data
  } catch {
    aiStatus.value = null
  }
}

const handleAvatarUpload = async (event: Event) => {
  const input = event.target as HTMLInputElement
  if (!input.files || !input.files[0]) return

  const file = input.files[0]

  if (file.size > 5 * 1024 * 1024) {
    avatarError.value = 'Файл занадто великий (макс. 5MB)'
    return
  }

  if (!file.type.startsWith('image/')) {
    avatarError.value = 'Дозволені лише зображення'
    return
  }

  avatarError.value = ''
  avatarUploading.value = true

  try {
    const res = await uploadApi.upload(file, 'avatar')
    await authStore.updateProfile({ avatar_url: res.data.url })
  } catch (err: any) {
    avatarError.value = err.response?.data?.message || 'Помилка завантаження'
  } finally {
    avatarUploading.value = false
    input.value = ''
  }
}

onMounted(async () => {
  await Promise.all([
    storiesStore.fetchMyStories({ per_page: 6 }),
    fetchAIStatus()
  ])
})

const getStatusClass = (status: string) => {
  if (status === 'draft') return 'status-draft'
  if (status === 'published') return 'status-published'
  return 'status-completed'
}

const getStatusText = (status: string) => {
  if (status === 'draft') return 'Чернетка'
  if (status === 'published') return 'Опубліковано'
  return 'Завершено'
}
</script>

<template>
  <div class="dashboard">
    <AppHeader />

    <main class="main">
      <!-- Welcome Section -->
      <div class="card welcome-card">
        <div class="welcome-content">
          <label class="avatar-wrapper" :class="{ uploading: avatarUploading }">
            <div class="avatar">
              <img v-if="user?.avatar_url" :src="user.avatar_url" :alt="user.username" />
              <span v-else>{{ user?.username?.charAt(0).toUpperCase() || '?' }}</span>
            </div>
            <div class="avatar-overlay">
              <span v-if="avatarUploading">...</span>
              <span v-else>📷</span>
            </div>
            <input
              type="file"
              accept="image/*"
              @change="handleAvatarUpload"
              :disabled="avatarUploading"
              class="avatar-input"
            />
          </label>
          <div>
            <h1 class="welcome-title">Привіт, {{ user?.username }}!</h1>
            <p class="welcome-subtitle">Ласкаво просимо до StoryVerse</p>
            <p v-if="avatarError" class="avatar-error">{{ avatarError }}</p>
          </div>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="card stat-card">
          <div class="stat-icon stat-icon-blue">📝</div>
          <div>
            <p class="stat-value">{{ totalStories }}</p>
            <p class="stat-label">Моїх історій</p>
          </div>
        </div>

        <div class="card stat-card">
          <div class="stat-icon stat-icon-green">🎮</div>
          <div>
            <p class="stat-value">0</p>
            <p class="stat-label">Пригод</p>
          </div>
        </div>

        <div class="card stat-card">
          <div class="stat-icon stat-icon-purple">👁</div>
          <div>
            <p class="stat-value">{{ totalViews }}</p>
            <p class="stat-label">Переглядів</p>
          </div>
        </div>
      </div>

      <!-- AI Status -->
      <div v-if="aiStatus" class="card ai-status-card">
        <div class="ai-status-header">
          <h2 class="section-title">🤖 AI Провайдери</h2>
          <span :class="['ai-badge', aiStatus.available ? 'ai-badge-ok' : 'ai-badge-error']">
            {{ aiStatus.available ? 'Працює' : 'Недоступно' }}
          </span>
        </div>
        <div class="ai-providers">
          <div
            v-for="(info, name) in aiStatus.providers"
            :key="name"
            :class="['ai-provider', { active: name === aiStatus.primary_provider }]"
          >
            <span class="provider-name">
              {{
                name === 'gemini' ? '🟢 Gemini' :
                name === 'claude' ? '🟣 Claude' :
                name === 'openai' ? '🔵 OpenAI' :
                name === 'ollama' ? '🔴 Ollama' : name
              }}
            </span>
            <span :class="['provider-status', info.available ? 'status-ok' : 'status-off']">
              {{ info.available ? (info.disabled ? '⏸️ Пауза' : '✓') : '✗' }}
            </span>
            <span v-if="name === aiStatus.primary_provider" class="primary-badge">активний</span>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="card">
        <h2 class="section-title">Швидкі дії</h2>
        <div class="actions-grid">
          <RouterLink to="/stories/new" class="action-card action-primary">
            <span class="action-icon">✍️</span>
            <div>
              <p class="action-title">Нова історія</p>
              <p class="action-subtitle">Почни писати</p>
            </div>
          </RouterLink>

          <div class="action-card action-disabled">
            <span class="action-icon">🎲</span>
            <div>
              <p class="action-title">Adventure Mode</p>
              <p class="action-subtitle">Скоро</p>
            </div>
          </div>

          <div class="action-card action-disabled">
            <span class="action-icon">🤝</span>
            <div>
              <p class="action-title">Спільне писання</p>
              <p class="action-subtitle">Скоро</p>
            </div>
          </div>

          <RouterLink to="/stories" class="action-card action-secondary">
            <span class="action-icon">🔍</span>
            <div>
              <p class="action-title">Відкрити</p>
              <p class="action-subtitle">Знайди історії</p>
            </div>
          </RouterLink>
        </div>
      </div>

      <!-- My Stories -->
      <div class="card">
        <div class="section-header">
          <h2 class="section-title">Мої історії</h2>
          <RouterLink v-if="myStories.length > 0" to="/stories?user=me" class="view-all">
            Дивитись всі
          </RouterLink>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading">
          <div class="spinner"></div>
        </div>

        <!-- Empty State -->
        <div v-else-if="myStories.length === 0" class="empty-state">
          <div class="empty-icon">📚</div>
          <h3 class="empty-title">Поки що тут порожньо</h3>
          <p class="empty-text">
            Створи свою першу історію або почни захоплюючу пригоду з AI Dungeon Master!
          </p>
          <RouterLink to="/stories/new" class="btn-primary">
            ✍️ Написати історію
          </RouterLink>
        </div>

        <!-- Stories Grid -->
        <div v-else class="stories-grid">
          <RouterLink
            v-for="story in myStories"
            :key="story.id"
            :to="`/stories/${story.id}`"
            class="story-card"
          >
            <div class="story-header">
              <h3 class="story-title">{{ story.title }}</h3>
              <span :class="['status-badge', getStatusClass(story.status)]">
                {{ getStatusText(story.status) }}
              </span>
            </div>
            <p v-if="story.description" class="story-description">
              {{ story.description }}
            </p>
            <div class="story-meta">
              <span>{{ story.chapters_count || 0 }} глав</span>
              <span>{{ story.word_count.toLocaleString() }} слів</span>
              <span>{{ story.view_count }} переглядів</span>
            </div>
          </RouterLink>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
.dashboard {
  min-height: 100vh;
  background: #f9fafb;
}

.main {
  max-width: 1200px;
  margin: 0 auto;
  padding: 32px 20px;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.card {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  padding: 24px;
}

/* Welcome */
.welcome-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.avatar-wrapper {
  position: relative;
  cursor: pointer;
}

.avatar-wrapper.uploading {
  opacity: 0.5;
  cursor: not-allowed;
}

.avatar {
  width: 64px;
  height: 64px;
  background: #eef2ff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: 600;
  color: #4f46e5;
  overflow: hidden;
}

.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.2s;
  font-size: 1.25rem;
}

.avatar-wrapper:hover .avatar-overlay {
  opacity: 1;
}

.avatar-input {
  display: none;
}

.avatar-error {
  font-size: 0.75rem;
  color: #dc2626;
  margin-top: 4px;
}

.welcome-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
  margin-bottom: 4px;
}

.welcome-subtitle {
  color: #6b7280;
}

/* Stats */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.stat-icon-blue { background: #dbeafe; }
.stat-icon-green { background: #dcfce7; }
.stat-icon-purple { background: #f3e8ff; }

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
}

.stat-label {
  font-size: 0.875rem;
  color: #6b7280;
}

/* Section */
.section-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #111827;
  margin-bottom: 16px;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}

.section-header .section-title {
  margin-bottom: 0;
}

.view-all {
  color: #4f46e5;
  font-size: 0.875rem;
  font-weight: 500;
}

.view-all:hover {
  text-decoration: underline;
}

/* Actions */
.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.action-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  border: 2px dashed #d1d5db;
  border-radius: 12px;
  transition: all 0.2s;
}

.action-icon {
  font-size: 2rem;
}

.action-title {
  font-weight: 500;
  color: #111827;
}

.action-subtitle {
  font-size: 0.875rem;
  color: #6b7280;
}

.action-primary:hover {
  border-color: #4f46e5;
  background: #eef2ff;
}

.action-primary:hover .action-title {
  color: #4f46e5;
}

.action-secondary:hover {
  border-color: #f97316;
  background: #fff7ed;
}

.action-secondary:hover .action-title {
  color: #ea580c;
}

.action-disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Loading */
.loading {
  display: flex;
  justify-content: center;
  padding: 48px;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #e5e7eb;
  border-top-color: #4f46e5;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 48px 20px;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 16px;
}

.empty-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #111827;
  margin-bottom: 8px;
}

.empty-text {
  color: #6b7280;
  max-width: 400px;
  margin: 0 auto 24px;
}

.btn-primary {
  display: inline-block;
  background: #4f46e5;
  color: #fff;
  padding: 12px 24px;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-primary:hover {
  background: #4338ca;
}

/* Stories Grid */
.stories-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 16px;
}

.story-card {
  display: block;
  padding: 16px;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  transition: all 0.2s;
}

.story-card:hover {
  border-color: #a5b4fc;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.story-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 8px;
}

.story-title {
  font-weight: 600;
  color: #111827;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.status-badge {
  font-size: 0.75rem;
  padding: 4px 8px;
  border-radius: 9999px;
  font-weight: 500;
  white-space: nowrap;
}

.status-draft {
  background: #fef3c7;
  color: #92400e;
}

.status-published {
  background: #dcfce7;
  color: #166534;
}

.status-completed {
  background: #dbeafe;
  color: #1e40af;
}

.story-description {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 12px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.story-meta {
  display: flex;
  gap: 16px;
  font-size: 0.75rem;
  color: #9ca3af;
}

/* AI Status */
.ai-status-card {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.ai-status-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.ai-status-header .section-title {
  margin-bottom: 0;
}

.ai-badge {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 4px 12px;
  border-radius: 9999px;
}

.ai-badge-ok {
  background: #dcfce7;
  color: #166534;
}

.ai-badge-error {
  background: #fee2e2;
  color: #991b1b;
}

.ai-providers {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.ai-provider {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.875rem;
}

.ai-provider.active {
  border-color: #4f46e5;
  background: #eef2ff;
}

.provider-name {
  font-weight: 500;
}

.provider-status {
  font-size: 0.75rem;
}

.status-ok {
  color: #16a34a;
}

.status-off {
  color: #dc2626;
}

.primary-badge {
  font-size: 0.625rem;
  background: #4f46e5;
  color: #fff;
  padding: 2px 6px;
  border-radius: 4px;
  text-transform: uppercase;
}

@media (max-width: 640px) {
  .main {
    padding: 16px;
  }

  .welcome-content {
    flex-direction: column;
    text-align: center;
  }

  .actions-grid {
    grid-template-columns: 1fr;
  }

  .ai-providers {
    flex-direction: column;
  }
}
</style>
