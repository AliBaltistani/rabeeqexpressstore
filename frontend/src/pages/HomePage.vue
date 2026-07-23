<template>
  <div class="home-page">
    <template v-for="section in sections" :key="section.id">

      <!-- Hero Slider -->
      <section
        v-if="section.type === 'hero_slider' && section.data?.length"
        class="home-section"
        :class="sectionWidthClass(section)"
        :id="`section-${section.id}`"
      >
        <HeroSlider :slides="mapHeroSlides(section.data)" :config="section.config" />
        <SectionCustomStyles :sectionId="section.id" :css="section.config?.custom_css" :js="section.config?.custom_js" />
      </section>

      <!-- Banner Grid -->
      <section
        v-else-if="section.type === 'banner' && section.data?.length"
        class="home-section"
        :class="sectionWidthClass(section)"
        :id="`section-${section.id}`"
      >
        <BannerGrid :banners="section.data" :config="section.config || {}" />
        <SectionCustomStyles :sectionId="section.id" :css="section.config?.custom_css" :js="section.config?.custom_js" />
      </section>

      <!-- Products (grid or slider) -->
      <section
        v-else-if="section.type === 'products' && section.data?.length"
        class="home-section"
        :class="sectionWidthClass(section)"
        :id="`section-${section.id}`"
      >
        <ProductsSection :products="section.data" :config="section.config || {}" />
        <SectionCustomStyles :sectionId="section.id" :css="section.config?.custom_css" :js="section.config?.custom_js" />
      </section>

      <!-- Customer Reviews (slider) -->
      <section
        v-else-if="section.type === 'reviews' && section.data?.length"
        class="home-section"
        :class="sectionWidthClass(section)"
        :id="`section-${section.id}`"
      >
        <TestimonialsSlider
          :title="getLocalizedTitle(section.config)"
          :reviews="section.data"
          :config="section.config || {}"
        />
        <SectionCustomStyles :sectionId="section.id" :css="section.config?.custom_css" :js="section.config?.custom_js" />
      </section>

      <!-- Custom HTML -->
      <section
        v-else-if="section.type === 'custom_html'"
        class="home-section"
        :class="sectionWidthClass(section)"
        :id="`section-${section.id}`"
      >
        <div
          v-if="(section.config?.title_en || section.config?.title_ar) && section.config?.show_title !== false"
        >
          <h2
            class="custom-html__title"
            :style="{ textAlign: (section.config?.title_alignment || 'center') as any }"
          >
            {{ $i18n.locale === 'ar' ? (section.config.title_ar || section.config.title_en) : section.config.title_en }}
          </h2>
        </div>
        <div class="custom-html__content" v-html="section.config?.content || ''"></div>
        <SectionCustomStyles :sectionId="section.id" :css="section.config?.custom_css" :js="section.config?.custom_js" />
      </section>

    </template>

    <!-- Loading state -->
    <div v-if="loading" class="home-loading">
      <p>{{ $t('common.loading') }}...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, defineComponent, h, onBeforeUnmount, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import HeroSlider from '@/components/home/HeroSlider.vue'
import BannerGrid from '@/components/home/BannerGrid.vue'
import ProductsSection from '@/components/home/ProductsSection.vue'
import TestimonialsSlider from '@/components/home/TestimonialsSlider.vue'
import { fetchHomeSections } from '@/api/services'
import { useSettingsStore } from '@/stores/settingsStore'

const { locale } = useI18n()
const settings = useSettingsStore()

// ─── State ───
const sections = ref<any[]>([])
const loading = ref(true)

/**
 * Map hero slider API data to the shape HeroSlider expects.
 */
function mapHeroSlides(sliders: any[]) {
  return (sliders || []).map((s: any) => ({
    image: s.image || '',
    link: s.link_url || '/',
    alt: s.title || 'Slide',
  }))
}

/**
 * Get localized title from config based on current locale.
 */
