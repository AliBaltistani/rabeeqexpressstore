<template>
  <section class="s-block testimonials-section">
    <div class="container">
      <div class="testimonials-inner">
        <div class="home-block-title">
          <h2 class="testimonials-heading">{{ title }}</h2>
          <div class="testimonials-divider"></div>
        </div>
        <div class="testimonials-slider" ref="sliderRef">
          <button
            class="testimonials-arrow testimonials-arrow--prev"
            @click="scrollLeft"
            aria-label="Previous reviews"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
          </button>
          <div class="testimonials-track" ref="trackRef">
            <div
              v-for="(review, i) in reviews"
              :key="i"
              class="testimonial-item"
            >
              <div class="testimonial-card">
                <header class="testimonial-header">
                  <img
                    :src="review.avatar"
                    :alt="review.name"
                    class="testimonial-avatar"
                    loading="lazy"
                  />
                </header>
                <div class="testimonial-info">
                  <div class="testimonial-name">{{ review.name }}</div>
                  <div class="testimonial-rating">
                    <span
                      v-for="s in 5"
                      :key="s"
                      class="testimonial-star"
                      :class="{ 'testimonial-star--filled': s <= review.rating }"
                    >★</span>
                  </div>
                </div>
                <p class="testimonial-text">{{ review.text }}</p>
              </div>
            </div>
          </div>
          <button
            class="testimonials-arrow testimonials-arrow--next"
            @click="scrollRight"
            aria-label="Next reviews"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Review {
  name: string
  avatar: string
  rating: number
  text: string
}

defineProps<{
  title: string
  reviews: Review[]
}>()

const trackRef = ref<HTMLElement | null>(null)

function scrollLeft() {
  if (!trackRef.value) return
  trackRef.value.scrollBy({ left: -300, behavior: 'smooth' })
}
function scrollRight() {
  if (!trackRef.value) return
  trackRef.value.scrollBy({ left: 300, behavior: 'smooth' })
}
</script>

<style scoped>
.testimonials-section {
  padding: 2rem 0 2.5rem;
  overflow: hidden;
}
.testimonials-inner {
  position: relative;
}
.testimonials-heading {
  text-align: center;
  color: var(--store-text-primary, #111827);
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1.3;
}
@media (min-width: 1024px) {
  .testimonials-heading {
    font-size: 1.875rem;
  }
}
.testimonials-divider {
  margin: 0.5rem auto;
  width: 7rem;
  height: 0;
  border-top: 2px solid var(--color-primary, #858585);
}
.testimonials-slider {
  position: relative;
  margin-top: 1.5rem;
}
.testimonials-track {
  display: flex;
  gap: 0;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  scrollbar-width: none;
  -ms-overflow-style: none;
}
.testimonials-track::-webkit-scrollbar {
  display: none;
}
.testimonial-item {
  flex: 0 0 25%;
  min-width: 220px;
  scroll-snap-align: start;
}
@media (max-width: 1023px) {
  .testimonial-item { flex: 0 0 33.33%; }
}
@media (max-width: 767px) {
  .testimonial-item { flex: 0 0 50%; }
}
@media (max-width: 479px) {
  .testimonial-item { flex: 0 0 85%; }
}
.testimonial-card {
  padding: 0.75rem 1.25rem;
  height: 100%;
  text-align: center;
  background: var(--bg-primary, #fff);
}
.testimonial-header {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.625rem;
  min-height: 40px;
}
.testimonial-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  border: 2px solid var(--color-primary, #858585);
  object-fit: cover;
}
.testimonial-info {
  text-align: center;
}
.testimonial-name {
  font-weight: 700;
  color: var(--store-text-primary, #111827);
  font-size: 0.9rem;
}
.testimonial-rating {
  margin-top: 0.375rem;
  font-size: 0.875rem;
}
.testimonial-star {
  color: #d1d5db;
  margin: 0 1px;
}
.testimonial-star--filled {
  color: #f59e0b;
}
.testimonial-text {
  line-height: 1.5;
  font-size: 0.875rem;
  margin: 0.625rem 0 0.5rem;
  color: var(--store-text-primary, #111827);
  word-break: break-words;
}
.testimonials-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(255,255,255,0.9);
  border: 1px solid #e5e7eb;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #374151;
  z-index: 3;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  transition: all 0.2s ease;
}
.testimonials-arrow:hover {
  background: #fff;
  box-shadow: 0 4px 12px rgba(0,0,0,0.14);
}
.testimonials-arrow--prev { left: -6px; }
.testimonials-arrow--next { right: -6px; }
</style>
