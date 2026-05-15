<template>
  <div class="hero-slider-wrapper" :class="{ 'hero-slider--contained': sliderConfig.width === 'contained' }" @mouseenter="pauseAutoplay" @mouseleave="resumeAutoplay">
    <div class="hero-slider" ref="sliderEl" :style="heightStyle">
      <div class="hero-slider__track" :style="{ transform: `translateX(-${currentSlide * 100}%)` }">
        <div v-for="(slide, i) in slides" :key="i" class="hero-slider__slide">
          <router-link :to="slide.link" class="hero-slider__link">
            <img
              :src="slide.image"
              :alt="slide.alt || 'Banner'"
              class="hero-slider__image"
              :loading="i === 0 ? 'eager' : 'lazy'"
              :fetchpriority="i === 0 ? 'high' : 'auto'"
              width="1200"
              height="300"
            />
          </router-link>
        </div>
      </div>

      <!-- Arrow navigation -->
      <template v-if="showArrows && slides.length > 1">
        <button
          class="hero-slider__arrow hero-slider__arrow--prev"
          :class="arrowClasses"
          @click="prevSlide"
          aria-label="Previous slide"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <button
          class="hero-slider__arrow hero-slider__arrow--next"
          :class="arrowClasses"
          @click="nextSlide"
          aria-label="Next slide"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </template>

      <!-- Dot indicators -->
      <div
        v-if="showDots && slides.length > 1"
        class="hero-slider__dots"
        :class="indicatorPositionClass"
      >
        <button
          v-for="(_, i) in slides"
          :key="i"
          class="hero-slider__dot"
          :class="{ 'hero-slider__dot--active': currentSlide === i }"
          @click="goToSlide(i)"
          :aria-label="'Go to slide ' + (i + 1)"
        ></button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

interface Slide {
  image: string
  link: string
  alt?: string
}

interface SliderConfig {
  show_indicators?: boolean
  indicator_position?: string
  navigation_style?: string
  navigation_position?: string
  height?: number
  width?: string
  autoplay?: boolean
  autoplay_delay?: number
  // Common config
  show_arrows?: boolean
  arrows_style?: string
  arrows_position?: string
}

const props = withDefaults(defineProps<{
  slides: Slide[]
  config?: SliderConfig
}>(), {
  config: () => ({}),
})

const sliderConfig = computed<SliderConfig>(() => props.config || {})

const currentSlide = ref(0)
let autoplayTimer: ReturnType<typeof setInterval> | null = null

const showArrows = computed(() => {
  if (sliderConfig.value.show_arrows === false) return false
  const style = sliderConfig.value.navigation_style || 'arrows'
  return style === 'arrows' || style === 'both'
})

const arrowClasses = computed(() => {
  const style = sliderConfig.value.arrows_style || 'rounded'
  const navPos = sliderConfig.value.arrows_position || sliderConfig.value.navigation_position || 'inside'
  return {
    [`hero-slider__arrow--${navPos}`]: navPos !== 'inside',
    [`hero-slider__arrow--${style}`]: true,
  }
})

const showDots = computed(() => {
  const style = sliderConfig.value.navigation_style || 'arrows'
  const indicators = sliderConfig.value.show_indicators !== false
  return indicators || style === 'dots' || style === 'both'
})

const indicatorPositionClass = computed(() => {
  return sliderConfig.value.indicator_position === 'top'
    ? 'hero-slider__dots--top'
    : 'hero-slider__dots--bottom'
})

const heightStyle = computed(() => {
  const h = sliderConfig.value.height
  return h ? { maxHeight: `${h}px` } : {}
})

function nextSlide() {
  currentSlide.value = (currentSlide.value + 1) % props.slides.length
}
function prevSlide() {
  currentSlide.value = (currentSlide.value - 1 + props.slides.length) % props.slides.length
}
function goToSlide(i: number) {
  currentSlide.value = i
}

function startAutoplay() {
  if (sliderConfig.value.autoplay === false) return
  const delay = sliderConfig.value.autoplay_delay || 5000
  autoplayTimer = setInterval(nextSlide, delay)
}
function pauseAutoplay() {
  if (autoplayTimer) { clearInterval(autoplayTimer); autoplayTimer = null }
}
function resumeAutoplay() {
  pauseAutoplay()
  startAutoplay()
}

onMounted(() => startAutoplay())
onBeforeUnmount(() => pauseAutoplay())
</script>

