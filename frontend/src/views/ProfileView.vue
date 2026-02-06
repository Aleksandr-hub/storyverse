<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { usersApi } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import AppHeader from '@/components/layout/AppHeader.vue'

interface UserProfile {
  id: string
  username: string
  avatar_url: string | null
  bio: string | null
  is_premium: boolean
  created_at: string
  stats: {
    stories_count: number
    total_views: number
    total_likes: number
  }
}

interface Story {
  id: string
  title: string
  description: string | null
  status: string
  rating: string
  word_count: number
  view_count: number
  chapters_count: number
  tags?: { id: string; name: string }[]
}

const route = useRoute()
const authStore = useAuthStore()

const user = ref<UserProfile | null>(null)
const stories = ref<Story[]>([])
const loading = ref(true)
const error = ref('')
const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  total: 0,
})

const isOwnProfile = computed(() => {
  return authStore.user?.id === route.params.id
})

const memberSince = computed(() => {
  if (!user.value) return ''
  const date = new Date(user.value.created_at)
  return date.toLocaleDateString('uk-UA', { month: 'long', year: 'numeric' })
})

const loadProfile = async () => {
  loading.value = true
  error.value = ''

  try {
    const userId = route.params.id as string
    const [profileRes, storiesRes] = await Promise.all([
      usersApi.get(userId),
      usersApi.stories(userId, { per_page: 12 }),
    ])

    user.value = profileRes.data
    stories.value = storiesRes.data.data
    pagination.value = {
      currentPage: storiesRes.data.meta.current_page,
      lastPage: storiesRes.data.meta.last_page,
      total: storiesRes.data.meta.total,
    }
  } catch (err: unknown) {
    const e = err as { response?: { data?: { message?: string } } }
    error.value = e.response?.data?.message || 'Користувача не знайдено'
  } finally {
    loading.value = false
  }
}

const loadMoreStories = async () => {
  if (pagination.value.currentPage >= pagination.value.lastPage) return

  const userId = route.params.id as string
  const res = await usersApi.stories(userId, {
    per_page: 12,
    page: pagination.value.currentPage + 1,
  })

  stories.value = [...stories.value, ...res.data.data]
  pagination.value = {
    currentPage: res.data.meta.current_page,
    lastPage: res.data.meta.last_page,
    total: res.data.meta.total,
  }
}

onMounted(loadProfile)
</script>

<template>
  <div class="profile-page">
    <AppHeader />

    <main class="main">
      <!-- Loading -->
      <div v-if="loading" class="loading">
        <div class="spinner"></div>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="card error-card">
        <div class="error-icon">😔</div>
        <h3 class="error-title">{{ error }}</h3>
        <RouterLink to="/stories" class="back-link">
          Повернутись до історій
        </RouterLink>
      </div>

      <!-- Profile -->
      <template v-else-if="user">
        <!-- Header Card -->
        <div class="card profile-card">
          <div class="profile-header">
            <div class="avatar">
              <img v-if="user.avatar_url" :src="user.avatar_url" :alt="user.username" />
              <span v-else>{{ user.username.charAt(0).toUpperCase() }}</span>
            </div>
            <div class="profile-info">
              <h1 class="username">
                {{ user.username }}
                <span v-if="user.is_premium" class="premium-badge">PRO</span>
              </h1>
              <p v-if="user.bio" class="bio">{{ user.bio }}</p>
              <p class="member-since">Учасник з {{ memberSince }}</p>
            </div>
          </div>

          <!-- Stats -->
          <div class="stats">
            <div class="stat">
              <span class="stat-value">{{ user.stats.stories_count }}</span>
              <span class="stat-label">Історій</span>
            </div>
            <div class="stat">
              <span class="stat-value">{{ (user.stats?.total_views ?? 0).toLocaleString() }}</span>
              <span class="stat-label">Переглядів</span>
            </div>
            <div class="stat">
              <span class="stat-value">{{ (user.stats?.total_likes ?? 0).toLocaleString() }}</span>
              <span class="stat-label">Вподобань</span>
            </div>
          </div>

          <!-- Edit Profile Button -->
          <RouterLink v-if="isOwnProfile" to="/dashboard" class="btn-edit">
            ⚙️ Налаштування профілю
          </RouterLink>
        </div>

        <!-- Stories -->
        <div class="card">
          <h2 class="section-title">Історії автора ({{ pagination.total }})</h2>

          <!-- Empty -->
          <div v-if="stories.length === 0" class="empty-state">
            <div class="empty-icon">📚</div>
            <p class="empty-text">Автор ще не опублікував жодної історії</p>
          </div>

          <!-- Stories Grid -->
          <div v-else class="stories-grid">
            <RouterLink
              v-for="story in stories"
              :key="story.id"
              :to="`/stories/${story.id}`"
              class="story-card"
            >
              <div class="story-header">
                <h3 class="story-title">{{ story.title }}</h3>
                <span v-if="story.rating !== '0+'" class="rating-badge">{{ story.rating }}</span>
              </div>
              <p v-if="story.description" class="story-description">{{ story.description }}</p>
              <div v-if="story.tags && story.tags.length > 0" class="story-tags">
                <span v-for="tag in story.tags.slice(0, 3)" :key="tag.id" class="tag">
                  {{ tag.name }}
                </span>
              </div>
              <div class="story-meta">
                <span>{{ story.chapters_count || 0 }} глав</span>
                <span>{{ (story.word_count ?? 0).toLocaleString() }} слів</span>
                <span>{{ story.view_count }} 👁</span>
              </div>
            </RouterLink>
          </div>

          <!-- Load More -->
          <button
            v-if="pagination.currentPage < pagination.lastPage"
            @click="loadMoreStories"
            class="btn-load-more"
          >
            Завантажити ще
          </button>
        </div>
      </template>
    </main>
  </div>
