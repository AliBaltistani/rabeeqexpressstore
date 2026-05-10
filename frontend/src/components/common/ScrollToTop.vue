<template>
  <button
    v-show="isVisible"
    class="scroll-to-top"
    :class="{ visible: isVisible }"
    @click="scrollToTop"
    aria-label="Scroll to top"
  >
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
  </button>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

const isVisible = ref(false)

function handleScroll() {
  isVisible.value = window.scrollY > 400
}

function scrollToTop() {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

onMounted(() => window.addEventListener('scroll', handleScroll, { passive: true }))
onUnmounted(() => window.removeEventListener('scroll', handleScroll))
</script>

<style scoped>
.scroll-to-top {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  z-index: 200;
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 50%;
  background: var(--color-primary);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  opacity: 0;
  visibility: hidden;
  transition: all 0.25s ease;
  border: none;
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
}
html[dir="rtl"] .scroll-to-top { right: auto; left: 1.5rem; }
.scroll-to-top.visible { opacity: 1; visibility: visible; }
.scroll-to-top:hover { background: var(--color-primary-dark); }
@media (max-width: 1023px) {
  .scroll-to-top { bottom: 5.5rem; right: auto; left: 1.5rem; }
  html[dir="rtl"] .scroll-to-top { left: auto; right: 1.5rem; }
}
</style>
