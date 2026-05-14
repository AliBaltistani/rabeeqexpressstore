<template>
  <div class="blog-post-page">
    <!-- Breadcrumb -->
    <nav class="breadcrumbs container">
      <ol class="breadcrumb-list">
        <li class="breadcrumb-item">
          <router-link to="/">{{ $t('breadcrumb.home') }}</router-link>
        </li>
        <li class="breadcrumb-arrow">
          <svg width="16" height="16" viewBox="0 0 32 32"><path d="M11.438 22.479l6.125-6.125-6.125-6.125 1.875-1.875 8 8-8 8z" fill="currentColor"/></svg>
        </li>
        <li class="breadcrumb-item">
          <router-link to="/blog">{{ $t('blog.title') || 'Blog' }}</router-link>
        </li>
        <li class="breadcrumb-arrow">
          <svg width="16" height="16" viewBox="0 0 32 32"><path d="M11.438 22.479l6.125-6.125-6.125-6.125 1.875-1.875 8 8-8 8z" fill="currentColor"/></svg>
        </li>
        <li class="breadcrumb-item breadcrumb-current">{{ post?.title || '...' }}</li>
      </ol>
    </nav>

    <div class="container">
      <!-- Loading -->
      <div v-if="isLoading" class="loading-state">
        <p>{{ $t('common.loading') }}...</p>
      </div>

      <!-- Error -->
      <div v-else-if="errorMsg" class="error-state">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        <h3>{{ $t('blog.postNotFound') || 'Post not found' }}</h3>
        <router-link to="/blog" class="btn-back">{{ $t('blog.backToBlog') || 'Back to Blog' }}</router-link>
      </div>

      <!-- Post Content -->
      <article v-else-if="post" class="blog-article">
        <header class="blog-article__header">
          <time class="blog-article__date">{{ formatDate(post.createdAt) }}</time>
          <h1 class="blog-article__title">{{ post.title }}</h1>
        </header>

        <div v-if="post.image" class="blog-article__hero">
          <img :src="post.image" :alt="post.title" />
        </div>

        <div class="blog-article__body" v-html="post.content"></div>

        <footer class="blog-article__footer">
          <router-link to="/blog" class="btn-back">
            ← {{ $t('blog.backToBlog') || 'Back to Blog' }}
          </router-link>
        </footer>
      </article>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { fetchBlogPost } from '@/api/services'
import type { BlogPost } from '@/types'

const route = useRoute()
const post = ref<BlogPost | null>(null)
const isLoading = ref(true)
const errorMsg = ref('')

function formatDate(dateStr: string): string {
  try {
    return new Date(dateStr).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    })
  } catch {
    return dateStr
  }
}

async function loadPost(slug: string) {
  isLoading.value = true
  errorMsg.value = ''
  try {
    post.value = await fetchBlogPost(slug)
  } catch (error: any) {
    errorMsg.value = error.response?.data?.message || 'Post not found'
    post.value = null
  } finally {
    isLoading.value = false
  }
}

watch(
  () => route.params.slug,
  (newSlug) => {
    if (newSlug && route.name === 'blog-post') {
      loadPost(newSlug as string)
    }
  }
)

onMounted(() => {
  const slug = route.params.slug as string
  if (slug) loadPost(slug)
})
</script>

<style scoped>
.blog-post-page {
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
  flex-wrap: wrap;
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
  margin: 0 0 1.5rem;
}

.btn-back {
  display: inline-block;
  padding: 0.75rem 1.5rem;
  background: var(--color-primary, #858585);
  color: #fff;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.875rem;
}
.btn-back:hover {
  opacity: 0.9;
}

.blog-article {
  max-width: 800px;
  margin: 0 auto;
}

.blog-article__header {
  margin-bottom: 2rem;
}
.blog-article__date {
  font-size: 0.8125rem;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  display: block;
  margin-bottom: 0.75rem;
}
.blog-article__title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
  margin: 0;
  line-height: 1.3;
}

.blog-article__hero {
  margin-bottom: 2rem;
  border-radius: 12px;
  overflow: hidden;
}
.blog-article__hero img {
  width: 100%;
  height: auto;
  display: block;
}

.blog-article__body {
  line-height: 1.8;
  color: #374151;
  font-size: 1rem;
}
.blog-article__body :deep(h2) {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 2rem 0 1rem;
  color: var(--store-text-primary, #111827);
}
.blog-article__body :deep(h3) {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 1.5rem 0 0.75rem;
  color: var(--store-text-primary, #111827);
}
.blog-article__body :deep(p) {
  margin: 0 0 1rem;
}
.blog-article__body :deep(ul),
.blog-article__body :deep(ol) {
  margin: 0 0 1rem;
  padding-left: 1.5rem;
}
.blog-article__body :deep(a) {
  color: var(--color-primary, #858585);
  text-decoration: underline;
}
.blog-article__body :deep(img) {
  max-width: 100%;
  border-radius: 8px;
  margin: 1rem 0;
}
.blog-article__body :deep(blockquote) {
  border-left: 4px solid var(--color-primary, #858585);
  margin: 1.5rem 0;
  padding: 1rem 1.5rem;
  background: #f9fafb;
  border-radius: 0 8px 8px 0;
  color: #4b5563;
}

.blog-article__footer {
  margin-top: 3rem;
  padding-top: 2rem;
  border-top: 1px solid #e5e7eb;
}
.blog-article__footer .btn-back {
  background: transparent;
  color: var(--color-primary, #858585);
  padding: 0;
  font-weight: 600;
}
.blog-article__footer .btn-back:hover {
  text-decoration: underline;
  opacity: 1;
}
</style>
