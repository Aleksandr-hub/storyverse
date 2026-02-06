import axios, { type AxiosInstance, type AxiosError, type InternalAxiosRequestConfig } from 'axios'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8888/api'

const api: AxiosInstance = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Request interceptor - add auth token
api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error: AxiosError) => Promise.reject(error)
)

// Response interceptor - handle errors
api.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    if (error.response?.status === 401) {
      // Token expired or invalid
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api

// Auth API
export const authApi = {
  register: (data: { email: string; username: string; password: string; password_confirmation: string }) =>
    api.post('/auth/register', data),

  login: (data: { email: string; password: string }) =>
    api.post('/auth/login', data),

  logout: () =>
    api.post('/auth/logout'),

  logoutAll: () =>
    api.post('/auth/logout/all'),

  me: () =>
    api.get('/auth/me'),

  updateProfile: (data: { username?: string; bio?: string; avatar_url?: string }) =>
    api.put('/auth/me', data),

  updatePassword: (data: { current_password?: string; password: string; password_confirmation: string }) =>
    api.put('/auth/me/password', data),

  // OAuth
  getOAuthUrl: (provider: 'google' | 'facebook') =>
    `${API_URL}/auth/oauth/${provider}`,
}

// Stories API
export const storiesApi = {
  list: (params?: {
    search?: string
    universe?: string
    mode?: string
    tag?: string
    rating?: string
    sort?: string
    direction?: string
    per_page?: number
    page?: number
  }) => api.get('/stories', { params }),

  myStories: (params?: { per_page?: number; page?: number }) =>
    api.get('/stories/my', { params }),

  get: (id: string) => api.get(`/stories/${id}`),

  create: (data: {
    title: string
    description?: string
    universe_id?: string
    mode?: string
    is_public?: boolean
    rating?: string
    tags?: string[]
  }) => api.post('/stories', data),

  update: (
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
  ) => api.put(`/stories/${id}`, data),

  delete: (id: string) => api.delete(`/stories/${id}`),

  publish: (id: string) => api.post(`/stories/${id}/publish`),
}

// Chapters API
export const chaptersApi = {
  list: (storyId: string) => api.get(`/stories/${storyId}/chapters`),

  get: (storyId: string, chapterId: string) =>
    api.get(`/stories/${storyId}/chapters/${chapterId}`),

  create: (storyId: string, data: { title?: string; content?: string }) =>
    api.post(`/stories/${storyId}/chapters`, data),

  update: (chapterId: string, data: { title?: string; content?: string }) =>
    api.put(`/chapters/${chapterId}`, data),

  delete: (chapterId: string) => api.delete(`/chapters/${chapterId}`),

  reorder: (storyId: string, chapters: { id: string; chapter_number: number }[]) =>
    api.post(`/stories/${storyId}/chapters/reorder`, { chapters }),
}

// Universes API
export const universesApi = {
  list: (params?: { official?: boolean; search?: string }) =>
    api.get('/universes', { params }),

  get: (id: string) => api.get(`/universes/${id}`),
}

// Tags API
export const tagsApi = {
  list: (params?: { category?: string; search?: string }) =>
    api.get('/tags', { params }),

  popular: () => api.get('/tags/popular'),

  get: (id: string) => api.get(`/tags/${id}`),
}

// Users API
export const usersApi = {
  get: (id: string) => api.get(`/users/${id}`),

  stories: (id: string, params?: { per_page?: number; page?: number }) =>
    api.get(`/users/${id}/stories`, { params }),
}

// Likes API
export const likesApi = {
  like: (storyId: string) => api.post(`/stories/${storyId}/like`),

  unlike: (storyId: string) => api.delete(`/stories/${storyId}/like`),

  check: (storyId: string) => api.get(`/stories/${storyId}/like`),
}

// Comments API
export const commentsApi = {
  list: (storyId: string, params?: { per_page?: number; page?: number }) =>
    api.get(`/stories/${storyId}/comments`, { params }),

  create: (storyId: string, data: { content: string; chapter_id?: string; parent_id?: string }) =>
    api.post(`/stories/${storyId}/comments`, data),

  update: (commentId: string, data: { content: string }) =>
    api.put(`/comments/${commentId}`, data),

  delete: (commentId: string) => api.delete(`/comments/${commentId}`),
}

// Upload API
export const uploadApi = {
  upload: (file: File, type: 'avatar' | 'cover' | 'illustration') => {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('type', type)
    return api.post('/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
  },

  delete: (path: string) =>
    api.delete('/upload', { data: { path } }),
}

// Characters API
export const charactersApi = {
  list: (params?: {
    universe?: string
    canonical?: boolean
    search?: string
    creator?: string
    per_page?: number
    page?: number
  }) => api.get('/characters', { params }),

  myCharacters: (params?: { per_page?: number; page?: number }) =>
    api.get('/characters/my', { params }),

  get: (id: string) => api.get(`/characters/${id}`),

  create: (data: {
    name: string
    description?: string
    avatar_url?: string
    universe_id?: string
    traits?: {
      personality?: string
      skills?: string[]
      appearance?: string
    }
  }) => api.post('/characters', data),

  update: (id: string, data: {
    name?: string
    description?: string
    avatar_url?: string
    universe_id?: string
    traits?: object
  }) => api.put(`/characters/${id}`, data),

  delete: (id: string) => api.delete(`/characters/${id}`),
}

// AI API
export const aiApi = {
  status: () => api.get('/ai/status'),

  continueWriting: (storyId: string, data?: { chapter_id?: string; prompt?: string }) =>
    api.post(`/ai/stories/${storyId}/continue`, data),

  getSuggestions: (storyId: string, data?: { chapter_id?: string }) =>
    api.post(`/ai/stories/${storyId}/suggestions`, data),

  improveText: (storyId: string, data: { text: string; instruction: string }) =>
    api.post(`/ai/stories/${storyId}/improve`, data),

  generateTitle: (storyId: string) =>
    api.post(`/ai/stories/${storyId}/title`),

  generateDescription: (storyId: string) =>
    api.post(`/ai/stories/${storyId}/description`),

  // Adult content (uses Ollama - uncensored)
  continueWritingAdult: (storyId: string, data?: { chapter_id?: string; prompt?: string }) =>
    api.post(`/ai/stories/${storyId}/continue-adult`, data),

  adultStatus: () => api.get('/ai/adult-status'),

  // Image generation (uses Stable Diffusion)
  generateIllustration: (storyId: string, data: {
    chapter_id: string
    prompt: string
    negative_prompt?: string
    width?: number
    height?: number
    style?: 'anime' | 'realistic' | 'fantasy' | 'sketch'
  }) => api.post(`/ai/stories/${storyId}/illustrate`, data),

  imageStatus: () => api.get('/ai/image-status'),
}
