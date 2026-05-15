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
import { computed } from 'vue'

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

const gapMap: Record<string, string> = {
  none: '0',
  sm: '0.5rem',
  md: '1rem',
  lg: '1.5rem',
}

const gridStyle = computed(() => ({
  gridTemplateColumns: `repeat(${props.config.cols || 2}, 1fr)`,
  gridTemplateRows: props.config.rows && props.config.rows > 1
    ? `repeat(${props.config.rows}, 1fr)`
    : undefined,
  gap: gapMap[props.config.gap || 'md'] || '1rem',
}))

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
  padding: 0.75rem 0;
}
.banner-pos-contained,
.banner-pos-left,
.banner-pos-right {
  padding-left: 1rem;
  padding-right: 1rem;
}
.banner-pos-left {
  display: flex;
  justify-content: flex-start;
}
.banner-pos-right {
  display: flex;
  justify-content: flex-end;
}

.banner-grid {
  display: grid;
  width: 100%;
}
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
.banner-grid__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

@media (max-width: 767px) {
  .banner-grid {
    grid-template-columns: repeat(1, 1fr) !important;
  }
}
</style>
