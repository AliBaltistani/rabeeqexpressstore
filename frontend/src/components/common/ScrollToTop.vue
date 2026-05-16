<template>
  <button
    v-show="isVisible"
    class="scroll-to-top"
    :class="{ visible: isVisible }"
    @click="scrollToTop"
    aria-label="Scroll to top"
  >
    <!-- Circular Progress Ring -->
    <svg class="scroll-progress-ring" viewBox="0 0 48 48">
      <circle
        class="scroll-progress-ring__bg"
        cx="24" cy="24" r="20"
        fill="none"
        stroke-width="3"
      />
      <circle
        class="scroll-progress-ring__fill"
        cx="24" cy="24" r="20"
        fill="none"
        stroke-width="3"
        :stroke-dasharray="circumference"
        :stroke-dashoffset="dashOffset"
        stroke-linecap="round"
      />
    </svg>
    <!-- Arrow Icon -->
    <svg class="scroll-arrow-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
  </button>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'

const isVisible = ref(false)
const scrollProgress = ref(0)
const radius = 20
const circumference = 2 * Math.PI * radius

const dashOffset = computed(() => {
  return circumference - (scrollProgress.value * circumference)
})

function handleScroll() {
  const scrollY = window.scrollY
  const docHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight
  isVisible.value = scrollY > 400
  scrollProgress.value = docHeight > 0 ? Math.min(scrollY / docHeight, 1) : 0
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
  left: 1.5rem;
  z-index: 200;
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 50%;
  background: var(--color-primary, #858585);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  opacity: 0;
  visibility: hidden;
  transition: all 0.25s ease;
  border: none;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  padding: 0;
}
html[dir="rtl"] .scroll-to-top { left: auto; right: 1.5rem; }
.scroll-to-top.visible { opacity: 1; visibility: visible; }
.scroll-to-top:hover { background: var(--color-primary-dark, #6b6b6b); transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2); }

/* Progress Ring */
.scroll-progress-ring {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  transform: rotate(-90deg);
}
.scroll-progress-ring__bg {
  stroke: rgba(255, 255, 255, 0.25);
}
.scroll-progress-ring__fill {
  stroke: #fff;
  transition: stroke-dashoffset 0.15s ease;
}

/* Arrow */
.scroll-arrow-icon {
  position: relative;
  z-index: 1;
}

@media (max-width: 1023px) {
  .scroll-to-top { bottom: 5.5rem; }
}
</style>
