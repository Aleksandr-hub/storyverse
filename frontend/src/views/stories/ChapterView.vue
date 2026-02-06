<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { marked } from 'marked'
import { useStoriesStore } from '@/stores/stories'
import { useAuthStore } from '@/stores/auth'
import { useMeta } from '@/composables/useMeta'
import AppHeader from '@/components/layout/AppHeader.vue'

// Configure marked for safe output
marked.setOptions({
  breaks: true, // Convert \n to <br>
  gfm: true,    // GitHub Flavored Markdown
})

// Check if content looks like markdown (has markdown patterns but no HTML tags)
const isMarkdown = (content: string): boolean => {
  if (!content) return false
  // Has markdown patterns like **bold**, # heading, - list, etc.
  const hasMarkdownPatterns = /(\*\*|__|##?|^[-*+]\s|^\d+\.\s|```|>\s)/m.test(content)
  // Has minimal HTML (excluding simple <p> and <br> that might come from tiptap)
  const hasRichHtml = /<(div|span|h[1-6]|strong|em|ul|ol|li|blockquote)[^>]*>/i.test(content)
  return hasMarkdownPatterns && !hasRichHtml
}

// Process content: convert markdown to HTML if needed, then sanitize
const processContent = (content: string): string => {
  if (!content) return ''

  let html = content

  // If it looks like markdown, convert it
  if (isMarkdown(content)) {
    html = marked.parse(content) as string
  }

  // Sanitize to prevent XSS
  return html
    .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
    .replace(/on\w+="[^"]*"/gi, '')
    .replace(/on\w+='[^']*'/gi, '')
}

const route = useRoute()
const storiesStore = useStoriesStore()
const authStore = useAuthStore()

const story = computed(() => storiesStore.currentStory)
const chapter = computed(() => storiesStore.currentChapter)
const loading = computed(() => storiesStore.loading)
const currentUser = computed(() => authStore.user)
const isOwner = computed(() => story.value?.author?.id === currentUser.value?.id)

// SEO Meta tags
const metaTitle = computed(() => {
  if (!story.value || !chapter.value) return undefined
  const chapterTitle = chapter.value.title || `Глава ${chapter.value.chapter_number}`
  return `${chapterTitle} - ${story.value.title} - StoryVerse`
})
const metaDescription = computed(() => story.value?.description || undefined)
useMeta({
  title: metaTitle,
  description: metaDescription,
  type: 'article',
})

const currentIndex = computed(() => {
  if (!story.value?.chapters || !chapter.value) return -1
  return story.value.chapters.findIndex(c => c.id === chapter.value?.id)
})

const prevChapter = computed(() => {
  if (!story.value?.chapters || currentIndex.value <= 0) return null
  return story.value.chapters[currentIndex.value - 1]
})

const nextChapter = computed(() => {
  if (!story.value?.chapters || currentIndex.value === -1 || currentIndex.value >= story.value.chapters.length - 1) return null
  return story.value.chapters[currentIndex.value + 1]
})

// Reading progress
const readingProgress = ref(0)
const contentRef = ref<HTMLElement | null>(null)

const updateReadingProgress = () => {
  const scrollTop = window.scrollY
  const docHeight = document.documentElement.scrollHeight - window.innerHeight
  if (docHeight > 0) {
    readingProgress.value = Math.min(100, Math.round((scrollTop / docHeight) * 100))
  }
}

// Estimated reading time (average 200 words per minute for Ukrainian)
const readingTime = computed(() => {
  const words = chapter.value?.word_count ?? 0
  const minutes = Math.ceil(words / 200)
  if (minutes < 1) return 'менше хвилини'
  if (minutes === 1) return '1 хвилина'
  if (minutes < 5) return `${minutes} хвилини`
  return `${minutes} хвилин`
})

const fontSize = ref(18)

const increaseFontSize = () => {
  if (fontSize.value < 24) fontSize.value += 2
}

const decreaseFontSize = () => {
  if (fontSize.value > 14) fontSize.value -= 2
}

onMounted(async () => {
  const storyId = route.params.storyId as string
  const chapterId = route.params.chapterId as string

  // Load story if not already loaded or different
  if (!story.value || story.value.id !== storyId) {
    await storiesStore.fetchStory(storyId)
  }

  // Load chapter content
  await storiesStore.fetchChapter(storyId, chapterId)

  // Add scroll listener for reading progress
  window.addEventListener('scroll', updateReadingProgress)
  updateReadingProgress()
})

// Watch for route changes (navigating between chapters)
watch(() => route.params.chapterId, async (newChapterId) => {
  if (newChapterId && typeof newChapterId === 'string') {
    const storyId = route.params.storyId as string
    await storiesStore.fetchChapter(storyId, newChapterId)
    window.scrollTo({ top: 0, behavior: 'smooth' })
    readingProgress.value = 0
  }
})

// Cleanup
onUnmounted(() => {
  window.removeEventListener('scroll', updateReadingProgress)
})
</script>

<template>
  <div class="chapter-page">
    <!-- Reading Progress Bar -->
    <div class="reading-progress-bar" :style="{ width: readingProgress + '%' }"></div>

    <AppHeader />

    <main class="main">
      <!-- Loading State -->
      <div v-if="loading" class="loading">
        <div class="spinner"></div>
      </div>

      <!-- Content -->
      <div v-else-if="story && chapter">
        <!-- Navigation -->
        <div class="nav-header">
          <RouterLink :to="`/stories/${story.id}`" class="back-link">
            ← {{ story.title }}
          </RouterLink>

          <div v-if="isOwner" class="owner-actions">
            <RouterLink
              :to="`/stories/${story.id}/chapters/${chapter.id}/edit`"
              class="btn-edit"
            >
              ✏️ Редагувати
            </RouterLink>
          </div>
        </div>

        <!-- Chapter Header -->
        <div class="card chapter-header">
          <div class="chapter-meta">
            <span class="chapter-number">{{ chapter.chapter_number }}</span>
            <div>
              <h1 class="chapter-title">
                {{ chapter.title || `Глава ${chapter.chapter_number}` }}
              </h1>
              <p class="chapter-meta-text">
                {{ (chapter.word_count ?? 0).toLocaleString() }} слів · {{ readingTime }} читання
              </p>
            </div>
          </div>

          <!-- Font Size Controls -->
          <div class="font-controls">
            <span class="font-label">Розмір тексту:</span>
            <button @click="decreaseFontSize" class="font-btn">-</button>
            <span class="font-value">{{ fontSize }}</span>
            <button @click="increaseFontSize" class="font-btn">+</button>
          </div>
        </div>

        <!-- Chapter Content -->
        <div class="card content-card" ref="contentRef">
          <!-- Illustrations before content -->
          <div v-if="chapter.illustrations?.length" class="illustrations-header">
            <img
              v-for="illustration in chapter.illustrations.filter((i: { position: number }) => i.position === 0)"
              :key="illustration.id"
              :src="illustration.image_url"
              :alt="illustration.prompt || 'Ілюстрація'"
              class="chapter-illustration"
            />
          </div>

          <div
            v-if="chapter.content"
            class="prose"
            :style="{ fontSize: fontSize + 'px', lineHeight: '1.8' }"
            v-html="processContent(chapter.content)"
          ></div>

          <div v-else class="empty-content">
            <p>Ця глава ще порожня</p>
          </div>

          <!-- Illustrations after content -->
          <div v-if="chapter.illustrations?.length" class="illustrations-footer">
            <img
              v-for="illustration in chapter.illustrations.filter((i: { position: number }) => i.position > 0)"
              :key="illustration.id"
              :src="illustration.image_url"
              :alt="illustration.prompt || 'Ілюстрація'"
              class="chapter-illustration"
            />
          </div>
        </div>

        <!-- Chapter Navigation -->
        <div class="chapter-nav">
          <RouterLink
            v-if="prevChapter"
            :to="`/stories/${story.id}/chapters/${prevChapter.id}`"
            class="nav-card"
          >
            <span class="nav-arrow">←</span>
            <div class="nav-info">
              <p class="nav-label">Попередня</p>
              <p class="nav-title">{{ prevChapter.title || `Глава ${prevChapter.chapter_number}` }}</p>
            </div>
          </RouterLink>
          <div v-else></div>

          <RouterLink
            v-if="nextChapter"
            :to="`/stories/${story.id}/chapters/${nextChapter.id}`"
            class="nav-card nav-card-right"
          >
            <div class="nav-info nav-info-right">
              <p class="nav-label">Наступна</p>
              <p class="nav-title">{{ nextChapter.title || `Глава ${nextChapter.chapter_number}` }}</p>
            </div>
            <span class="nav-arrow">→</span>
          </RouterLink>
          <div v-else></div>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
.chapter-page {
  min-height: 100vh;
  background: #f9fafb;
  position: relative;
}

/* Reading Progress Bar */
.reading-progress-bar {
  position: fixed;
  top: 0;
  left: 0;
  height: 3px;
  background: linear-gradient(90deg, #4f46e5, #7c3aed);
  z-index: 1000;
  transition: width 0.1s ease-out;
}

.main {
  max-width: 768px;
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

/* Navigation Header */
.nav-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}

.back-link {
  color: #4f46e5;
  font-weight: 500;
}

.back-link:hover {
  color: #4338ca;
}

.owner-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-edit {
  padding: 8px 16px;
  color: #374151;
  background: #f3f4f6;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-edit:hover {
  background: #e5e7eb;
}

/* Chapter Header */
.chapter-header {
  margin-bottom: 24px;
}

.chapter-meta {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}

.chapter-number {
  width: 40px;
  height: 40px;
  background: #eef2ff;
  color: #4338ca;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.125rem;
  font-weight: 500;
}

.chapter-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
}

.chapter-meta-text {
  font-size: 0.875rem;
  color: #6b7280;
}

/* Font Controls */
.font-controls {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}

.font-label {
  font-size: 0.875rem;
  color: #6b7280;
}

.font-btn {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
}

.font-btn:hover {
  background: #f3f4f6;
}

.font-value {
  font-size: 0.875rem;
  color: #374151;
  width: 32px;
  text-align: center;
}

/* Content */
.content-card {
  padding: 32px;
}

.prose {
  color: #111827;
}

/* Markdown styles */
.prose :deep(p) {
  margin-bottom: 1em;
}

.prose :deep(p:last-child) {
  margin-bottom: 0;
}

.prose :deep(strong),
.prose :deep(b) {
  font-weight: 600;
}

.prose :deep(em),
.prose :deep(i) {
  font-style: italic;
}

.prose :deep(h1),
.prose :deep(h2),
.prose :deep(h3) {
  font-weight: 700;
  margin-top: 1.5em;
  margin-bottom: 0.5em;
  color: #111827;
}

.prose :deep(h1) {
  font-size: 1.5em;
}

.prose :deep(h2) {
  font-size: 1.25em;
}

.prose :deep(h3) {
  font-size: 1.1em;
}

.prose :deep(blockquote) {
  border-left: 3px solid #e5e7eb;
  padding-left: 1em;
  margin: 1em 0;
  color: #6b7280;
  font-style: italic;
}

.prose :deep(hr) {
  border: none;
  border-top: 1px solid #e5e7eb;
  margin: 2em 0;
}

.prose :deep(ul),
.prose :deep(ol) {
  margin: 1em 0;
  padding-left: 1.5em;
}

.prose :deep(li) {
  margin-bottom: 0.5em;
}

.empty-content {
  text-align: center;
  padding: 48px;
  color: #6b7280;
}

/* Chapter Navigation */
.chapter-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.nav-card {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  transition: all 0.2s;
}

.nav-card:hover {
  border-color: #a5b4fc;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.nav-card-right {
  text-align: right;
}

.nav-arrow {
  color: #9ca3af;
}

.nav-info {
  text-align: left;
}

.nav-info-right {
  text-align: right;
}

.nav-label {
  font-size: 0.75rem;
  color: #6b7280;
}

.nav-title {
  font-weight: 500;
  color: #111827;
}

/* Illustrations */
.illustrations-header,
.illustrations-footer {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.illustrations-header {
  margin-bottom: 32px;
}

.illustrations-footer {
  margin-top: 32px;
}

.chapter-illustration {
  max-width: 100%;
  height: auto;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

@media (max-width: 640px) {
  .chapter-nav {
    flex-direction: column;
    gap: 12px;
  }

  .nav-card {
    width: 100%;
    justify-content: center;
  }
}
</style>