<style scoped>
.hero-slider-wrapper {
  padding: 0.75rem 0;
}
.hero-slider--contained {
  max-width: 1280px;
  margin: 0 auto;
  padding-left: 1rem;
  padding-right: 1rem;
}
.hero-slider {
  position: relative;
  overflow: hidden;
  border-radius: 0.375rem;
  width: 100%;
}
.hero-slider__track {
  display: flex;
  width: 100%;
  transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
.hero-slider__slide {
  width: 100%;
  min-width: 100%;
  flex-shrink: 0;
}
.hero-slider__link {
  display: block;
  width: 100%;
}
.hero-slider__image {
  width: 100%;
  height: auto;
  max-height: 600px;
  object-fit: cover;
  display: block;
}
.hero-slider__arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(255,255,255,0.85);
  border: none;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #111827;
  z-index: 2;
  box-shadow: 0 2px 8px rgba(0,0,0,0.12);
  transition: all 0.2s ease;
  opacity: 0;
}
.hero-slider:hover .hero-slider__arrow { opacity: 1; }
.hero-slider__arrow:hover {
  background: #fff;
  box-shadow: 0 4px 12px rgba(0,0,0,0.18);
}
/* Default inside: prev left, next right */
.hero-slider__arrow--prev { left: 12px; }
.hero-slider__arrow--next { right: 12px; }

/* Arrow styles */
.hero-slider__arrow--rounded { border-radius: 50%; }
.hero-slider__arrow--square  { border-radius: 4px; }
.hero-slider__arrow--minimal {
  background: transparent;
  border: none;
  box-shadow: none;
  color: #fff;
}
.hero-slider__arrow--minimal:hover {
  background: rgba(0,0,0,0.2);
  box-shadow: none;
}

/* outside: push arrows outside slider edges */
.hero-slider__arrow--outside.hero-slider__arrow--prev { left: -44px; }
.hero-slider__arrow--outside.hero-slider__arrow--next { right: -44px; }

/* center-left: both stacked on left */
.hero-slider__arrow--center-left { left: 12px; right: auto; }
.hero-slider__arrow--center-left.hero-slider__arrow--prev { top: calc(50% - 22px); transform: none; }
.hero-slider__arrow--center-left.hero-slider__arrow--next { top: calc(50% + 4px); transform: none; }

/* center-right: both stacked on right */
.hero-slider__arrow--center-right { right: 12px; left: auto; }
.hero-slider__arrow--center-right.hero-slider__arrow--prev { top: calc(50% - 22px); transform: none; }
.hero-slider__arrow--center-right.hero-slider__arrow--next { top: calc(50% + 4px); transform: none; }

/* top-left: both at top-left */
.hero-slider__arrow--top-left { top: 12px; transform: none; opacity: 1; right: auto; }
.hero-slider__arrow--top-left.hero-slider__arrow--prev { left: 12px; }
.hero-slider__arrow--top-left.hero-slider__arrow--next { left: 56px; }

/* top-right: both at top-right */
.hero-slider__arrow--top-right { top: 12px; transform: none; opacity: 1; left: auto; }
.hero-slider__arrow--top-right.hero-slider__arrow--prev { right: 56px; }
.hero-slider__arrow--top-right.hero-slider__arrow--next { right: 12px; }

/* top-center: both at top-center */
.hero-slider__arrow--top-center { top: 12px; transform: none; opacity: 1; }
.hero-slider__arrow--top-center.hero-slider__arrow--prev { left: calc(50% - 40px); right: auto; }
.hero-slider__arrow--top-center.hero-slider__arrow--next { left: calc(50% + 4px); right: auto; }

/* bottom-left: both at bottom-left */
.hero-slider__arrow--bottom-left { top: auto; bottom: 12px; transform: none; opacity: 1; right: auto; }
.hero-slider__arrow--bottom-left.hero-slider__arrow--prev { left: 12px; }
.hero-slider__arrow--bottom-left.hero-slider__arrow--next { left: 56px; }

/* bottom-right: both at bottom-right */
.hero-slider__arrow--bottom-right { top: auto; bottom: 12px; transform: none; opacity: 1; left: auto; }
.hero-slider__arrow--bottom-right.hero-slider__arrow--prev { right: 56px; }
.hero-slider__arrow--bottom-right.hero-slider__arrow--next { right: 12px; }

/* bottom-center: both at bottom-center */
.hero-slider__arrow--bottom-center { top: auto; bottom: 12px; transform: none; opacity: 1; }
.hero-slider__arrow--bottom-center.hero-slider__arrow--prev { left: calc(50% - 40px); right: auto; }
.hero-slider__arrow--bottom-center.hero-slider__arrow--next { left: calc(50% + 4px); right: auto; }

.hero-slider__dots {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 6px;
  z-index: 2;
}
.hero-slider__dots--bottom { bottom: 12px; }
.hero-slider__dots--top { top: 12px; }

.hero-slider__dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  border: none;
  background: rgba(255,255,255,0.5);
  cursor: pointer;
  padding: 0;
  transition: all 0.2s ease;
}
.hero-slider__dot--active {
  background: #fff;
  width: 24px;
  border-radius: 4px;
}
</style>
