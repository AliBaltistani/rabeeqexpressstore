<template>
  <section class="products-section container">
    <SectionTitle
      v-if="sectionTitle"
      :title="sectionTitle"
    />

    <div class="products-grid" :style="gridStyle">
      <div
        v-for="product in products"
        :key="product.id"
        class="products-grid__item"
      >
        <ProductCard
          :product="mapProduct(product)"
          :showPrice="config.show_price !== false"
          :showBadge="config.show_badge !== false"
          :showAddToCart="config.show_add_to_cart !== false"
        />
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import SectionTitle from './SectionTitle.vue'
import ProductCard from './ProductCard.vue'

interface ProductItem {
  id: number
  slug: string
  name: string
  primaryImage?: string
  price?: { raw: number }
  comparePrice?: { raw: number }
  flashSalePrice?: { raw: number }
  discountPercent?: number
  currency?: string
  category?: { name?: string }
}

interface ProductsConfig {
  title_en?: string
  title_ar?: string
  cols?: number
  show_price?: boolean
  show_badge?: boolean
  show_add_to_cart?: boolean
}

const props = defineProps<{
  products: ProductItem[]
  config: ProductsConfig
}>()

const { locale } = useI18n()

const sectionTitle = computed(() => {
  return locale.value === 'ar'
    ? (props.config.title_ar || props.config.title_en || '')
    : (props.config.title_en || '')
})

const gridStyle = computed(() => ({
  gridTemplateColumns: `repeat(${props.config.cols || 4}, 1fr)`,
}))

function mapProduct(p: ProductItem) {
  return {
    id: p.id,
    slug: p.slug,
    name: p.name,
    subtitle: p.category?.name || undefined,
    image: p.primaryImage || '',
    price: p.flashSalePrice?.raw ?? p.price?.raw ?? 0,
    oldPrice: p.comparePrice?.raw || undefined,
    discount: p.discountPercent || undefined,
    currency: p.currency || 'SAR',
  }
}
</script>

<style scoped>
.products-section {
  padding: 1.5rem 0;
}
.products-grid {
  display: grid;
  gap: 1rem;
}
.products-grid__item {
  min-width: 0;
}

@media (max-width: 1023px) {
  .products-grid {
    grid-template-columns: repeat(3, 1fr) !important;
  }
}
@media (max-width: 767px) {
  .products-grid {
    grid-template-columns: repeat(2, 1fr) !important;
  }
}
</style>
