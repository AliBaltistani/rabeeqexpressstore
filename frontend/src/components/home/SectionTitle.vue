<template>
  <div class="section-title">
    <h2 class="section-title__heading">{{ title }}</h2>
    <p v-if="subtitle" class="section-title__subtitle">{{ subtitle }}</p>
    <div class="section-title__divider"></div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title: string
  subtitle?: string
}>()
</script>

<style scoped>
.section-title {
  text-align: center;
  margin-bottom: 2rem;
}
.section-title__heading {
  font-size: 1.875rem;
  font-weight: 700;
  letter-spacing: -0.025em;
  color: var(--store-text-primary, #111827);
}
@media (min-width: 640px) {
  .section-title__heading {
    font-size: 2.25rem;
  }
}
.section-title__subtitle {
  margin-top: 0.5rem;
  font-size: 1.125rem;
  line-height: 2rem;
  color: var(--store-text-secondary, #000);
}
.section-title__divider {
  margin: 0.5rem auto;
  width: 7rem;
  height: 0;
  border-top: 2px solid var(--color-primary, #858585);
}
</style>
