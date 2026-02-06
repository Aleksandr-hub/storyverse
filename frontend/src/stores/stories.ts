import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { storiesApi, chaptersApi, universesApi, tagsApi } from '@/services/api'
import type { AxiosError } from 'axios'

export interface Author {
  id: string
  username: string
  avatar_url: string | null
  bio?: string | null
}

export interface Universe {
  id: string
  name: string
  slug: string
  description?: string | null
  cover_url?: string | null
  is_official?: boolean
  stories_count?: number
  characters_count?: number
}

export interface Tag {
  id: string
  name: string
  slug: string
  category: string | null
  stories_count?: number
}

export interface Illustration {
  id: string
  image_url: string
  prompt?: string | null
  position: number
  created_at: string
}

export interface Chapter {
  id: string
  story_id: string
  title: string | null
  content?: string | null
  chapter_number: number
  word_count: number
  is_ai_generated: boolean
  created_at: string
  updated_at: string
  illustrations?: Illustration[]
}

export interface Story {
  id: string
  title: string
  slug: string
  description: string | null
  cover_url: string | null
  status: 'draft' | 'published' | 'completed'
  mode: 'story' | 'collaborative' | 'adventure'
  is_public: boolean
  rating: '0+' | '6+' | '12+' | '16+' | '18+'
  word_count: number
  view_count: number
  like_count: number
  created_at: string
  updated_at: string
  published_at: string | null
  author?: Author
  universe?: Universe | null
  tags?: Tag[]
  chapters?: Chapter[]
  chapters_count?: number
}

interface ApiError {
  message: string
  errors?: Record<string, string[]>
}

interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

// Cache duration in milliseconds (10 minutes for reference data)
const CACHE_DURATION = 10 * 60 * 1000

