<template>
  <div class="hero-slider-wrapper" @mouseenter="pauseAutoplay" @mouseleave="resumeAutoplay">
    <div class="hero-slider" ref="sliderEl">
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
      <button
        v-if="slides.length > 1"
        class="hero-slider__arrow hero-slider__arrow--prev"
        @click="prevSlide"
        aria-label="Previous slide"
      >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
      </button>
      <button
        v-if="slides.length > 1"
        class="hero-slider__arrow hero-slider__arrow--next"
        @click="nextSlide"
        aria-label="Next slide"
      >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
      <div v-if="slides.length > 1" class="hero-slider__dots">
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
import { ref, onMounted, onBeforeUnmount } from 'vue'

interface Slide {
  image: string
  link: string
  alt?: string
}

const props = defineProps<{
  slides: Slide[]
}>()

const currentSlide = ref(0)
let autoplayTimer: ReturnType<typeof setInterval> | null = null

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
  autoplayTimer = setInterval(nextSlide, 5000)
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
.hero-slider {
  position: relative;
  overflow: hidden;
  border-radius: 0.375rem;
}
.hero-slider__track {
  display: flex;
  transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
.hero-slider__slide {
  min-width: 100%;
  flex-shrink: 0;
}
.hero-slider__link { display: block; }
.hero-slider__image {
  width: 100%;
  height: auto;
  display: block;
  border-radius: 0.375rem;
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
.hero-slider__arrow--prev { left: 12px; }
.hero-slider__arrow--next { right: 12px; }
.hero-slider__dots {
  position: absolute;
  bottom: 12px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 6px;
  z-index: 2;
}
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
