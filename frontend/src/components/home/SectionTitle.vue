<template>
  <div class="section-title" :style="{ textAlign: alignment }">
    <h2 class="section-title__heading">{{ title }}</h2>
    <p v-if="subtitle" class="section-title__subtitle">{{ subtitle }}</p>
    <div
      class="section-title__divider"
      :style="dividerMargin"
    ></div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  title: string
  subtitle?: string
  alignment?: 'left' | 'center' | 'right'
}>(), {
  alignment: 'center',
})

const dividerMargin = computed(() => {
  switch (props.alignment) {
    case 'left':  return { margin: '0.5rem auto 0.5rem 0' }
    case 'right': return { margin: '0.5rem 0 0.5rem auto' }
    default:      return { margin: '0.5rem auto' }
  }
})
</script>

<style scoped>
.section-title {
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
  width: 7rem;
  height: 0;
  border-top: 2px solid var(--color-primary, #858585);
}
</style>