export const useStoriesStore = defineStore('stories', () => {
  // State
  const stories = ref<Story[]>([])
  const myStories = ref<Story[]>([])
  const currentStory = ref<Story | null>(null)
  const currentChapter = ref<Chapter | null>(null)
  const universes = ref<Universe[]>([])
  const tags = ref<Tag[]>([])
  const popularTags = ref<Tag[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    perPage: 12,
    total: 0,
  })

  // Cache timestamps
  const universesLastFetched = ref<number | null>(null)
  const tagsLastFetched = ref<number | null>(null)
  const popularTagsLastFetched = ref<number | null>(null)

  // Getters
  const publishedStories = computed(() =>
    stories.value.filter((s) => s.status === 'published')
  )
  const draftStories = computed(() =>
    myStories.value.filter((s) => s.status === 'draft')
  )

  // Actions
  const fetchStories = async (params?: {
    search?: string
    universe?: string
    mode?: string
    tag?: string
    rating?: string
    sort?: string
    direction?: string
    per_page?: number
    page?: number
  }) => {
    loading.value = true
    error.value = null

    try {
      const response = await storiesApi.list(params)
      const data = response.data as PaginatedResponse<Story>
      stories.value = data.data
      pagination.value = {
        currentPage: data.current_page,
        lastPage: data.last_page,
        perPage: data.per_page,
        total: data.total,
      }
    } catch (err) {
      const axiosError = err as AxiosError<ApiError>
      error.value = axiosError.response?.data?.message || 'Помилка завантаження'
    } finally {
      loading.value = false
    }
  }

  const fetchMyStories = async (params?: { per_page?: number; page?: number }) => {
    loading.value = true
    error.value = null

    try {
      const response = await storiesApi.myStories(params)
      const data = response.data as PaginatedResponse<Story>
      myStories.value = data.data
      pagination.value = {
        currentPage: data.current_page,
        lastPage: data.last_page,
        perPage: data.per_page,
        total: data.total,
      }
    } catch (err) {
      const axiosError = err as AxiosError<ApiError>
      error.value = axiosError.response?.data?.message || 'Помилка завантаження'
    } finally {
      loading.value = false
    }
  }

  const fetchStory = async (id: string) => {
    loading.value = true
    error.value = null

    try {
      const response = await storiesApi.get(id)
      currentStory.value = response.data
      return response.data
    } catch (err) {
      const axiosError = err as AxiosError<ApiError>
      error.value = axiosError.response?.data?.message || 'Історію не знайдено'
      return null
    } finally {
      loading.value = false
    }
  }

  const createStory = async (data: {
    title: string
    description?: string
    cover_url?: string
    universe_id?: string
    mode?: string
    is_public?: boolean
    rating?: string
    tags?: string[]
  }) => {
    loading.value = true
    error.value = null

    try {
      const response = await storiesApi.create(data)
      const newStory = response.data.story as Story
      myStories.value.unshift(newStory)
      return { success: true, story: newStory }
    } catch (err) {
      const axiosError = err as AxiosError<ApiError>
      error.value = axiosError.response?.data?.message || 'Помилка створення'
      return {
        success: false,
        errors: axiosError.response?.data?.errors,
        message: error.value,
      }
    } finally {
      loading.value = false
    }
  }

  const updateStory = async (
    id: string,
    data: {
      title?: string
      description?: string
      cover_url?: string
      universe_id?: string
      status?: string
      is_public?: boolean
      rating?: string
      tags?: string[]
    }
  ) => {
    loading.value = true
    error.value = null

    try {
      const response = await storiesApi.update(id, data)
      const updatedStory = response.data.story as Story

      const index = myStories.value.findIndex((s) => s.id === id)
      if (index !== -1) {
        myStories.value[index] = updatedStory
      }
      if (currentStory.value?.id === id) {
        currentStory.value = updatedStory
      }

      return { success: true, story: updatedStory }
    } catch (err) {
      const axiosError = err as AxiosError<ApiError>
      error.value = axiosError.response?.data?.message || 'Помилка оновлення'
      return {
        success: false,
        errors: axiosError.response?.data?.errors,
        message: error.value,
      }
    } finally {
      loading.value = false
    }
  }

  const deleteStory = async (id: string) => {
    loading.value = true
    error.value = null

    try {
      await storiesApi.delete(id)
      myStories.value = myStories.value.filter((s) => s.id !== id)
      stories.value = stories.value.filter((s) => s.id !== id)
      if (currentStory.value?.id === id) {
        currentStory.value = null
      }
      return { success: true }
    } catch (err) {
      const axiosError = err as AxiosError<ApiError>
      error.value = axiosError.response?.data?.message || 'Помилка видалення'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  const publishStory = async (id: string) => {
    loading.value = true
    error.value = null

    try {
      const response = await storiesApi.publish(id)
      const publishedStory = response.data.story as Story

      const index = myStories.value.findIndex((s) => s.id === id)
      if (index !== -1) {
        myStories.value[index] = publishedStory
      }
      if (currentStory.value?.id === id) {
        currentStory.value = publishedStory
      }

      return { success: true, story: publishedStory }
    } catch (err) {
      const axiosError = err as AxiosError<ApiError>
      error.value = axiosError.response?.data?.message || 'Помилка публікації'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  // Chapters
  const fetchChapter = async (storyId: string, chapterId: string) => {
    loading.value = true
    error.value = null

    try {
      const response = await chaptersApi.get(storyId, chapterId)
      currentChapter.value = response.data
      return response.data
    } catch (err) {
      const axiosError = err as AxiosError<ApiError>
      error.value = axiosError.response?.data?.message || 'Главу не знайдено'
      return null
    } finally {
      loading.value = false
    }
  }

  const createChapter = async (storyId: string, data: { title?: string; content?: string }) => {
    loading.value = true
    error.value = null

    try {
      const response = await chaptersApi.create(storyId, data)
      const newChapter = response.data.chapter as Chapter

      if (currentStory.value?.id === storyId && currentStory.value.chapters) {
        currentStory.value.chapters.push(newChapter)
      }

      return { success: true, chapter: newChapter }
    } catch (err) {
      const axiosError = err as AxiosError<ApiError>
      error.value = axiosError.response?.data?.message || 'Помилка створення'
      return {
        success: false,
        errors: axiosError.response?.data?.errors,
        message: error.value,
      }
    } finally {
      loading.value = false
    }
  }

  const updateChapter = async (chapterId: string, data: { title?: string; content?: string }) => {
    loading.value = true
    error.value = null

    try {
      const response = await chaptersApi.update(chapterId, data)
      const updatedChapter = response.data.chapter as Chapter

      // Update currentChapter if it matches
      if (currentChapter.value?.id === chapterId) {
        currentChapter.value = updatedChapter
      }

      // Also update in currentStory.chapters list
      if (currentStory.value?.chapters) {
        const index = currentStory.value.chapters.findIndex(c => c.id === chapterId)
        if (index !== -1) {
          currentStory.value.chapters[index] = updatedChapter
        }
      }

      return { success: true, chapter: updatedChapter }
    } catch (err) {
      const axiosError = err as AxiosError<ApiError>
      error.value = axiosError.response?.data?.message || 'Помилка оновлення'
      return {
        success: false,
        errors: axiosError.response?.data?.errors,
        message: error.value,
      }
    } finally {
      loading.value = false
    }
  }

  const deleteChapter = async (chapterId: string) => {
    loading.value = true
    error.value = null

    try {
      await chaptersApi.delete(chapterId)

      if (currentStory.value?.chapters) {
        currentStory.value.chapters = currentStory.value.chapters.filter(
          (c) => c.id !== chapterId
        )
      }
      if (currentChapter.value?.id === chapterId) {
        currentChapter.value = null
      }

      return { success: true }
    } catch (err) {
      const axiosError = err as AxiosError<ApiError>
      error.value = axiosError.response?.data?.message || 'Помилка видалення'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  // Universes & Tags (with caching)
  const isCacheValid = (lastFetched: number | null): boolean => {
    if (!lastFetched) return false
    return Date.now() - lastFetched < CACHE_DURATION
  }

  const fetchUniverses = async (params?: { official?: boolean; search?: string }, forceRefresh = false) => {
    // Skip fetch if we have valid cached data and no search params
    if (!forceRefresh && !params?.search && isCacheValid(universesLastFetched.value) && universes.value.length > 0) {
      return
    }

    try {
      const response = await universesApi.list(params)
      universes.value = response.data

      // Only update cache timestamp for full list (no search)
      if (!params?.search) {
        universesLastFetched.value = Date.now()
      }
    } catch (err) {
      console.error('Failed to fetch universes:', err)
    }
  }

  const fetchTags = async (params?: { category?: string; search?: string }, forceRefresh = false) => {
    // Skip fetch if we have valid cached data and no search params
    if (!forceRefresh && !params?.search && !params?.category && isCacheValid(tagsLastFetched.value) && tags.value.length > 0) {
      return
    }

    try {
      const response = await tagsApi.list(params)
      tags.value = response.data

      // Only update cache timestamp for full list (no search/category)
      if (!params?.search && !params?.category) {
        tagsLastFetched.value = Date.now()
      }
    } catch (err) {
      console.error('Failed to fetch tags:', err)
    }
  }

  const fetchPopularTags = async (forceRefresh = false) => {
    // Skip fetch if we have valid cached data
    if (!forceRefresh && isCacheValid(popularTagsLastFetched.value) && popularTags.value.length > 0) {
      return
    }

    try {
      const response = await tagsApi.popular()
      popularTags.value = response.data
      popularTagsLastFetched.value = Date.now()
    } catch (err) {
      console.error('Failed to fetch popular tags:', err)
    }
  }

  // Invalidate caches (useful after creating new universes/tags)
  const invalidateUniversesCache = () => {
    universesLastFetched.value = null
  }

  const invalidateTagsCache = () => {
    tagsLastFetched.value = null
    popularTagsLastFetched.value = null
  }

  return {
    // State
    stories,
    myStories,
    currentStory,
    currentChapter,
    universes,
    tags,
    popularTags,
    loading,
    error,
    pagination,
    // Getters
    publishedStories,
    draftStories,
    // Actions
    fetchStories,
    fetchMyStories,
    fetchStory,
    createStory,
    updateStory,
    deleteStory,
    publishStory,
    fetchChapter,
    createChapter,
    updateChapter,
    deleteChapter,
    fetchUniverses,
    fetchTags,
    fetchPopularTags,
    invalidateUniversesCache,
    invalidateTagsCache,
  }
})
