<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const router = useRouter()

const isAuthenticated = computed(() => authStore.isAuthenticated)
const user = computed(() => authStore.user)
const mobileMenuOpen = ref(false)

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value
}
</script>

<template>
  <header class="header">
    <nav class="nav">
      <RouterLink to="/" class="logo">
        <span class="logo-icon">📚</span>
        <span class="logo-text">StoryVerse</span>
      </RouterLink>

      <!-- Desktop Navigation -->
      <div class="nav-links">
        <template v-if="isAuthenticated">
          <RouterLink to="/stories" class="nav-link">Історії</RouterLink>
          <RouterLink to="/characters" class="nav-link">Персонажі</RouterLink>
          <RouterLink to="/dashboard" class="nav-link">Мої історії</RouterLink>
          <div class="user-menu">
            <span class="username">{{ user?.username }}</span>
            <button @click="handleLogout" class="logout-btn">Вийти</button>
          </div>
        </template>
        <template v-else>
          <RouterLink to="/stories" class="nav-link">Історії</RouterLink>
          <RouterLink to="/characters" class="nav-link">Персонажі</RouterLink>
          <RouterLink to="/login" class="nav-link">Увійти</RouterLink>
          <RouterLink to="/register" class="nav-btn">Реєстрація</RouterLink>
        </template>
      </div>

      <!-- Mobile menu button -->
      <button @click="toggleMobileMenu" class="mobile-menu-btn">
        <svg v-if="!mobileMenuOpen" class="menu-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
        <svg v-else class="menu-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </nav>

    <!-- Mobile menu -->
    <div v-if="mobileMenuOpen" class="mobile-menu">
      <template v-if="isAuthenticated">
        <RouterLink to="/stories" @click="mobileMenuOpen = false" class="mobile-link">Історії</RouterLink>
        <RouterLink to="/characters" @click="mobileMenuOpen = false" class="mobile-link">Персонажі</RouterLink>
        <RouterLink to="/dashboard" @click="mobileMenuOpen = false" class="mobile-link">Мої історії</RouterLink>
        <div class="mobile-user">
          <span class="mobile-username">{{ user?.username }}</span>
          <button @click="handleLogout" class="mobile-logout">Вийти</button>
        </div>
      </template>
      <template v-else>
        <RouterLink to="/stories" @click="mobileMenuOpen = false" class="mobile-link">Історії</RouterLink>
        <RouterLink to="/characters" @click="mobileMenuOpen = false" class="mobile-link">Персонажі</RouterLink>
        <RouterLink to="/login" @click="mobileMenuOpen = false" class="mobile-link">Увійти</RouterLink>
        <RouterLink to="/register" @click="mobileMenuOpen = false" class="mobile-btn">Реєстрація</RouterLink>
      </template>
    </div>
  </header>
</template>

<style scoped>
.header {
  background: #fff;
  border-bottom: 1px solid #e5e7eb;
  position: sticky;
  top: 0;
  z-index: 50;
}

.nav {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 20px;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.logo {
  display: flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
}

.logo-icon {
  font-size: 1.5rem;
}

.logo-text {
  font-size: 1.25rem;
  font-weight: 700;
  color: #4f46e5;
}

.nav-links {
  display: none;
  align-items: center;
  gap: 8px;
}

@media (min-width: 640px) {
  .nav-links {
    display: flex;
  }
}

.nav-link {
  color: #4b5563;
  text-decoration: none;
  padding: 8px 12px;
  font-size: 0.875rem;
  font-weight: 500;
  transition: color 0.2s;
}

.nav-link:hover {
  color: #4f46e5;
}

.nav-btn {
  background: #4f46e5;
  color: #fff;
  text-decoration: none;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  transition: background 0.2s;
}

.nav-btn:hover {
  background: #4338ca;
}

.user-menu {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-left: 8px;
  padding-left: 16px;
  border-left: 1px solid #e5e7eb;
}

.username {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

.logout-btn {
  font-size: 0.875rem;
  color: #6b7280;
  background: none;
  border: none;
  cursor: pointer;
  transition: color 0.2s;
}

.logout-btn:hover {
  color: #dc2626;
}

/* Mobile */
.mobile-menu-btn {
  display: flex;
  padding: 8px;
  border-radius: 6px;
  color: #4b5563;
  background: none;
  border: none;
  cursor: pointer;
}

.mobile-menu-btn:hover {
  background: #f3f4f6;
  color: #4f46e5;
}

@media (min-width: 640px) {
  .mobile-menu-btn {
    display: none;
  }
}

.menu-icon {
  width: 24px;
  height: 24px;
}

.mobile-menu {
  padding: 8px 20px 16px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

@media (min-width: 640px) {
  .mobile-menu {
    display: none;
  }
}

.mobile-link {
  display: block;
  padding: 10px 12px;
  color: #4b5563;
  text-decoration: none;
  font-weight: 500;
  border-radius: 8px;
  transition: all 0.2s;
}

.mobile-link:hover {
  background: #f3f4f6;
  color: #4f46e5;
}

.mobile-btn {
  display: block;
  margin-top: 8px;
  padding: 12px;
  background: #4f46e5;
  color: #fff;
  text-decoration: none;
  text-align: center;
  font-weight: 500;
  border-radius: 8px;
}

.mobile-user {
  padding-top: 12px;
  margin-top: 8px;
  border-top: 1px solid #e5e7eb;
}

.mobile-username {
  display: block;
  padding: 8px 12px;
  font-size: 0.875rem;
  color: #6b7280;
}

.mobile-logout {
  display: block;
  width: 100%;
  text-align: left;
  padding: 10px 12px;
  color: #dc2626;
  background: none;
  border: none;
  font-weight: 500;
  border-radius: 8px;
  cursor: pointer;
}

.mobile-logout:hover {
  background: #fef2f2;
}
</style>
