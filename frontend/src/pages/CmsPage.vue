<template>
  <div class="cms-page">
    <!-- Breadcrumb -->
    <nav class="breadcrumbs container">
      <ol class="breadcrumb-list">
        <li class="breadcrumb-item">
          <router-link to="/">{{ $t('breadcrumb.home') }}</router-link>
        </li>
        <li class="breadcrumb-arrow">
          <svg width="16" height="16" viewBox="0 0 32 32"><path d="M11.438 22.479l6.125-6.125-6.125-6.125 1.875-1.875 8 8-8 8z" fill="currentColor"/></svg>
        </li>
        <li class="breadcrumb-item breadcrumb-current">{{ page?.title || slug }}</li>
      </ol>
    </nav>

    <div class="container">
      <!-- Loading -->
      <div v-if="isLoading" class="loading-state">
        <p>{{ $t('common.loading') }}...</p>
      </div>

      <!-- Error -->
      <div v-else-if="errorMsg" class="error-state">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        <h3>{{ $t('cms.notFound') || 'Page not found' }}</h3>
        <p>{{ $t('cms.notFoundDesc') || 'The page you are looking for could not be found.' }}</p>
        <router-link to="/" class="btn-home">{{ $t('breadcrumb.home') }}</router-link>
      </div>

      <!-- Page Content -->
      <div v-else-if="page" class="cms-content">
        <img v-if="page.featuredImage" :src="page.featuredImage" :alt="page.title" class="cms-featured-image" />
        <h1>{{ page.title }}</h1>
        <div class="cms-body" v-html="page.content"></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { fetchCmsPage } from '@/api/services'
import type { CmsPage } from '@/types'

const route = useRoute()
const page = ref<CmsPage | null>(null)
const isLoading = ref(true)
const errorMsg = ref('')
const slug = ref('')

async function loadPage(pageSlug: string) {
  isLoading.value = true
  errorMsg.value = ''
  slug.value = pageSlug
  try {
    page.value = await fetchCmsPage(pageSlug)
  } catch (error: any) {
    errorMsg.value = error.response?.data?.message || 'Page not found'
    page.value = null
  } finally {
    isLoading.value = false
  }
}

watch(
  () => route.params.slug,
  (newSlug) => {
    if (newSlug && route.name === 'cms-page') {
      loadPage(newSlug as string)
    }
  }
)

onMounted(() => {
  const s = route.params.slug as string
  if (s) loadPage(s)
})
</script>

<style scoped>
.cms-page {
  padding-bottom: 4rem;
}
.breadcrumbs {
  padding: 1.5rem 1rem;
}
.breadcrumb-list {
  display: flex;
  align-items: center;
  list-style: none;
  margin: 0;
  padding: 0;
  gap: 0.5rem;
  font-size: 0.875rem;
}
.breadcrumb-item a {
  color: #6b7280;
  text-decoration: none;
}
.breadcrumb-item a:hover {
  color: var(--color-primary, #858585);
}
.breadcrumb-current {
  color: var(--store-text-primary, #111827);
  font-weight: 500;
}
.breadcrumb-arrow {
  color: #d1d5db;
  display: flex;
  align-items: center;
}

.loading-state {
  text-align: center;
  padding: 6rem 2rem;
  color: #6b7280;
}

.error-state {
  text-align: center;
  padding: 6rem 2rem;
}
.error-state svg {
  margin-bottom: 1.5rem;
}
.error-state h3 {
  font-size: 1.25rem;
  color: #111827;
  margin: 0 0 0.5rem;
}
.error-state p {
  color: #6b7280;
  margin: 0 0 1.5rem;
}
.btn-home {
  display: inline-block;
  padding: 0.75rem 1.5rem;
  background: var(--color-primary, #858585);
  color: #fff;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 600;
}

.cms-content {
  max-width: 800px;
  margin: 0 auto;
  padding: 1rem 0;
}
.cms-featured-image {
  width: 100%;
  max-height: 400px;
  object-fit: cover;
  border-radius: 12px;
  margin-bottom: 2rem;
}
.cms-content h1 {
  font-size: 2rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
  margin: 0 0 2rem;
}
.cms-body {
  line-height: 1.75;
  color: #374151;
  font-size: 1rem;
}
.cms-body :deep(h2) {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 2rem 0 1rem;
  color: var(--store-text-primary, #111827);
}
.cms-body :deep(h3) {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 1.5rem 0 0.75rem;
  color: var(--store-text-primary, #111827);
}
.cms-body :deep(p) {
  margin: 0 0 1rem;
}
.cms-body :deep(ul),
.cms-body :deep(ol) {
  margin: 0 0 1rem;
  padding-left: 1.5rem;
}
.cms-body :deep(a) {
  color: var(--color-primary, #858585);
  text-decoration: underline;
}
.cms-body :deep(img) {
  max-width: 100%;
  border-radius: 8px;
  margin: 1rem 0;
}
</style>
