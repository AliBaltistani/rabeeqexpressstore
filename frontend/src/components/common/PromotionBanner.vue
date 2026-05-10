<template>
  <div
    v-if="!isHidden"
    class="promotion-banner"
    :style="{ backgroundColor: bgColor }"
  >
    <div class="promotion-banner__inner">
      <ul>
        <li v-for="i in 15" :key="i">
          <a :href="link" :style="{ color: textColor }" aria-label="Promotion Text">
            {{ text }}
          </a>
        </li>
      </ul>
    </div>
    <button
      class="promotion-banner__close"
      @click="dismiss"
      aria-label="Close promotion banner"
    >
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
      </svg>
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useSettingsStore } from '@/stores/settingsStore'

const settings = useSettingsStore()
const isHidden = ref(false)

const bgColor = '#ff0000'
const textColor = '#ffffff'
const text = settings.storeSettings.announcementText
const link = settings.storeSettings.announcementLink || '#'

onMounted(() => {
  const stored = localStorage.getItem('promotionBanner')
  if (stored === 'hidden') {
    const timestamp = localStorage.getItem('promotionBannerTime')
    if (timestamp) {
      const elapsed = Date.now() - parseInt(timestamp)
      // 24 hours = 86400000ms
      if (elapsed < 86400000) {
        isHidden.value = true
      } else {
        localStorage.removeItem('promotionBanner')
        localStorage.removeItem('promotionBannerTime')
      }
    }
  }
})

function dismiss() {
  isHidden.value = true
  localStorage.setItem('promotionBanner', 'hidden')
  localStorage.setItem('promotionBannerTime', Date.now().toString())
}
</script>

<style scoped>
.promotion-banner {
  width: 100%;
  overflow: hidden;
  position: relative;
  z-index: 100;
}

.promotion-banner__inner {
  display: flex;
  width: max-content;
  animation: marquee 40s linear infinite;
}

.promotion-banner__inner ul {
  display: flex;
  list-style: none;
  margin: 0;
  padding: 0;
  gap: 3rem;
}

.promotion-banner__inner li {
  white-space: nowrap;
  font-size: 0.8125rem;
  font-weight: 500;
  padding: 0.4375rem 0;
}

.promotion-banner__inner a {
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.promotion-banner__close {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: #ffffff;
  opacity: 0.7;
  padding: 0.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: opacity 0.15s ease;
  background: none;
  border: none;
}
html[dir="rtl"] .promotion-banner__close {
  right: auto;
  left: 0.75rem;
}
.promotion-banner__close:hover {
  opacity: 1;
}

@keyframes marquee {
  0% { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}
html[dir="rtl"] .promotion-banner__inner {
  animation-name: marquee-rtl;
}
@keyframes marquee-rtl {
  0% { transform: translateX(0); }
  100% { transform: translateX(50%); }
}
</style>