</template>

<style scoped>
.profile-page {
  min-height: 100vh;
  background: #f9fafb;
}

.main {
  max-width: 960px;
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
  padding: 24px;
  margin-bottom: 24px;
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
  margin-bottom: 8px;
}

.back-link {
  color: #4f46e5;
  font-weight: 500;
}

/* Profile Header */
.profile-header {
  display: flex;
  gap: 24px;
  margin-bottom: 24px;
}

.avatar {
  width: 100px;
  height: 100px;
  background: #eef2ff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  font-weight: 600;
  color: #4f46e5;
  flex-shrink: 0;
  overflow: hidden;
}

.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.profile-info {
  flex: 1;
}

.username {
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.premium-badge {
  font-size: 0.75rem;
  padding: 2px 8px;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: #fff;
  border-radius: 9999px;
  font-weight: 600;
}

.bio {
  color: #4b5563;
  margin-bottom: 8px;
  line-height: 1.5;
}

.member-since {
  font-size: 0.875rem;
  color: #6b7280;
}

/* Stats */
.stats {
  display: flex;
  gap: 32px;
  padding: 20px 0;
  border-top: 1px solid #e5e7eb;
  border-bottom: 1px solid #e5e7eb;
  margin-bottom: 20px;
}

.stat {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
}

.stat-label {
  font-size: 0.875rem;
  color: #6b7280;
}

.btn-edit {
  display: inline-block;
  padding: 10px 20px;
  background: #f3f4f6;
  color: #374151;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-edit:hover {
  background: #e5e7eb;
}

/* Section */
.section-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #111827;
  margin-bottom: 20px;
}

/* Empty */
.empty-state {
  text-align: center;
  padding: 48px;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 16px;
}

.empty-text {
  color: #6b7280;
}

/* Stories Grid */
.stories-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.story-card {
  display: block;
  padding: 20px;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  transition: all 0.2s;
}

.story-card:hover {
  border-color: #a5b4fc;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
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
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.rating-badge {
  font-size: 0.75rem;
  padding: 2px 8px;
  background: #fee2e2;
  color: #b91c1c;
  border-radius: 9999px;
  font-weight: 500;
  white-space: nowrap;
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

.story-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 12px;
}

.tag {
  font-size: 0.75rem;
  padding: 2px 8px;
  background: #f3f4f6;
  color: #4b5563;
  border-radius: 9999px;
}

.story-meta {
  display: flex;
  gap: 16px;
  font-size: 0.75rem;
  color: #9ca3af;
}

.btn-load-more {
  display: block;
  width: 100%;
  margin-top: 24px;
  padding: 12px;
  background: #f3f4f6;
  color: #374151;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-load-more:hover {
  background: #e5e7eb;
}

@media (max-width: 640px) {
  .profile-header {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }

  .username {
    justify-content: center;
  }

  .stats {
    justify-content: center;
  }

  .stories-grid {
    grid-template-columns: 1fr;
  }
}
</style>
