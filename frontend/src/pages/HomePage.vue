<template>
  <div class="home-page">
    <template v-for="section in sections" :key="section.id">
      <div
        :class="[
          'home-section',
          section.config?.css_class || '',
          section.config?.background ? 'home-bg-' + section.config.background : '',
          section.config?.padding ? 'home-pad-' + section.config.padding : 'home-pad-md',
        ]"
      >

        <!-- Hero Slider -->
        <section v-if="section.type === 'hero_slider'" class="s-block banners-slider">
          <div class="container">
            <HeroSlider :slides="mapHeroSlides(section.data)" />
          </div>
        </section>

        <!-- Promo Banners (grid) -->
        <template v-if="section.type === 'promo_banners' && section.data?.length">
          <DoubleBanner v-if="section.data.length >= 2" :banners="mapPromoBanners(section.data.slice(0, 2))" />
          <DoubleBanner v-if="section.data.length >= 4" :banners="mapPromoBanners(section.data.slice(2, 4))" />
        </template>

        <!-- Featured Products -->
        <ProductSlider
          v-if="section.type === 'featured_products' && section.data?.length"
          :title="section.title"
          :products="mapProducts(section.data)"
          :view-all-link="section.config?.view_all_url || '/products?featured=true'"
        />

        <!-- Best Sellers -->
        <ProductSlider
          v-if="section.type === 'best_sellers' && section.data?.length"
          :title="section.title"
          :products="mapProducts(section.data)"
          :view-all-link="section.config?.view_all_url || '/products?sortBy=best_seller'"
        />

        <!-- New Arrivals -->
        <ProductSlider
          v-if="section.type === 'new_arrivals' && section.data?.length"
          :title="section.title"
          :products="mapProducts(section.data)"
          :view-all-link="section.config?.view_all_url || '/products?sortBy=newest'"
        />

        <!-- Category Products -->
        <ProductSlider
          v-if="section.type === 'category_products' && section.data?.length"
          :title="section.title"
          :products="mapProducts(section.data)"
          :view-all-link="'/category/' + (section.category?.slug || '')"
        />

        <!-- Customer Reviews -->
        <TestimonialsSlider
          v-if="section.type === 'reviews' && section.data?.length"
          :title="section.title"
          :reviews="section.data"
        />

      </div>
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
import DoubleBanner from '@/components/home/DoubleBanner.vue'
import ProductSlider from '@/components/home/ProductSlider.vue'
import TestimonialsSlider from '@/components/home/TestimonialsSlider.vue'
import { fetchHomeSections } from '@/api/services'

// ─── State ───
const sections = ref<any[]>([])
const loading = ref(true)

/**
 * Map hero banner API data to the shape HeroSlider expects.
 */
function mapHeroSlides(banners: any[]) {
  return (banners || []).map((b: any) => ({
    image: b.image || '',
    link: b.linkUrl || '/',
    alt: b.title || 'Banner',
  }))
}

/**
 * Map promo banner API data to the shape DoubleBanner expects.
 */
function mapPromoBanners(banners: any[]) {
  return (banners || []).map((b: any) => ({
    image: b.image || '',
    link: b.linkUrl || '/',
  }))
}

/**
 * Map API Product to the shape ProductCard expects.
 */
function mapProducts(products: any[]) {
  return (products || []).map((p: any) => ({
    id: p.id,
    slug: p.slug,
    name: p.name,
    subtitle: p.category?.name || undefined,
    image: p.primaryImage || '',
    price: p.flashSalePrice?.raw ?? p.price?.raw ?? 0,
    oldPrice: p.comparePrice?.raw || undefined,
    discount: p.discountPercent || undefined,
    currency: p.currency || 'SAR',
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

/* Shared s-block spacing */
.s-block {
  margin-bottom: 0.25rem;
}

/* ── Section wrapper ── */
.home-section {
  /* no extra styles by default */
}

/* ── Background styles (controlled from admin) ── */
.home-bg-default { background: var(--bg-primary, #fff); }
.home-bg-light { background: var(--bg-secondary, #f7f7f8); }
.home-bg-primary { background: var(--color-primary, #0d6efd); color: #fff; }
.home-bg-dark { background: var(--footer-bg-color, #1a1a2e); color: #fff; }

/* ── Padding styles (controlled from admin) ── */
.home-pad-none { padding-top: 0; padding-bottom: 0; }
.home-pad-sm { padding-top: 0.75rem; padding-bottom: 0.75rem; }
.home-pad-md { padding-top: 1.25rem; padding-bottom: 1.25rem; }
.home-pad-lg { padding-top: 2.5rem; padding-bottom: 2.5rem; }

/* ── Loading ── */
.home-loading {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: var(--space-3xl, 4rem);
  color: var(--footer-text-color, #374151);
  font-size: 1rem;
}
</style>
