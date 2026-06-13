<template>
  <section class="products-section container">
    <SectionTitle
      v-if="sectionTitle && config.show_title !== false"
      :title="sectionTitle"
      :alignment="(config.title_alignment as 'left' | 'center' | 'right') || 'center'"
    />

    <!-- SLIDER MODE -->
    <div
      v-if="isSlider"
      class="products-slider"
      ref="sliderRef"
      @mouseenter="pauseAutoplay"
      @mouseleave="onMouseLeave"
      @mousedown.prevent="onDragStart"
      @mousemove="onDragMove"
      @mouseup="onDragEnd"
      @touchstart.passive="onTouchStart"
      @touchmove.passive="onTouchMove"
      @touchend="onTouchEnd"
    >
      <!-- Prev Arrow -->
      <button
        v-if="showArrows && products.length > slidesPerView"
        class="products-arrow products-arrow--prev"
        :class="arrowClasses"
        @click="slidePrev"
        aria-label="Previous products"
      >
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      </button>

      <div
        class="products-slider__track"
        ref="trackRef"
        :style="{
          transform: `translateX(${currentTranslate}px)`,
          transition: isDragging ? 'none' : 'transform 0.5s cubic-bezier(0.4, 0, 0.2, 1)',
          direction: effectiveDirection === 'rtl' ? 'rtl' : 'ltr',
        }"
      >
        <div
          v-for="product in products"
          :key="product.id"
          class="products-slider__item"
          :style="{ flex: `0 0 calc((100% - ${(slidesPerView - 1) * 12}px) / ${slidesPerView})` }"
        >
          <ProductCard
            :product="mapProduct(product)"
            :showPrice="config.show_price !== false"
            :showBadge="config.show_badge !== false"
            :showAddToCart="config.show_add_to_cart !== false"
          />
        </div>
      </div>

      <!-- Next Arrow -->
      <button
        v-if="showArrows && products.length > slidesPerView"
        class="products-arrow products-arrow--next"
        :class="arrowClasses"
        @click="slideNext"
        aria-label="Next products"
      >
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    </div>

    <!-- GRID MODE -->
    <div v-else class="products-grid" :style="gridStyle">
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
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import SectionTitle from './SectionTitle.vue'
import ProductCard from './ProductCard.vue'

interface ProductItem {
  id: number
  name: string
  slug: string
  price: any
  comparePrice?: any
  flashSalePrice?: any
  discountPercent?: number
  currency?: string
  primaryImage?: string
  images?: any[]
  category?: {
    id: number
    name: string
    slug: string
  }
}

