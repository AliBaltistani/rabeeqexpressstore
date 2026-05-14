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
            />
            <button type="button" v-if="searchQuery" @click="searchQuery = ''" class="clear-btn">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
          </form>
          <button class="close-btn" @click="close">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'

const props = defineProps<{
  isOpen: boolean
}>()

const emit = defineEmits(['close'])

const router = useRouter()
const searchQuery = ref('')
const searchInput = ref<HTMLInputElement | null>(null)

watch(() => props.isOpen, (val) => {
  if (val) {
    searchQuery.value = ''
    nextTick(() => {
      searchInput.value?.focus()
    })
  }
})

function close() {
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

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
