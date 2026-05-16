<template>
  <transition name="fade">
    <div v-if="isOpen" class="search-overlay" @click.self="close">
      <div class="search-container">
        <div class="search-header">
          <form @submit.prevent="submitSearch" class="search-form">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="search-icon">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input 
              ref="searchInput"
              type="text" 
              v-model="searchQuery" 
              :placeholder="$t('common.searchPlaceholder') || 'Search for products...'" 
              class="search-input"
              @keydown.esc="close"
              autocomplete="off"
            />
            <button type="button" v-if="searchQuery" @click="searchQuery = ''" class="clear-btn">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
          </form>
          <button class="close-btn" @click="close">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
        </div>

        <!-- Live Search Results Dropdown -->
        <div v-if="searchQuery.trim().length >= 2" class="search-dropdown">
          <!-- Loading State -->
          <div v-if="isSearching" class="search-dropdown__loading">
            <div class="search-spinner"></div>
            <span>{{ $t('common.searching') || 'Searching...' }}</span>
          </div>

          <!-- Results -->
          <div v-else-if="searchResults.length > 0" class="search-dropdown__results">
            <router-link
              v-for="product in searchResults"
              :key="product.id"
              :to="'/product/' + product.slug"
              class="search-result-item"
              @click="close"
            >
              <img
                :src="product.primaryImage || product.image || '/storage/dummy/placeholder.jpg'"
                :alt="product.name"
                class="search-result-item__img"
                loading="lazy"
              />
              <div class="search-result-item__info">
                <span class="search-result-item__name">{{ product.name }}</span>
                <span class="search-result-item__price">{{ formatPrice(product) }}</span>
              </div>
            </router-link>
            <!-- View all results link -->
            <button class="search-dropdown__view-all" @click="submitSearch">
              {{ $t('common.viewAllResults') || 'View all results' }} →
            </button>
          </div>

          <!-- No Results -->
          <div v-else-if="hasSearched" class="search-dropdown__empty">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              <line x1="8" y1="8" x2="14" y2="14"></line>
            </svg>
            <span>{{ $t('common.noResults') || 'No products found' }}</span>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { searchProducts } from '@/api/services'

const props = defineProps<{
  isOpen: boolean
}>()

const emit = defineEmits(['close'])

const router = useRouter()
const searchQuery = ref('')
const searchInput = ref<HTMLInputElement | null>(null)
const searchResults = ref<any[]>([])
const isSearching = ref(false)
const hasSearched = ref(false)

let debounceTimer: ReturnType<typeof setTimeout> | null = null

watch(() => props.isOpen, (val) => {
  if (val) {
    searchQuery.value = ''
    searchResults.value = []
    hasSearched.value = false
    isSearching.value = false
    nextTick(() => {
      searchInput.value?.focus()
    })
  }
})

// Debounced live search
watch(searchQuery, (newVal) => {
  if (debounceTimer) clearTimeout(debounceTimer)

  const q = newVal.trim()
  if (q.length < 2) {
    searchResults.value = []
    hasSearched.value = false
    isSearching.value = false
    return
  }

  isSearching.value = true
  hasSearched.value = false

  debounceTimer = setTimeout(async () => {
    try {
      const res = await searchProducts(q, 1, 6)
      searchResults.value = res.data || []
    } catch {
      searchResults.value = []
    } finally {
      isSearching.value = false
      hasSearched.value = true
    }
  }, 300)
})

function formatPrice(product: any): string {
  if (product.flashSalePrice?.formatted) return product.flashSalePrice.formatted
  if (product.price?.formatted) return product.price.formatted
  if (product.price && typeof product.price === 'object' && product.price.raw) {
    return `${Number(product.price.raw).toFixed(0)} ${product.currency || 'SAR'}`
  }
  return `${Number(product.price || 0).toFixed(0)} ${product.currency || 'SAR'}`
}

function close() {
  if (debounceTimer) clearTimeout(debounceTimer)
  emit('close')
}

function submitSearch() {
  if (searchQuery.value.trim()) {
    router.push({ name: 'search', query: { q: searchQuery.value.trim() } })
    close()
  }
}
</script>

<style scoped>
.search-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  backdrop-filter: blur(4px);
  z-index: 1000;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding-top: 10vh;
}
.search-container {
  background: #fff;
  width: 90%;
  max-width: 600px;
  border-radius: 12px;
  padding: 1rem;
  box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
}
.search-header {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.search-form {
  flex: 1;
  display: flex;
  align-items: center;
  background: #f3f4f6;
  border-radius: 8px;
  padding: 0 1rem;
}
.search-icon {
  color: #6b7280;
  flex-shrink: 0;
}
.search-input {
  flex: 1;
  background: transparent;
  border: none;
  padding: 1rem;
  font-size: 1rem;
  color: #111827;
  outline: none;
}
.clear-btn {
  background: transparent;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  padding: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
}
.clear-btn:hover {
  color: #4b5563;
}
.close-btn {
  background: transparent;
  border: none;
  color: #6b7280;
  cursor: pointer;
  padding: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s;
}
.close-btn:hover {
  color: #111827;
  transform: rotate(90deg);
}

/* ─── Dropdown ─── */
.search-dropdown {
  margin-top: 0.75rem;
  border-top: 1px solid #f3f4f6;
  max-height: 400px;
  overflow-y: auto;
}

/* Loading */
.search-dropdown__loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2rem 1rem;
  color: #6b7280;
  font-size: 0.875rem;
}
.search-spinner {
  width: 20px;
  height: 20px;
  border: 2.5px solid #e5e7eb;
  border-top-color: var(--color-primary, #858585);
  border-radius: 50%;
  animation: search-spin 0.7s linear infinite;
}
@keyframes search-spin {
  to { transform: rotate(360deg); }
}

/* Results */
.search-dropdown__results {
  display: flex;
  flex-direction: column;
}
.search-result-item {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.75rem;
  text-decoration: none;
  border-radius: 8px;
  transition: background 0.15s ease;
}
.search-result-item:hover {
  background: #f9fafb;
}
.search-result-item__img {
  width: 56px;
  height: 56px;
  border-radius: 8px;
  object-fit: contain;
  background: #f9fafb;
  border: 1px solid #f3f4f6;
  flex-shrink: 0;
}
.search-result-item__info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 0;
}
.search-result-item__name {
  font-size: 0.875rem;
  font-weight: 600;
  color: #111827;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.search-result-item__price {
  font-size: 0.8125rem;
  font-weight: 700;
  color: var(--color-primary, #ef4444);
}

/* View All */
.search-dropdown__view-all {
  display: block;
  width: 100%;
  padding: 0.75rem;
  text-align: center;
  background: none;
  border: none;
  border-top: 1px solid #f3f4f6;
  color: var(--color-primary, #858585);
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s;
}
.search-dropdown__view-all:hover {
  background: #f9fafb;
}

/* No Results */
.search-dropdown__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2.5rem 1rem;
  color: #9ca3af;
  font-size: 0.875rem;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