interface ProductsConfig {
  title_en?: string
  title_ar?: string
  cols?: number
  show_price?: boolean
  show_badge?: boolean
  show_add_to_cart?: boolean
  display_mode?: string
  autoplay?: boolean
  autoplay_delay?: number
  slides_per_view?: number
  loop?: boolean
  direction?: string
  // Common config
  show_title?: boolean
  title_alignment?: string
  show_arrows?: boolean
  arrows_style?: string
  arrows_position?: string
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

const isSlider = computed(() => (props.config.display_mode || 'slider') === 'slider')

const configSlidesPerView = computed(() => props.config.slides_per_view || 5)

/** Responsive slides-per-view: adapts to actual container width */
const responsiveSlidesPerView = ref(5)

function calcResponsivePerView(): number {
  const w = sliderRef.value?.clientWidth || window.innerWidth
  const configured = configSlidesPerView.value
  if (w < 480) return Math.min(configured, 2)
  if (w < 640) return Math.min(configured, 2.5)
  if (w < 768) return Math.min(configured, 3)
  if (w < 1024) return Math.min(configured, 3)
  if (w < 1280) return Math.min(configured, 4)
  return configured
}

const slidesPerView = computed(() => responsiveSlidesPerView.value)

const effectiveDirection = computed(() => {
  const dir = props.config.direction || 'auto'
  if (dir === 'auto') return locale.value === 'ar' ? 'rtl' : 'ltr'
  return dir
})

const gridStyle = computed(() => ({
  gridTemplateColumns: `repeat(${props.config.cols || 4}, 1fr)`,
}))

// ─── Arrow config ───
const showArrows = computed(() => props.config.show_arrows !== false)

const arrowClasses = computed(() => {
  const style = props.config.arrows_style || 'rounded'
  const position = props.config.arrows_position || 'inside'
  return [
    `products-arrow--${style}`,
    `products-arrow--pos-${position}`,
  ]
})

function mapProduct(p: ProductItem) {
  return {
    id: p.id,
    slug: p.slug,
    name: p.name,
    subtitle: p.category?.name || undefined,
    image: p.primaryImage || '',
    primaryImage: p.primaryImage || '',
    images: p.images || [],
    price: p.flashSalePrice?.raw ?? p.price?.raw ?? 0,
    oldPrice: p.comparePrice?.raw || undefined,
    discount: p.discountPercent || undefined,
    currency: p.currency || 'SAR',
  }
}

// ─── Slider Logic ───
const sliderRef = ref<HTMLElement | null>(null)
const trackRef = ref<HTMLElement | null>(null)
const currentTranslate = ref(0)
const isDragging = ref(false)

let autoplayTimer: ReturnType<typeof setInterval> | null = null
let dragStartX = 0
let dragStartTranslate = 0
const gap = 12

function getItemWidth(): number {
  if (!sliderRef.value) return 220
  const perView = Math.floor(slidesPerView.value) || 2
  return (sliderRef.value.clientWidth - gap * (perView - 1)) / slidesPerView.value
}

function getMaxTranslate(): number {
  if (!sliderRef.value) return 0
  const itemWidth = getItemWidth()
  const totalWidth = props.products.length * (itemWidth + gap) - gap
  return Math.max(0, totalWidth - sliderRef.value.clientWidth)
}

function slideNext() {
  const itemWidth = getItemWidth()
  const step = itemWidth + gap
  const max = getMaxTranslate()
  const isRtl = effectiveDirection.value === 'rtl'

  if (isRtl) {
    let next = currentTranslate.value + step
    if (next > max) {
      next = props.config.loop !== false ? 0 : max
    }
    currentTranslate.value = next
  } else {
    let next = currentTranslate.value - step
    if (next < -max) {
      next = props.config.loop !== false ? 0 : -max
    }
    currentTranslate.value = next
  }
}

function slidePrev() {
  const itemWidth = getItemWidth()
  const step = itemWidth + gap
  const max = getMaxTranslate()
  const isRtl = effectiveDirection.value === 'rtl'

  if (isRtl) {
    let next = currentTranslate.value - step
    if (next < 0) {
      next = props.config.loop !== false ? max : 0
    }
    currentTranslate.value = next
  } else {
    let next = currentTranslate.value + step
    if (next > 0) {
      next = props.config.loop !== false ? -max : 0
    }
    currentTranslate.value = next
  }
}

function startAutoplay() {
  stopAutoplay()
  if (!isSlider.value || props.config.autoplay === false) return
  const delay = props.config.autoplay_delay || 4000
  autoplayTimer = setInterval(slideNext, delay)
}

function stopAutoplay() {
  if (autoplayTimer) { clearInterval(autoplayTimer); autoplayTimer = null }
}

function pauseAutoplay() { stopAutoplay() }
function resumeAutoplay() { startAutoplay() }

// Mouse drag
function onDragStart(e: MouseEvent) {
  isDragging.value = true
  dragStartX = e.clientX
  dragStartTranslate = currentTranslate.value
  stopAutoplay()
}
function onDragMove(e: MouseEvent) {
  if (!isDragging.value) return
  currentTranslate.value = dragStartTranslate + (e.clientX - dragStartX)
}
function onDragEnd() {
  if (!isDragging.value) return
  isDragging.value = false
  snapToNearest()
  startAutoplay()
}
function onMouseLeave() {
  onDragEnd()
  resumeAutoplay()
}

// Touch drag
function onTouchStart(e: TouchEvent) {
  isDragging.value = true
  dragStartX = e.touches[0].clientX
  dragStartTranslate = currentTranslate.value
  stopAutoplay()
}
function onTouchMove(e: TouchEvent) {
  if (!isDragging.value) return
  currentTranslate.value = dragStartTranslate + (e.touches[0].clientX - dragStartX)
}
function onTouchEnd() {
  if (!isDragging.value) return
  isDragging.value = false
  snapToNearest()
  startAutoplay()
}

function snapToNearest() {
  const itemWidth = getItemWidth()
  const step = itemWidth + gap
  const max = getMaxTranslate()
  let idx = Math.round(Math.abs(currentTranslate.value) / step)
  idx = Math.max(0, Math.min(idx, props.products.length - 1))
  let snapped = -(idx * step)
  if (effectiveDirection.value === 'rtl') snapped = idx * step
  if (effectiveDirection.value === 'rtl') {
    if (snapped > max) snapped = max
    if (snapped < 0) snapped = 0
  } else {
    if (snapped < -max) snapped = -max
    if (snapped > 0) snapped = 0
  }
  currentTranslate.value = snapped
}

function handleResize() {
  responsiveSlidesPerView.value = calcResponsivePerView()
  snapToNearest()
}

onMounted(() => {
  if (isSlider.value) {
    responsiveSlidesPerView.value = calcResponsivePerView()
    startAutoplay()
    window.addEventListener('resize', handleResize)
  }
})

onBeforeUnmount(() => {
  stopAutoplay()
  window.removeEventListener('resize', handleResize)
})
</script>

<style scoped>
.products-section {
  padding: 2.5rem 0;
  position: relative;
}

/* Grid */
.products-grid {
  display: grid;
  gap: 1rem;
}
.products-grid__item {
  min-width: 0;
}

/* Slider */
.products-slider {
  position: relative;
  overflow: hidden;
  cursor: grab;
  user-select: none;
  touch-action: pan-y;
  -webkit-overflow-scrolling: touch;
}
.products-slider:active {
  cursor: grabbing;
}
.products-slider__track {
  display: flex;
  gap: 0.75rem;
  will-change: transform;
}
.products-slider__item {
  min-width: 160px;
  flex-shrink: 0;
}

/* Prevent link/image dragging */
.products-slider__track a,
.products-slider__track img {
  -webkit-user-drag: none;
  user-select: none;
  pointer-events: auto;
}
.products-slider:active .products-slider__track a {
  pointer-events: none;
}

/* ─── Arrow Buttons ─── */
.products-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(255,255,255,0.9);
  border: 1px solid #e5e7eb;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #374151;
  z-index: 3;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  transition: all 0.2s ease;
  opacity: 0;
}
.products-slider:hover .products-arrow {
  opacity: 1;
}
.products-arrow:hover {
  background: #fff;
  box-shadow: 0 4px 12px rgba(0,0,0,0.14);
}
.products-arrow--prev { left: 8px; }
.products-arrow--next { right: 8px; }

