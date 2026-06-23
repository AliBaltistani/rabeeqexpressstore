<template>
  <section class="banner-grid-section" :class="[positionClass]">
    <div :class="containerClass">
      <div class="banner-grid" :style="gridStyle">
        <router-link
          v-for="banner in banners"
          :key="banner.id"
          :to="banner.linkUrl || '/'"
          class="banner-grid__item"
        >
          <img
            :src="banner.image"
            :alt="banner.title || 'Banner'"
            class="banner-grid__image"
            loading="lazy"
          />
        </router-link>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue'

interface BannerItem {
  id: number
  title?: string
  image: string
  linkUrl?: string
}

interface BannerConfig {
  cols?: number
  rows?: number
  gap?: string
  position?: string
}

const props = defineProps<{
  banners: BannerItem[]
  config: BannerConfig
}>()

// ── Responsive column detection ─────────────────────────────────────────────
const isMobile = ref(false)

function checkMobile() {
  isMobile.value = window.innerWidth <= 767
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile, { passive: true })
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})

// ── Gap map ──────────────────────────────────────────────────────────────────
const gapMap: Record<string, string> = {
  none: '0',
  sm: '0.5rem',
  md: '1rem',
  lg: '1.5rem',
}

// ── Mobile column logic ──────────────────────────────────────────────────────
// Rules:
//   cols = 1  →  1 full-width column (don't force 2!)
//   cols >= 2 →  cap to 2 columns on mobile for readability
const mobileCols = computed(() => {
  const configCols = props.config.cols || 2
  if (configCols === 1) return 1
  return Math.min(configCols, 2)
})

// ── Grid style — uses mobileCols when on mobile, full cols on desktop ────────
const gridStyle = computed(() => {
  const cols = isMobile.value ? mobileCols.value : (props.config.cols || 2)
  const baseGap = gapMap[props.config.gap || 'md'] || '1rem'
  const gap = isMobile.value ? '0.375rem' : baseGap

  return {
    gridTemplateColumns: `repeat(${cols}, 1fr)`,
    gridTemplateRows:
      props.config.rows && props.config.rows > 1
        ? `repeat(${props.config.rows}, 1fr)`
        : undefined,
    gap,
  }
})

// ── Position / container ─────────────────────────────────────────────────────
const positionClass = computed(() => {
  const pos = props.config.position || 'full-width'
  return `banner-pos-${pos}`
})

const containerClass = computed(() => {
  const pos = props.config.position || 'full-width'
  return pos === 'full-width' ? '' : 'container'
})
</script>

<style scoped>
.banner-grid-section {
  padding: 0.5rem 0;
}

/* Left / Right positioned banner sets get explicit padding.
   Contained banners: parent .container already supplies horizontal padding. */
.banner-pos-left {
  padding-left: 1rem;
  padding-right: 1rem;
  display: flex;
  justify-content: flex-start;
}
.banner-pos-right {
  padding-left: 1rem;
  padding-right: 1rem;
  display: flex;
  justify-content: flex-end;
}

/* Grid shell — columns & gap are driven entirely by :style binding above */
.banner-grid {
  display: grid;
  width: 100%;
}

/* Banner item */
.banner-grid__item {
  display: block;
  overflow: hidden;
  border-radius: 0.375rem;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.banner-grid__item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

/* Banner image — height: auto so aspect ratio is always preserved */
.banner-grid__image {
  width: 100%;
  height: auto;
  object-fit: cover;
  display: block;
}

/* Mobile: only tweak border-radius — columns/gap handled by JS computed */
@media (max-width: 767px) {
  .banner-grid__item {
    border-radius: 0.25rem;
  }
}
</style>
