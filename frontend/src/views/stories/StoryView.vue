<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { useStoriesStore } from '@/stores/stories'
import { useAuthStore } from '@/stores/auth'
import { likesApi, commentsApi } from '@/services/api'
import { useMeta } from '@/composables/useMeta'
import AppHeader from '@/components/layout/AppHeader.vue'

interface Comment {
  id: string
  content: string
  created_at: string
  user: {
    id: string
    username: string
    avatar_url: string | null
  }
  replies?: Comment[]
}

const route = useRoute()
const storiesStore = useStoriesStore()
const authStore = useAuthStore()

const story = computed(() => storiesStore.currentStory)
const loading = computed(() => storiesStore.loading)
const error = computed(() => storiesStore.error)
const currentUser = computed(() => authStore.user)
const isOwner = computed(() => story.value?.author?.id === currentUser.value?.id)
const isAuthenticated = computed(() => authStore.isAuthenticated)

// SEO Meta tags
const metaTitle = computed(() => story.value?.title ? `${story.value.title} - StoryVerse` : undefined)
const metaDescription = computed(() => story.value?.description || undefined)
const metaImage = computed(() => story.value?.cover_url || undefined)
useMeta({
  title: metaTitle,
  description: metaDescription,
  image: metaImage,
  type: 'article',
})

// Estimated reading time for entire story (average 200 words per minute for Ukrainian)
const totalReadingTime = computed(() => {
  const words = story.value?.word_count ?? 0
  const minutes = Math.ceil(words / 200)
  if (minutes < 1) return 'менше хвилини'
  if (minutes === 1) return '1 хвилина'
  if (minutes < 5) return `${minutes} хвилини`
  if (minutes < 60) return `${minutes} хвилин`
  const hours = Math.floor(minutes / 60)
  const remainingMinutes = minutes % 60
  if (hours === 1) return `${hours} година ${remainingMinutes} хв`
  if (hours < 5) return `${hours} години ${remainingMinutes} хв`
  return `${hours} годин ${remainingMinutes} хв`
})

// Likes
const liked = ref(false)
const likeCount = ref(0)
const likeLoading = ref(false)

// Comments
const comments = ref<Comment[]>([])
const commentsLoading = ref(false)
const newComment = ref('')
const submittingComment = ref(false)

const toggleLike = async () => {
  if (!isAuthenticated.value || !story.value) return

  likeLoading.value = true
  try {
    if (liked.value) {
      const res = await likesApi.unlike(story.value.id)
      liked.value = false
      likeCount.value = res.data.like_count
    } else {
      const res = await likesApi.like(story.value.id)
      liked.value = true
      likeCount.value = res.data.like_count
    }
  } catch (err) {
    console.error('Like error:', err)
  } finally {
    likeLoading.value = false
  }
}

const loadLikeStatus = async () => {
  if (!isAuthenticated.value || !story.value) return

  try {
    const res = await likesApi.check(story.value.id)
    liked.value = res.data.liked
    likeCount.value = res.data.like_count
  } catch (err) {
    console.error('Like status error:', err)
  }
}

const loadComments = async () => {
  if (!story.value) return

  commentsLoading.value = true
  try {
    const res = await commentsApi.list(story.value.id)
    comments.value = res.data.data
  } catch (err) {
    console.error('Comments error:', err)
  } finally {
    commentsLoading.value = false
  }
}

const submitComment = async () => {
  if (!story.value || !newComment.value.trim()) return

  submittingComment.value = true
  try {
    const res = await commentsApi.create(story.value.id, {
      content: newComment.value.trim(),
    })
    comments.value = [res.data.comment, ...comments.value]
    newComment.value = ''
  } catch (err) {
    console.error('Comment submit error:', err)
  } finally {
    submittingComment.value = false
  }
}

const deleteComment = async (commentId: string) => {
  if (!confirm('Видалити цей коментар?')) return

  try {
    await commentsApi.delete(commentId)
    comments.value = comments.value.filter(c => c.id !== commentId)
  } catch (err) {
    console.error('Comment delete error:', err)
  }
}