/* Arrow styles */
.products-arrow--rounded { border-radius: 50%; }
.products-arrow--square  { border-radius: 4px; }
.products-arrow--minimal {
  background: transparent;
  border: none;
  box-shadow: none;
  color: var(--store-text-primary, #111827);
}
.products-arrow--minimal:hover {
  background: rgba(0,0,0,0.05);
  box-shadow: none;
}

/* outside: push beyond edges */
.products-arrow--pos-outside.products-arrow--prev { left: -44px; }
.products-arrow--pos-outside.products-arrow--next { right: -44px; }

/* center-left: both stacked on left */
.products-arrow--pos-center-left { left: 8px; right: auto; }
.products-arrow--pos-center-left.products-arrow--prev { top: calc(50% - 22px); transform: none; }
.products-arrow--pos-center-left.products-arrow--next { top: calc(50% + 4px); transform: none; }

/* center-right: both stacked on right */
.products-arrow--pos-center-right { right: 8px; left: auto; }
.products-arrow--pos-center-right.products-arrow--prev { top: calc(50% - 22px); transform: none; }
.products-arrow--pos-center-right.products-arrow--next { top: calc(50% + 4px); transform: none; }

/* top-left: both at top-left, always visible */
.products-arrow--pos-top-left { top: -2.5rem; transform: none; opacity: 1; right: auto; }
.products-arrow--pos-top-left.products-arrow--prev { left: 0; }
.products-arrow--pos-top-left.products-arrow--next { left: 44px; }

/* top-right: both at top-right, always visible */
.products-arrow--pos-top-right { top: -2.5rem; transform: none; opacity: 1; left: auto; }
.products-arrow--pos-top-right.products-arrow--prev { right: 44px; }
.products-arrow--pos-top-right.products-arrow--next { right: 0; }

/* top-center: both at top-center, always visible */
.products-arrow--pos-top-center { top: -2.5rem; transform: none; opacity: 1; }
.products-arrow--pos-top-center.products-arrow--prev { left: calc(50% - 40px); right: auto; }
.products-arrow--pos-top-center.products-arrow--next { left: calc(50% + 4px); right: auto; }

/* bottom-left: both at bottom-left, always visible */
.products-arrow--pos-bottom-left { top: auto; bottom: -2.5rem; transform: none; opacity: 1; right: auto; }
.products-arrow--pos-bottom-left.products-arrow--prev { left: 0; }
.products-arrow--pos-bottom-left.products-arrow--next { left: 44px; }

/* bottom-right: both at bottom-right, always visible */
.products-arrow--pos-bottom-right { top: auto; bottom: -2.5rem; transform: none; opacity: 1; left: auto; }
.products-arrow--pos-bottom-right.products-arrow--prev { right: 44px; }
.products-arrow--pos-bottom-right.products-arrow--next { right: 0; }

/* bottom-center: both at bottom-center, always visible */
.products-arrow--pos-bottom-center { top: auto; bottom: -2.5rem; transform: none; opacity: 1; }
.products-arrow--pos-bottom-center.products-arrow--prev { left: calc(50% - 40px); right: auto; }
.products-arrow--pos-bottom-center.products-arrow--next { left: calc(50% + 4px); right: auto; }

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
