<template>
  <section class="s-block product-slider-section container">
    <SectionTitle :title="title" :subtitle="subtitle" />

    <!-- Slider with drag + auto-slide -->
    <div
      class="product-slider"
      ref="sliderRef"
      @mousedown="onDragStart"
      @mousemove="onDragMove"
      @mouseup="onDragEnd"
      @mouseleave="onDragEnd"
      @touchstart.passive="onTouchStart"
      @touchmove.passive="onTouchMove"
      @touchend="onTouchEnd"
    >
      <div
        class="product-slider__track"
        ref="trackRef"
        :style="{ transform: `translateX(${currentTranslate}px)`, transition: isDragging ? 'none' : 'transform 0.5s cubic-bezier(0.4, 0, 0.2, 1)' }"
      >
        <div
          v-for="product in products"
          :key="product.id"
          class="product-slider__item"
        >
          <ProductCard :product="product" />
        </div>
      </div>
    </div>

    <!-- View All button -->
    <div v-if="viewAllLink" class="product-slider__view-all">
      <span class="product-slider__line"></span>
      <router-link :to="viewAllLink" class="product-slider__view-all-btn">
        <span>View all</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
      </router-link>
      <span class="product-slider__line"></span>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue'
import SectionTitle from './SectionTitle.vue'
import ProductCard from './ProductCard.vue'

interface Product {
  id: number
  slug: string
  name: string
  subtitle?: string
  image: string
  price: number
  oldPrice?: number
  discount?: number
  currency?: string
}

const props = defineProps<{
  title: string
  subtitle?: string
  products: Product[]
  viewAllLink?: string
}>()

const sliderRef = ref<HTMLElement | null>(null)
const trackRef = ref<HTMLElement | null>(null)
const currentIndex = ref(0)
const currentTranslate = ref(0)
const isDragging = ref(false)

let autoplayTimer: ReturnType<typeof setInterval> | null = null
let dragStartX = 0
let dragStartTranslate = 0
let itemWidth = 0
let gap = 12 // 0.75rem

function getItemWidth() {
  if (!sliderRef.value) return 220
  const sliderW = sliderRef.value.clientWidth
  // 5 per row on desktop, 4 on tablet, 2 on mobile
  let perRow = 5
  if (sliderW < 480) perRow = 2
  else if (sliderW < 768) perRow = 2.5
  else if (sliderW < 1024) perRow = 3
  else if (sliderW < 1280) perRow = 4
  return (sliderW - gap * (perRow - 1)) / perRow
}

function getMaxTranslate() {
  if (!sliderRef.value) return 0
  const totalWidth = props.products.length * (itemWidth + gap) - gap
  const visibleWidth = sliderRef.value.clientWidth
  return Math.max(0, totalWidth - visibleWidth)
}

function recalc() {
  itemWidth = getItemWidth()
  clampTranslate()
}

function clampTranslate() {
  const max = getMaxTranslate()
  if (currentTranslate.value < -max) currentTranslate.value = -max
  if (currentTranslate.value > 0) currentTranslate.value = 0
}

function slideNext() {
  itemWidth = getItemWidth()
  const step = itemWidth + gap
  const max = getMaxTranslate()
  let next = currentTranslate.value - step
  if (next < -max) {
    // Loop back to start
    next = 0
  }
  currentTranslate.value = next
}

// Auto-slide
function startAutoplay() {
  stopAutoplay()
  autoplayTimer = setInterval(slideNext, 3500)
}

function stopAutoplay() {
  if (autoplayTimer) {
    clearInterval(autoplayTimer)
    autoplayTimer = null
  }
}

// Mouse drag
function onDragStart(e: MouseEvent) {
  isDragging.value = true
  dragStartX = e.clientX
  dragStartTranslate = currentTranslate.value
  stopAutoplay()
}

function onDragMove(e: MouseEvent) {
  if (!isDragging.value) return
  const diff = e.clientX - dragStartX
  currentTranslate.value = dragStartTranslate + diff
}

function onDragEnd() {
  if (!isDragging.value) return
  isDragging.value = false
  snapToNearest()
  startAutoplay()
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
  const diff = e.touches[0].clientX - dragStartX
  currentTranslate.value = dragStartTranslate + diff
}

function onTouchEnd() {
  if (!isDragging.value) return
  isDragging.value = false
  snapToNearest()
  startAutoplay()
}

function snapToNearest() {
  itemWidth = getItemWidth()
  const step = itemWidth + gap
  const max = getMaxTranslate()
  // Snap to nearest item boundary
  let idx = Math.round(-currentTranslate.value / step)
  idx = Math.max(0, Math.min(idx, props.products.length - 1))
  let snapped = -(idx * step)
  if (snapped < -max) snapped = -max
  if (snapped > 0) snapped = 0
  currentTranslate.value = snapped
}

onMounted(() => {
  recalc()
  startAutoplay()
  window.addEventListener('resize', recalc)
})

onBeforeUnmount(() => {
  stopAutoplay()
  window.removeEventListener('resize', recalc)
})
</script>

<style scoped>
.product-slider-section {
  padding: 1.5rem 0;
  overflow: hidden;
}
.product-slider {
  position: relative;
  overflow: hidden;
  cursor: grab;
  user-select: none;
}
.product-slider:active {
  cursor: grabbing;
}
.product-slider__track {
  display: flex;
  gap: 0.75rem;
  will-change: transform;
}
.product-slider__item {
  flex: 0 0 calc((100% - 3rem) / 5);
  min-width: 180px;
}
@media (max-width: 1279px) {
  .product-slider__item {
    flex: 0 0 calc((100% - 2.25rem) / 4);
  }
}
@media (max-width: 1023px) {
  .product-slider__item {
    flex: 0 0 calc((100% - 1.5rem) / 3);
  }
}
@media (max-width: 767px) {
  .product-slider__item {
    flex: 0 0 calc((100% - 0.75rem) / 2.5);
    min-width: 155px;
  }
}
@media (max-width: 479px) {
  .product-slider__item {
    flex: 0 0 calc((100% - 0.75rem) / 2);
  }
}

/* Prevent link/image dragging */
.product-slider__track a,
.product-slider__track img {
  -webkit-user-drag: none;
  user-select: none;
  pointer-events: auto;
}
.product-slider:active .product-slider__track a {
  pointer-events: none;
}

/* View all */
.product-slider__view-all {
  display: flex;
  align-items: center;
  gap: 2rem;
  margin-top: 1.25rem;
}
.product-slider__line {
  flex: 1;
  height: 1px;
  background: #e5e7eb;
}
.product-slider__view-all-btn {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  border: 1px solid var(--color-primary, #858585);
  color: var(--color-primary, #858585);
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.25s ease;
  flex-shrink: 0;
}
.product-slider__view-all-btn:hover {
  background: var(--color-primary, #858585);
  color: #fff;
}
.product-slider__view-all-btn:hover svg {
  transform: translateX(4px);
}
.product-slider__view-all-btn svg {
  transition: transform 0.3s ease;
}
</style>
