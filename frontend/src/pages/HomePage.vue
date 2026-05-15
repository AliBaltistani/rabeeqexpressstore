<template>
  <div class="home-page">
    <template v-for="section in sections" :key="section.id">

      <!-- Hero Slider -->
      <section v-if="section.type === 'hero_slider' && section.data?.length" class="home-section">
        <div :class="section.config?.width === 'contained' ? 'container' : ''">
          <HeroSlider :slides="mapHeroSlides(section.data)" :config="section.config" />
        </div>
      </section>

      <!-- Banner Grid -->
      <section v-else-if="section.type === 'banner' && section.data?.length" class="home-section">
        <BannerGrid :banners="section.data" :config="section.config || {}" />
      </section>

      <!-- Products -->
      <section v-else-if="section.type === 'products' && section.data?.length" class="home-section">
        <ProductsSection :products="section.data" :config="section.config || {}" />
      </section>

      <!-- Custom HTML -->
      <section v-else-if="section.type === 'custom_html'" class="home-section">
        <div class="container" v-if="section.config?.title_en || section.config?.title_ar">
          <h2 class="custom-html__title">
            {{ $i18n.locale === 'ar' ? (section.config.title_ar || section.config.title_en) : section.config.title_en }}
          </h2>
        </div>
        <div class="custom-html__content" v-html="section.config?.content || ''"></div>
      </section>

    </template>

    <!-- Loading state -->
    <div v-if="loading" class="home-loading">
      <p>{{ $t('common.loading') }}...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import HeroSlider from '@/components/home/HeroSlider.vue'
import BannerGrid from '@/components/home/BannerGrid.vue'
import ProductsSection from '@/components/home/ProductsSection.vue'
import { fetchHomeSections } from '@/api/services'

// ─── State ───
const sections = ref<any[]>([])
const loading = ref(true)

/**
 * Map hero slider API data to the shape HeroSlider expects.
 */
function mapHeroSlides(sliders: any[]) {
  return (sliders || []).map((s: any) => ({
    image: s.image || '',
    link: s.link_url || '/',
    alt: s.title || 'Slide',
  }))
}

onMounted(async () => {
  try {
    const data = await fetchHomeSections()
    sections.value = data || []
  } catch (e) {
    console.error('Failed to fetch homepage sections:', e)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.home-page {
  background: var(--bg-primary, #fff);
}

.home-section {
  margin-bottom: 0.25rem;
}

/* Custom HTML */
.custom-html__title {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1rem;
  color: var(--color-text, #111827);
}
.custom-html__content {
  line-height: 1.6;
}

/* Loading */
.home-loading {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: var(--space-3xl, 4rem);
  color: var(--footer-text-color, #374151);
  font-size: 1rem;
}
</style>
