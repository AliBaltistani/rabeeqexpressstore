<template>
  <section class="s-block double-banner">
    <div class="container">
      <div class="double-banner__row">
        <router-link
          v-for="(banner, i) in banners"
          :key="i"
          :to="banner.link"
          class="double-banner__item"
          aria-label="Banner"
        >
          <img
            :src="banner.image"
            alt="Banner"
            class="double-banner__image"
            loading="lazy"
            width="1200"
            height="300"
          />
        </router-link>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
interface BannerItem {
  image: string
  link: string
}

defineProps<{
  banners: BannerItem[]
}>()
</script>

<style scoped>
.double-banner {
  padding: 0.25rem 0;
}
.double-banner__row {
  display: flex;
  gap: 0.5rem;
}
@media (min-width: 768px) {
  .double-banner__row {
    gap: 1rem;
  }
}
.double-banner__item {
  flex: 1 1 50%;
  overflow: hidden;
  display: block;
  border-radius: 0.25rem;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.double-banner__item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}
.double-banner__image {
  width: 100%;
  height: auto;
  object-fit: cover;
  display: block;
}
</style>
