import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: () => import('../views/HomeView.vue'),
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/auth/LoginView.vue'),
      meta: { guest: true },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('../views/auth/RegisterView.vue'),
      meta: { guest: true },
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('../views/DashboardView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/stories',
      name: 'stories',
      component: () => import('../views/stories/StoriesView.vue'),
    },
    {
      path: '/stories/new',
      name: 'story-create',
      component: () => import('../views/stories/StoryCreateView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/stories/:id',
      name: 'story',
      component: () => import('../views/stories/StoryView.vue'),
    },
    {
      path: '/stories/:id/edit',
      name: 'story-edit',
      component: () => import('../views/stories/StoryEditView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/stories/:storyId/chapters/:chapterId',
      name: 'chapter',
      component: () => import('../views/stories/ChapterView.vue'),
    },
    {
      path: '/stories/:storyId/chapters/:chapterId/edit',
      name: 'chapter-edit',
      component: () => import('../views/stories/ChapterEditView.vue'),
      meta: { requiresAuth: true },
    },
    // User profile
    {
      path: '/users/:id',
      name: 'user-profile',
      component: () => import('../views/ProfileView.vue'),
    },
    // Characters
    {
      path: '/characters',
      name: 'characters',
      component: () => import('../views/characters/CharactersView.vue'),
    },
    {
      path: '/characters/new',
      name: 'character-create',
      component: () => import('../views/characters/CharacterCreateView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/characters/:id',
      name: 'character',
      component: () => import('../views/characters/CharacterView.vue'),
    },
    {
      path: '/characters/:id/edit',
      name: 'character-edit',
      component: () => import('../views/characters/CharacterCreateView.vue'),
      meta: { requiresAuth: true },
    },
    // OAuth callback handler
    {
      path: '/auth/callback',
      name: 'auth-callback',
      component: () => import('../views/auth/OAuthCallbackView.vue'),
    },
  ],
})

// Navigation guards
router.beforeEach((to, _from, next) => {
  const authStore = useAuthStore()

  // Initialize auth state from localStorage
  if (!authStore.token) {
    authStore.init()
  }

  const isAuthenticated = authStore.isAuthenticated

  // Routes that require authentication
  if (to.meta.requiresAuth && !isAuthenticated) {
    next({ name: 'login', query: { redirect: to.fullPath } })
    return
  }

  // Routes only for guests (login, register)
  if (to.meta.guest && isAuthenticated) {
    next({ name: 'dashboard' })
    return
  }

  next()
})

export default router
