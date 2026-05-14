<template>
  <div class="blog-list-page">
    <!-- Breadcrumb -->
    <nav class="breadcrumbs container">
      <ol class="breadcrumb-list">
        <li class="breadcrumb-item">
          <router-link to="/">{{ $t('breadcrumb.home') }}</router-link>
        </li>
        <li class="breadcrumb-arrow">
          <svg width="16" height="16" viewBox="0 0 32 32"><path d="M11.438 22.479l6.125-6.125-6.125-6.125 1.875-1.875 8 8-8 8z" fill="currentColor"/></svg>
        </li>
        <li class="breadcrumb-item breadcrumb-current">{{ $t('blog.title') || 'Blog' }}</li>
      </ol>
    </nav>

    <div class="container">
      <h1 class="page-title">{{ $t('blog.title') || 'Blog' }}</h1>

      <!-- Loading -->
      <div v-if="isLoading" class="loading-state">
        <p>{{ $t('common.loading') }}...</p>
      </div>

      <!-- Empty -->
      <div v-else-if="posts.length === 0" class="empty-state">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        <h3>{{ $t('blog.noPosts') || 'No blog posts yet' }}</h3>
        <p>{{ $t('blog.noPostsDesc') || 'Check back soon for new content!' }}</p>
      </div>

      <!-- Blog Grid -->
      <div v-else>
        <div class="blog-grid">
          <article v-for="post in posts" :key="post.id" class="blog-card">
            <router-link :to="'/blog/' + post.slug" class="blog-card__image-link">
              <img
                v-if="post.image"
                :src="post.image"
                :alt="post.title"
                class="blog-card__image"
                loading="lazy"
              />
              <div v-else class="blog-card__image-placeholder">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              </div>
            </router-link>
            <div class="blog-card__body">
              <time class="blog-card__date">{{ formatDate(post.createdAt) }}</time>
              <router-link :to="'/blog/' + post.slug" class="blog-card__title">
                {{ post.title }}
              </router-link>
              <p v-if="post.excerpt" class="blog-card__excerpt">{{ post.excerpt }}</p>
              <router-link :to="'/blog/' + post.slug" class="blog-card__read-more">
                {{ $t('blog.readMore') || 'Read More' }} →
              </router-link>
            </div>
          </article>
        </div>

        <!-- Pagination -->
        <div v-if="meta && meta.lastPage > 1" class="pagination">
          <button
            class="pagination-btn"
            :disabled="currentPage <= 1"
            @click="loadPosts(currentPage - 1)"
          >
            &laquo; {{ $t('search.prev') || 'Prev' }}
          </button>
          <span class="pagination-info">{{ currentPage }} / {{ meta.lastPage }}</span>
          <button
            class="pagination-btn"
            :disabled="currentPage >= meta.lastPage"
            @click="loadPosts(currentPage + 1)"
          >
            {{ $t('search.next') || 'Next' }} &raquo;
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { fetchBlogPosts } from '@/api/services'
import type { BlogPost, PaginationMeta } from '@/types'

const posts = ref<BlogPost[]>([])
const meta = ref<PaginationMeta | undefined>(undefined)
const isLoading = ref(true)
const currentPage = ref(1)

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

async function loadPosts(page = 1) {
  isLoading.value = true
  currentPage.value = page
  try {
    const result = await fetchBlogPosts(page)
    posts.value = result.data
    meta.value = result.meta
  } catch (error) {
    console.error('Failed to load blog posts:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => loadPosts(1))
</script>

<style scoped>
.blog-list-page {
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

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
  margin: 0 0 2rem;
}

.loading-state {
  text-align: center;
  padding: 6rem 2rem;
  color: #6b7280;
}

.empty-state {
  text-align: center;
  padding: 6rem 2rem;
}
.empty-state svg {
  margin-bottom: 1.5rem;
}
.empty-state h3 {
  font-size: 1.25rem;
  color: #111827;
  margin: 0 0 0.5rem;
}
.empty-state p {
  color: #6b7280;
  margin: 0;
}

.blog-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
}
@media (min-width: 640px) {
  .blog-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
@media (min-width: 1024px) {
  .blog-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

.blog-card {
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: box-shadow 0.3s ease;
  display: flex;
  flex-direction: column;
}
.blog-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.blog-card__image-link {
  display: block;
  aspect-ratio: 16 / 9;
  overflow: hidden;
}
.blog-card__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s ease;
}
.blog-card:hover .blog-card__image {
  transform: scale(1.05);
}
.blog-card__image-placeholder {
  width: 100%;
  height: 100%;
  background: #f3f4f6;
  display: flex;
  align-items: center;
  justify-content: center;
}

.blog-card__body {
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  flex: 1;
}
.blog-card__date {
  font-size: 0.75rem;
  color: #9ca3af;
  margin-bottom: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.blog-card__title {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
  text-decoration: none;
  line-height: 1.4;
  margin-bottom: 0.5rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.blog-card__title:hover {
  color: var(--color-primary, #858585);
}
.blog-card__excerpt {
  font-size: 0.875rem;
  color: #6b7280;
  line-height: 1.6;
  margin: 0 0 1rem;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  flex: 1;
}
.blog-card__read-more {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-primary, #858585);
  text-decoration: none;
  margin-top: auto;
}
.blog-card__read-more:hover {
  text-decoration: underline;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-top: 2.5rem;
  padding: 1rem 0;
}
.pagination-btn {
  padding: 0.5rem 1.25rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: #fff;
  font-size: 0.875rem;
  cursor: pointer;
  color: var(--store-text-primary, #111827);
  transition: all 0.2s;
}
.pagination-btn:hover:not(:disabled) {
  background: var(--color-primary, #858585);
  color: #fff;
  border-color: var(--color-primary, #858585);
}
.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.pagination-info {
  font-size: 0.875rem;
  color: #6b7280;
}
</style>
