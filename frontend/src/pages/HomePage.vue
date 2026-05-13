<template>
  <div class="home-page">
    <!-- 1. Hero Banner Slider -->
    <section class="s-block banners-slider">
      <div class="container">
        <HeroSlider :slides="heroSlides" />
      </div>
    </section>

    <!-- 2. Promo Banners (double) -->
    <DoubleBanner v-if="promoBanners.length >= 2" :banners="promoBanners.slice(0, 2)" />
    <DoubleBanner v-if="promoBanners.length >= 4" :banners="promoBanners.slice(2, 4)" />

    <!-- 3. Featured Products -->
    <ProductSlider v-if="featuredProducts.length" :title="$t('product.viewAll')" :products="mapProducts(featuredProducts)" view-all-link="/products?featured=true" />

    <!-- 4. Best Sellers -->
    <ProductSlider v-if="bestSellers.length" :title="$t('category.bestSeller')" :products="mapProducts(bestSellers)" view-all-link="/products?sortBy=best_seller" />

    <!-- 5. New Arrivals -->
    <ProductSlider v-if="newArrivals.length" :title="$t('common.viewAll')" :products="mapProducts(newArrivals)" view-all-link="/products?sortBy=newest" />

    <!-- 6. Category-based Product Sliders (dynamic from API categories) -->
    <template v-for="section in categorySections" :key="section.slug">
      <ProductSlider
        v-if="section.products.length"
        :title="section.name"
        :products="mapProducts(section.products)"
        :view-all-link="'/category/' + section.slug"
      />
    </template>

    <!-- 7. Customer Reviews -->
    <TestimonialsSlider :title="$t('common.customersReviews')" :reviews="customerReviews" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import HeroSlider from '@/components/home/HeroSlider.vue'
import DoubleBanner from '@/components/home/DoubleBanner.vue'
import ProductSlider from '@/components/home/ProductSlider.vue'
import TestimonialsSlider from '@/components/home/TestimonialsSlider.vue'
import {
  fetchBanners, fetchFeaturedProducts, fetchBestSellers,
  fetchNewArrivals, fetchProducts,
} from '@/api/services'
import { useSettingsStore } from '@/stores/settingsStore'
import type { Product, Banner } from '@/types'

const settings = useSettingsStore()

// ─── State ───
const heroSlides = ref<{ image: string; link: string; alt?: string }[]>([])
const promoBanners = ref<{ image: string; link: string }[]>([])
const featuredProducts = ref<Product[]>([])
const bestSellers = ref<Product[]>([])
const newArrivals = ref<Product[]>([])
const categorySections = ref<{ name: string; slug: string; products: Product[] }[]>([])

const customerReviews = ref([
  { name: 'سلوى الحوطي', avatar: 'https://cdn.assets.salla.network/prod/stores/themes/default/assets/images/avatar_female.png', rating: 5, text: 'الشوز مريح جدا' },
  { name: 'Gharam .', avatar: 'https://cdn.assets.salla.network/prod/stores/themes/default/assets/images/avatar_female.png', rating: 5, text: 'Very comfortable shoes and fast delivery!' },
  { name: 'محمد العتيبي', avatar: 'https://cdn.assets.salla.network/prod/stores/themes/default/assets/images/avatar_male.png', rating: 5, text: 'جودة ممتازة وسعر مناسب' },
  { name: 'Sarah K.', avatar: 'https://cdn.assets.salla.network/prod/stores/themes/default/assets/images/avatar_female.png', rating: 5, text: 'Amazing quality, will order again!' },
  { name: 'عبدالله الشمري', avatar: 'https://cdn.assets.salla.network/prod/stores/themes/default/assets/images/avatar_male.png', rating: 4, text: 'الحذاء جميل والتوصيل سريع' },
  { name: 'Nora A.', avatar: 'https://cdn.assets.salla.network/prod/stores/themes/default/assets/images/avatar_female.png', rating: 5, text: 'Best store for shoes in Saudi!' },
  { name: 'فهد القحطاني', avatar: 'https://cdn.assets.salla.network/prod/stores/themes/default/assets/images/avatar_male.png', rating: 5, text: 'تجربة رائعة' },
  { name: 'Lina M.', avatar: 'https://cdn.assets.salla.network/prod/stores/themes/default/assets/images/avatar_female.png', rating: 5, text: 'عجبتني' },
])

/**
 * Map API Product to the shape ProductCard expects.
 */
function mapProducts(products: Product[]) {
  return products.map(p => ({
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
  // Fetch all homepage data in parallel
  const [bannersRes, featuredRes, bestRes, newRes] = await Promise.allSettled([
    fetchBanners(),
    fetchFeaturedProducts(10),
    fetchBestSellers(10),
    fetchNewArrivals(10),
  ])

  // Process banners
  if (bannersRes.status === 'fulfilled') {
    const banners = bannersRes.value || []

    heroSlides.value = banners
      .filter((b: Banner) => b.position === 'hero')
      .map((b: Banner) => ({
        image: b.image || '',
        link: b.linkUrl || '/',
        alt: b.title || 'Banner',
      }))

    promoBanners.value = banners
      .filter((b: Banner) => b.position === 'promo')
      .map((b: Banner) => ({
        image: b.image || '',
        link: b.linkUrl || '/',
      }))
  }

  // Process products
  if (featuredRes.status === 'fulfilled') {
    featuredProducts.value = featuredRes.value || []
  }
  if (bestRes.status === 'fulfilled') {
    bestSellers.value = bestRes.value || []
  }
  if (newRes.status === 'fulfilled') {
    newArrivals.value = newRes.value || []
  }

  // Load category-based sections from first few nav categories
  const cats = settings.navCategories.slice(0, 6)
  const catPromises = cats.map(async (cat) => {
    try {
      const result = await fetchProducts({ category: cat.slug, perPage: 10 })
      return { name: cat.name, slug: cat.slug, products: result.data }
    } catch {
      return { name: cat.name, slug: cat.slug, products: [] }
    }
  })
  const catResults = await Promise.allSettled(catPromises)
  categorySections.value = catResults
    .filter((r): r is PromiseFulfilledResult<any> => r.status === 'fulfilled')
    .map(r => r.value)
    .filter(s => s.products.length > 0)
})
</script>

<style scoped>
.home-page {
  background: var(--bg-primary, #fff);
}

/* Shared s-block spacing to match the Salla template */
.s-block {
  margin-bottom: 0.25rem;
}

/* Fixed banner section */
.fixed-banner-section {
  padding: 0.5rem 0;
}
.fixed-banner__link {
  display: block;
  overflow: hidden;
  transition: transform 0.3s ease;
}
.fixed-banner__link:hover {
  transform: translateY(-2px);
}
.fixed-banner__img {
  width: 100%;
  height: auto;
  max-width: 100%;
  object-fit: contain;
  display: block;
}
</style>
