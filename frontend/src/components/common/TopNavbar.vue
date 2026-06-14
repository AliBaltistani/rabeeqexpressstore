<template>
  <div class="top-navbar">
    <div class="container top-navbar__inner">
      <!-- Left side: Page links + Language/Currency -->
      <div class="top-navbar__left">
        <!-- Page Links (desktop only) -->
        <ul class="top-navbar__links">
          <router-link
            v-for="page in settings.storeSettings.headerPages"
            :key="page.slug"
            :to="'/' + page.slug"
            class="topnav-link-item"
          >{{ page.title }}</router-link>
        </ul>

        <!-- Language & Currency Switchers -->
        <div class="top-navbar__switchers">
          <button class="switcher-btn" @click="openLocalization" aria-label="Change Language">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
            <span>{{ currentLanguage?.name || 'English' }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
          </button>

          <span class="switcher-divider">|</span>

          <button class="switcher-btn" @click="openLocalization" aria-label="Change Currency">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="9" y1="11" x2="15" y2="11"></line></svg>
            <span>{{ currentCurrency?.name || 'Saudi Riyal' }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
          </button>
        </div>
      </div>

      <!-- Right side: Contact info -->
      <div class="top-navbar__right">
        <a :href="'mailto:' + settings.storeSettings.email" class="contact-link">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
          <span>{{ settings.storeSettings.email }}</span>
        </a>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useSettingsStore } from '@/stores/settingsStore'

const settings = useSettingsStore()
const currentLanguage = computed(() => settings.currentLanguage)
const currentCurrency = computed(() => settings.currentCurrency)

const emit = defineEmits(['open-localization'])

function openLocalization() {
  emit('open-localization')
}
</script>

<style scoped>
.top-navbar {
  border-bottom: 1px solid var(--product-border-color);
  font-size: 0.8125rem;
  background: var(--header-bg);
  color: var(--header-text-color);
}

.top-navbar__inner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 0.375rem;
  padding-bottom: 0.375rem;
}

.top-navbar__left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex: 1;
}

.top-navbar__links {
  display: none;
  list-style: none;
  margin: 0;
  padding: 0;
  padding-inline-end: 1rem;
  margin-inline-end: 2.1875rem;
}

@media (min-width: 1024px) {
  .top-navbar__links {
    display: flex;
    align-items: center;
  }
}

.topnav-link-item {
  padding: 0 0.75rem;
  border-inline-end: 1px solid var(--product-border-color);
  white-space: nowrap;
  transition: color 0.15s ease;
  text-decoration: none;
  color: inherit;
}
.topnav-link-item:last-child {
  border-inline-end: none;
}
.topnav-link-item:hover {
  color: var(--color-primary);
}

.top-navbar__switchers {
  display: flex;
  align-items: center;
}

.switcher-btn {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  background: none;
  border: none;
  color: inherit;
  font-size: inherit;
  font-family: inherit;
  cursor: pointer;
  padding: 0.25rem 0;
  transition: color 0.15s ease;
  flex-shrink: 0;
}
.switcher-btn:hover {
  color: var(--color-primary);
}
.switcher-btn span {
  flex-shrink: 0;
}

.switcher-divider {
  margin: 0 0.625rem;
  color: var(--product-border-color);
}

.top-navbar__right {
  display: none;
}

@media (min-width: 1024px) {
  .top-navbar__right {
    display: flex;
    align-items: center;
  }
}

.contact-link {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  color: inherit;
  text-decoration: none;
  transition: color 0.15s ease;
}
.contact-link:hover {
  color: var(--color-primary);
}
</style>