function getLocalizedTitle(config: any): string {
  if (!config) return ''
  return locale.value === 'ar'
    ? (config.title_ar || config.title_en || '')
    : (config.title_en || '')
}

/**
 * Return CSS class based on section_width config.
 * 'contained' → applies the .container class (max-width centered)
 * 'full-width' or unset → no extra class (stretches edge-to-edge)
 */
function sectionWidthClass(section: any): Record<string, boolean> {
  const width = section.config?.section_width || section.config?.width || 'contained'
  return {
    'container': width === 'contained',
    'section--full-width': width === 'full-width',
  }
}

onMounted(async () => {
  try {
    const data = await fetchHomeSections()
    sections.value = data || []
  } catch (e) {
    console.error('Failed to fetch homepage sections:', e)
  } finally {
    loading.value = false
  }
})

// Re-fetch when currency changes so product prices update immediately
watch(() => settings.currentCurrencyCode, async () => {
  try {
    loading.value = true
    const data = await fetchHomeSections()
    sections.value = data || []
  } catch (e) {
    console.error('Failed to reload homepage sections on currency change:', e)
  } finally {
    loading.value = false
  }
})

/**
 * SectionCustomStyles — inline component that injects scoped custom CSS and JS per section.
 */
const SectionCustomStyles = defineComponent({
  name: 'SectionCustomStyles',
  props: {
    sectionId: { type: [Number, String], required: true },
    css: { type: String, default: '' },
    js: { type: String, default: '' },
  },
  setup(props) {
    let styleEl: HTMLStyleElement | null = null
    let scriptExecuted = false

    const injectCSS = () => {
      // Cleanup previous style
      if (styleEl) {
        styleEl.remove()
        styleEl = null
      }
      if (props.css) {
        styleEl = document.createElement('style')
        styleEl.setAttribute('data-section', String(props.sectionId))
        // Scope CSS to the section wrapper via #section-{id}
        const scopedCss = props.css
          .split('}')
          .filter(rule => rule.trim())
          .map(rule => {
            const trimmed = rule.trim()
            // If rule already references the section ID, don't double-scope
            if (trimmed.includes(`#section-${props.sectionId}`)) return rule + '}'
            // Find the opening brace
            const braceIdx = trimmed.indexOf('{')
            if (braceIdx === -1) return ''
            const selector = trimmed.substring(0, braceIdx).trim()
            const body = trimmed.substring(braceIdx)
            return `#section-${props.sectionId} ${selector} ${body}}`
          })
          .join('\n')
        styleEl.textContent = scopedCss
        document.head.appendChild(styleEl)
      }
    }

    const executeJS = () => {
      if (props.js && !scriptExecuted) {
        scriptExecuted = true
        try {
          // Execute in a function scope with section ID available
          const fn = new Function('sectionId', 'sectionEl', props.js)
          const el = document.getElementById(`section-${props.sectionId}`)
          fn(props.sectionId, el)
        } catch (err) {
          console.error(`[Section ${props.sectionId}] Custom JS error:`, err)
        }
      }
    }

    onMounted(() => {
      injectCSS()
      executeJS()
    })

    watch(() => props.css, () => injectCSS())

    onBeforeUnmount(() => {
      if (styleEl) {
        styleEl.remove()
        styleEl = null
      }
    })

    return () => h('span', { style: 'display:none' })
  },
})
</script>

<style scoped>
.home-page {
  background: var(--bg-primary, #fff);
}

.home-section {
  margin-bottom: 0.25rem;
}
.section--full-width {
  width: 100%;
  max-width: 100%;
  padding-left: 0;
  padding-right: 0;
}

/* Custom HTML */
.custom-html__title {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1rem;
  color: var(--color-text, #111827);
}
.custom-html__content {
  line-height: 1.6;
}

/* Loading */
.home-loading {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: var(--space-3xl, 4rem);
  color: var(--footer-text-color, #374151);
  font-size: 1rem;
}
</style>
