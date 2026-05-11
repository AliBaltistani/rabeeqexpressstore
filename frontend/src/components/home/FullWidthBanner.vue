<template>
  <section class="s-block full-banner">
    <div class="container">
      <router-link :to="link" class="full-banner__link" aria-label="Banner">
        <img
          v-if="desktopImage"
          :src="desktopImage"
          alt="Banner"
          class="full-banner__img full-banner__img--desktop"
          loading="lazy"
          width="1200"
          height="300"
        />
        <img
          v-if="mobileImage"
          :src="mobileImage"
          alt="Banner"
          class="full-banner__img full-banner__img--mobile"
          loading="lazy"
          width="600"
          height="300"
        />
      </router-link>
    </div>
  </section>
</template>

<script setup lang="ts">
defineProps<{
  desktopImage: string
  mobileImage?: string
  link: string
}>()
</script>

<style scoped>
.full-banner {
  padding: 0.25rem 0;
}
.full-banner__link {
  display: block;
  overflow: hidden;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.full-banner__link:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}
.full-banner__img {
  width: 100%;
  height: auto;
  display: block;
}
.full-banner__img--mobile {
  display: none;
}

@media (max-width: 639px) {
  .full-banner__img--desktop {
    display: none;
  }
  .full-banner__img--mobile {
    display: block;
  }
}
/* If no mobile image, always show desktop */
.full-banner__link:not(:has(.full-banner__img--mobile)) .full-banner__img--desktop {
  display: block !important;
}
</style>