const formatDate = (dateStr: string) => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('uk-UA', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

onMounted(async () => {
  const storyId = route.params.id as string
  await storiesStore.fetchStory(storyId)

  if (story.value) {
    likeCount.value = story.value.like_count
    loadLikeStatus()
    loadComments()
  }
})
</script>

<template>
  <div class="story-page">
    <AppHeader />

    <main class="main">
      <!-- Loading State -->
      <div v-if="loading" class="loading">
        <div class="spinner"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="card error-card">
        <div class="error-icon">😔</div>
        <h3 class="error-title">{{ error }}</h3>
        <RouterLink to="/stories" class="back-link">
          Повернутись до історій
        </RouterLink>
      </div>

      <!-- Story Content -->
      <div v-else-if="story">
        <!-- Header -->
        <div class="card story-header-card">
          <div class="story-header">
            <div>
              <h1 class="story-title">{{ story.title }}</h1>
              <div class="story-meta">
                <RouterLink
                  v-if="story.author"
                  :to="`/users/${story.author.id}`"
                  class="author-link"
                >
                  <div class="author-avatar">
                    {{ story.author.username?.charAt(0).toUpperCase() }}
                  </div>
                  <span>{{ story.author.username }}</span>
                </RouterLink>
                <span>{{ story.view_count }} переглядів</span>
                <span>{{ (story.word_count ?? 0).toLocaleString() }} слів</span>
                <span v-if="story.word_count > 0">~{{ totalReadingTime }} читання</span>
              </div>
            </div>

            <!-- Actions -->
            <div class="story-actions">
              <!-- Like Button -->
              <button
                @click="toggleLike"
                :disabled="!isAuthenticated || likeLoading"
                :class="['btn-like', { liked: liked }]"
                :title="isAuthenticated ? (liked ? 'Прибрати вподобання' : 'Вподобати') : 'Увійдіть, щоб вподобати'"
              >
                <span class="like-icon">{{ liked ? '❤️' : '🤍' }}</span>
                <span class="like-count">{{ likeCount }}</span>
              </button>

              <!-- Edit Button -->
              <RouterLink
                v-if="isOwner"
                :to="`/stories/${story.id}/edit`"
                class="btn-secondary"
              >
                ✏️ Редагувати
              </RouterLink>
            </div>
          </div>

          <!-- Description -->
          <p v-if="story.description" class="story-description">
            {{ story.description }}
          </p>

          <!-- Start Reading Button -->
          <RouterLink
            v-if="story.chapters && story.chapters.length > 0 && story.chapters[0]"
            :to="`/stories/${story.id}/chapters/${story.chapters[0].id}`"
            class="btn-start-reading"
          >
            📖 Почати читати
          </RouterLink>

          <!-- Meta badges -->
          <div class="badges">
            <span v-if="story.universe" class="badge badge-universe">
              {{ story.universe.name }}
            </span>
            <span v-if="story.rating !== '0+'" class="badge badge-rating">
              {{ story.rating }}
            </span>
            <span
              :class="['badge', {
                'badge-draft': story.status === 'draft',
                'badge-published': story.status === 'published',
                'badge-completed': story.status === 'completed',
              }]"
            >
              {{ story.status === 'draft' ? 'Чернетка' : story.status === 'published' ? 'В процесі' : 'Завершено' }}
            </span>
          </div>

          <!-- Tags -->
          <div v-if="story.tags && story.tags.length > 0" class="tags-section">
            <span v-for="tag in story.tags" :key="tag.id" class="tag">
              {{ tag.name }}
            </span>
          </div>
        </div>

        <!-- Chapters -->
        <div class="card">
          <div class="chapters-header">
            <h2 class="chapters-title">Глави ({{ story.chapters?.length || 0 }})</h2>
            <RouterLink v-if="isOwner" :to="`/stories/${story.id}/edit`" class="add-chapter-link">
              + Додати главу
            </RouterLink>
          </div>

          <!-- Empty Chapters -->
          <div v-if="!story.chapters || story.chapters.length === 0" class="empty-state">
            <div class="empty-icon">📄</div>
            <p class="empty-text">Ще немає глав</p>
            <RouterLink v-if="isOwner" :to="`/stories/${story.id}/edit`" class="btn-primary">
              Почати писати
            </RouterLink>
          </div>

          <!-- Chapter List -->
          <div v-else class="chapters-list">
            <RouterLink
              v-for="chapter in story.chapters"
              :key="chapter.id"
              :to="`/stories/${story.id}/chapters/${chapter.id}`"
              class="chapter-card"
            >
              <div class="chapter-content">
                <div class="chapter-info">
                  <span class="chapter-number">{{ chapter.chapter_number }}</span>
                  <div>
                    <h3 class="chapter-title">{{ chapter.title || `Глава ${chapter.chapter_number}` }}</h3>
                    <p class="chapter-words">{{ (chapter.word_count ?? 0).toLocaleString() }} слів</p>
                  </div>
                </div>
                <span class="chapter-arrow">→</span>
              </div>
            </RouterLink>
          </div>
        </div>

        <!-- Comments -->
        <div class="card">
          <h2 class="section-title">Коментарі ({{ comments.length }})</h2>

          <!-- Comment Form -->
          <div v-if="isAuthenticated" class="comment-form">
            <div class="comment-avatar">{{ currentUser?.username?.charAt(0).toUpperCase() }}</div>
            <div class="comment-input-wrap">
              <textarea
                v-model="newComment"
                placeholder="Напишіть коментар..."
                class="comment-input"
                rows="3"
              ></textarea>
              <button
                @click="submitComment"
                :disabled="!newComment.trim() || submittingComment"
                class="btn-submit-comment"
              >
                {{ submittingComment ? 'Надсилання...' : 'Надіслати' }}
              </button>
            </div>
          </div>
          <div v-else class="login-prompt">
            <RouterLink to="/login" class="login-link">Увійдіть</RouterLink>, щоб залишити коментар
          </div>

          <!-- Comments List -->
          <div v-if="commentsLoading" class="comments-loading">
            <div class="spinner-small"></div>
          </div>
          <div v-else-if="comments.length === 0" class="no-comments">
            <p>Ще немає коментарів. Будьте першим!</p>
          </div>
          <div v-else class="comments-list">
            <div v-for="comment in comments" :key="comment.id" class="comment">
              <RouterLink :to="`/users/${comment.user.id}`" class="comment-avatar-link">
                <div class="comment-avatar">{{ comment.user.username.charAt(0).toUpperCase() }}</div>
              </RouterLink>
              <div class="comment-body">
                <div class="comment-header">
                  <RouterLink :to="`/users/${comment.user.id}`" class="comment-author">
                    {{ comment.user.username }}
                  </RouterLink>
                  <span class="comment-date">{{ formatDate(comment.created_at) }}</span>
                  <button
                    v-if="currentUser?.id === comment.user.id"
                    @click="deleteComment(comment.id)"
                    class="comment-delete"
                    title="Видалити коментар"
                  >
                    🗑
                  </button>
                </div>
                <p class="comment-text">{{ comment.content }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
.story-page {
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

.spinner-small {
  width: 24px;
  height: 24px;
  border: 2px solid #e5e7eb;
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
  margin-bottom: 8px;
}

.back-link {
  color: #4f46e5;
  font-weight: 500;
}

/* Story Header */
.story-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 16px;
  gap: 16px;
}

.story-title {
  font-size: 1.875rem;
  font-weight: 700;
  color: #111827;
  margin-bottom: 8px;
}

.story-meta {
  display: flex;
  align-items: center;
  gap: 16px;
  font-size: 0.875rem;
  color: #6b7280;
}

.author-link {
  display: flex;
  align-items: center;
  gap: 8px;
}

.author-link:hover {
  color: #4f46e5;
}

.author-avatar {
  width: 32px;
  height: 32px;
  background: #eef2ff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  font-weight: 500;
  color: #4f46e5;
}

.story-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-like {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  background: #fff;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-like:hover:not(:disabled) {
  border-color: #fca5a5;
  background: #fef2f2;
}

.btn-like.liked {
  border-color: #f87171;
  background: #fef2f2;
}

.btn-like:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.like-icon {
  font-size: 1.125rem;
}

.like-count {
  color: #374151;
}

.btn-secondary {
  padding: 8px 16px;
  color: #374151;
  background: #f3f4f6;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.btn-primary {
  display: inline-block;
  padding: 8px 24px;
  background: #4f46e5;
  color: #fff;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-primary:hover {
  background: #4338ca;
}

.story-description {
  color: #4b5563;
  margin-bottom: 16px;
  line-height: 1.6;
}

.btn-start-reading {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  color: #fff;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 16px;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.btn-start-reading:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(79, 70, 229, 0.4);
}

/* Badges */
.badges {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px;
}

.badge {
  padding: 4px 12px;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 500;
}

.badge-universe { background: #dbeafe; color: #1e40af; }
.badge-rating { background: #fee2e2; color: #b91c1c; }
.badge-draft { background: #fef3c7; color: #92400e; }
.badge-published { background: #dcfce7; color: #166534; }
.badge-completed { background: #dbeafe; color: #1e40af; }

/* Tags */
.tags-section {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}

.tag {
  padding: 4px 12px;
  background: #f3f4f6;
  color: #4b5563;
  border-radius: 9999px;
  font-size: 0.875rem;
}

/* Chapters */
.chapters-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}

.chapters-title, .section-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #111827;
}

.section-title {
  margin-bottom: 20px;
}

.add-chapter-link {
  color: #4f46e5;
  font-size: 0.875rem;
  font-weight: 500;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 48px 20px;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 16px;
}

.empty-text {
  color: #6b7280;
  margin-bottom: 16px;
}

/* Chapters List */
.chapters-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.chapter-card {
  display: block;
  padding: 16px;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  transition: all 0.2s;
}

.chapter-card:hover {
  border-color: #a5b4fc;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.chapter-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.chapter-info {
  display: flex;
  align-items: center;
  gap: 16px;
}

.chapter-number {
  width: 32px;
  height: 32px;
  background: #eef2ff;
  color: #4338ca;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  font-weight: 500;
}

.chapter-title {
  font-weight: 500;
  color: #111827;
}

.chapter-words {
  font-size: 0.875rem;
  color: #6b7280;
}

.chapter-arrow {
  color: #9ca3af;
}

/* Comments */
.comment-form {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
  padding-bottom: 24px;
  border-bottom: 1px solid #e5e7eb;
}

.comment-avatar {
  width: 40px;
  height: 40px;
  background: #eef2ff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  font-weight: 500;
  color: #4f46e5;
  flex-shrink: 0;
}

.comment-input-wrap {
  flex: 1;
}

.comment-input {
  width: 100%;
  padding: 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  resize: none;
  font-size: 0.875rem;
  outline: none;
  margin-bottom: 8px;
}

.comment-input:focus {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.btn-submit-comment {
  padding: 8px 16px;
  background: #4f46e5;
  color: #fff;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-submit-comment:hover:not(:disabled) {
  background: #4338ca;
}

.btn-submit-comment:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.login-prompt {
  padding: 16px;
  background: #f9fafb;
  border-radius: 8px;
  margin-bottom: 24px;
  text-align: center;
  color: #6b7280;
}

.login-link {
  color: #4f46e5;
  font-weight: 500;
}

.comments-loading {
  display: flex;
  justify-content: center;
  padding: 24px;
}

.no-comments {
  text-align: center;
  padding: 24px;
  color: #6b7280;
}

.comments-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.comment {
  display: flex;
  gap: 12px;
}

.comment-avatar-link {
  flex-shrink: 0;
}

.comment-body {
  flex: 1;
}

.comment-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.comment-author {
  font-weight: 500;
  color: #111827;
}

.comment-author:hover {
  color: #4f46e5;
}

.comment-date {
  font-size: 0.75rem;
  color: #9ca3af;
}

.comment-delete {
  margin-left: auto;
  padding: 2px 6px;
  color: #9ca3af;
  font-size: 0.875rem;
  transition: color 0.2s;
}

.comment-delete:hover {
  color: #dc2626;
}

.comment-text {
  color: #374151;
  line-height: 1.5;
}

@media (max-width: 640px) {
  .story-header {
    flex-direction: column;
  }

  .story-meta {
    flex-wrap: wrap;
    gap: 8px;
  }

  .story-actions {
    width: 100%;
    justify-content: flex-start;
  }
}
</style>
