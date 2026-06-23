<template>
  <Transition name="promo-bar-slide">
    <div
      v-if="isVisible"
      class="promo-bar"
      :class="[`promo-bar--${cfg.barHeight}`, `promo-bar--${cfg.mode}`, { 'promo-bar--mobile-hidden': !cfg.showOnMobile }]"
      :style="barStyle"
      role="banner"
    >
      <!-- Content area -->
      <component
        :is="cfg.linkUrl ? 'a' : 'div'"
        :href="cfg.linkUrl || undefined"
        :target="cfg.linkUrl ? cfg.linkTarget : undefined"
        class="promo-bar__content"
        :class="{ 'promo-bar__content--link': !!cfg.linkUrl }"
      >
        <!-- ── MARQUEE MODE ── -->
        <div
          v-if="cfg.mode === 'marquee'"
          class="promo-bar__marquee-wrap"
          :style="marqueeTrackStyle"
        >
          <!-- Copy 1 -->
          <ul class="promo-bar__marquee">
            <li
              v-for="(msg, i) in marqueeItems"
              :key="i"
              class="promo-bar__item"
              :style="itemStyle"
            >
              <span v-if="cfg.icon" class="promo-bar__icon" aria-hidden="true">{{ cfg.icon }}</span>
              <span>{{ msg }}</span>
              <span class="promo-bar__sep" aria-hidden="true">✦</span>
            </li>
          </ul>
          <!-- Copy 2: duplicate for seamless loop -->
          <ul class="promo-bar__marquee" aria-hidden="true">
            <li
              v-for="(msg, i) in marqueeItems"
              :key="'dup-' + i"
              class="promo-bar__item"
              :style="itemStyle"
            >
              <span v-if="cfg.icon" class="promo-bar__icon" aria-hidden="true">{{ cfg.icon }}</span>
              <span>{{ msg }}</span>
              <span class="promo-bar__sep" aria-hidden="true">✦</span>
            </li>
          </ul>
        </div>

        <!-- ── STATIC MODE ── -->
        <div v-else-if="cfg.mode === 'static'" class="promo-bar__static" :style="itemStyle">
          <span v-if="cfg.icon" class="promo-bar__icon" aria-hidden="true">{{ cfg.icon }}</span>
          <span>{{ activeMessage }}</span>
        </div>

        <!-- ── ROTATE MODE ── -->
        <div v-else-if="cfg.mode === 'rotate'" class="promo-bar__rotate">
          <Transition name="promo-rotate" mode="out-in">
            <span :key="rotateIndex" class="promo-bar__rotate-msg" :style="itemStyle">
              <span v-if="cfg.icon" class="promo-bar__icon" aria-hidden="true">{{ cfg.icon }}</span>
              {{ rotateMessages[rotateIndex] }}
            </span>
          </Transition>
        </div>
      </component>

      <!-- ── SEGMENTED COUNTDOWN (fixed, visible in all modes) ── -->
      <div
        v-if="cfg.showCountdown && countdownActive"
        class="promo-bar__countdown-wrap"
        aria-live="polite"
        aria-atomic="true"
      >
        <!-- Days — only shown when > 0 -->
        <template v-if="countdownParts.d > 0">
          <div class="promo-unit" :style="unitStyle">
            <span class="promo-unit__val">{{ String(countdownParts.d).padStart(2,'0') }}</span>
            <span class="promo-unit__lbl">DAY</span>
          </div>
          <span class="promo-unit__sep" :style="{ color: cfg.textColor }">:</span>
        </template>
        <!-- Hours -->
        <div class="promo-unit" :style="unitStyle">
          <span class="promo-unit__val">{{ String(countdownParts.h).padStart(2,'0') }}</span>
          <span class="promo-unit__lbl">HR</span>
        </div>
        <span class="promo-unit__sep" :style="{ color: cfg.textColor }">:</span>
        <!-- Minutes -->
        <div class="promo-unit" :style="unitStyle">
          <span class="promo-unit__val">{{ String(countdownParts.m).padStart(2,'0') }}</span>
          <span class="promo-unit__lbl">MIN</span>
        </div>
        <span class="promo-unit__sep" :style="{ color: cfg.textColor }">:</span>
        <!-- Seconds -->
        <div class="promo-unit" :style="unitStyle">
          <span class="promo-unit__val">{{ String(countdownParts.s).padStart(2,'0') }}</span>
          <span class="promo-unit__lbl">SEC</span>
        </div>
      </div>

      <!-- Dismiss button -->
      <button
        v-if="cfg.dismissible"
        class="promo-bar__close"
        :style="{ color: cfg.textColor }"
        @click.prevent.stop="dismiss"
        :aria-label="$t('common.close')"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useSettingsStore } from '@/stores/settingsStore'

const settings = useSettingsStore()
const cfg = computed(() => settings.storeSettings.promoBar)

// ── Visibility / Dismiss ────────────────────────────────────────────────────
const isHidden = ref(false)

const isVisible = computed(() => {
  if (!cfg.value.enabled) return false
  if (isHidden.value) return false
  // Countdown expired → hide
  if (cfg.value.showCountdown && cfg.value.countdownEnd) {
    if (Date.now() > new Date(cfg.value.countdownEnd).getTime()) return false
  }
  return true
})

onMounted(() => {
  const stored = localStorage.getItem('promoBarDismissed')
  if (stored) {
    const dismissedAt = parseInt(stored)
    const hours = cfg.value.dismissHours ?? 24
    const ms = hours * 3600_000
    if (Date.now() - dismissedAt < ms) {
      isHidden.value = true
    } else {
      localStorage.removeItem('promoBarDismissed')
    }
  }
})

function dismiss() {
  isHidden.value = true
  if ((cfg.value.dismissHours ?? 24) > 0) {
    localStorage.setItem('promoBarDismissed', Date.now().toString())
  }
}

// Re-check if admin removed old dismiss when config changes
watch(() => cfg.value.enabled, (v) => { if (v) isHidden.value = false })

// ── Messages ────────────────────────────────────────────────────────────────
const allMessages = computed((): string[] => {
  const items = cfg.value.items
  if (items && items.length > 0) return items
  return cfg.value.message ? [cfg.value.message] : []
})

const activeMessage = computed(() => allMessages.value[0] ?? '')
const marqueeItems  = computed(() => allMessages.value.length ? allMessages.value : [''])

// ── Rotate mode ─────────────────────────────────────────────────────────────
const rotateMessages = computed(() => allMessages.value)
const rotateIndex = ref(0)
let rotateTimer: ReturnType<typeof setInterval> | null = null

watch(
  () => cfg.value.mode,
  (mode) => {
    if (mode === 'rotate') startRotate()
    else stopRotate()
  },
  { immediate: true }
)

function startRotate() {
  stopRotate()
  if (rotateMessages.value.length > 1) {
    rotateTimer = setInterval(() => {
      rotateIndex.value = (rotateIndex.value + 1) % rotateMessages.value.length
    }, 4000)
  }
}

function stopRotate() {
  if (rotateTimer) { clearInterval(rotateTimer); rotateTimer = null }
}

onUnmounted(stopRotate)

// ── Countdown ────────────────────────────────────────────────────────────────
const countdownParts = ref({ d: 0, h: 0, m: 0, s: 0 })
const countdownActive = ref(false)   // true only when diff > 0
let countdownTimer: ReturnType<typeof setInterval> | null = null

function updateCountdown() {
  const end = cfg.value.countdownEnd
  if (!end) { countdownActive.value = false; return }
  const diff = new Date(end).getTime() - Date.now()
  if (diff <= 0) { countdownActive.value = false; return }
  countdownActive.value = true
  countdownParts.value = {
    d: Math.floor(diff / 86_400_000),
    h: Math.floor((diff % 86_400_000) / 3_600_000),
    m: Math.floor((diff % 3_600_000) / 60_000),
    s: Math.floor((diff % 60_000) / 1_000),
  }
}

function restartCountdownTimer() {
  if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null }
  if (cfg.value.showCountdown && cfg.value.countdownEnd) {
    updateCountdown()
    countdownTimer = setInterval(updateCountdown, 1000)
  } else {
    countdownActive.value = false
  }
}

// Watch BOTH showCountdown and countdownEnd — restart timer if either changes
watch(
  () => [cfg.value.showCountdown, cfg.value.countdownEnd] as const,
  () => restartCountdownTimer(),
  { immediate: true }
)
onUnmounted(() => { if (countdownTimer) clearInterval(countdownTimer) })

// ── Styles ───────────────────────────────────────────────────────────────────
const barStyle = computed(() => {
  const c = cfg.value
  const style: Record<string, string> = {
    color: c.textColor,
  }

  if (c.style === 'gradient') {
    style.background = `linear-gradient(to right, ${c.gradientFrom}, ${c.gradientTo})`
  } else if (c.style === 'outline') {
    style.background = 'transparent'
    style.border = `2px solid ${c.bgColor}`
    style.color = c.bgColor
  } else {
    style.background = c.bgColor
  }

  return style
})

const fontWeightMap: Record<string, string> = {
  normal: '400', medium: '500', semibold: '600', bold: '700',
}
const fontSizeMap: Record<string, string> = {
  xs: '0.6875rem', sm: '0.8125rem', md: '0.875rem', lg: '1rem',
}

const itemStyle = computed(() => ({
  fontSize: fontSizeMap[cfg.value.fontSize] ?? '0.8125rem',
  fontWeight: fontWeightMap[cfg.value.fontWeight] ?? '600',
  color: cfg.value.textColor,
}))

const marqueeSpeedMap: Record<string, string> = {
  slowest: '120s', slow: '60s', medium: '40s', fast: '25s', fastest: '10s', top: '5s', superfast: '2s',
}

// Inject speed as a CSS custom property on the track wrapper
// so the animation duration reacts instantly to admin changes
const marqueeTrackStyle = computed(() => ({
  '--marquee-dur': marqueeSpeedMap[cfg.value.marqueeSpeed] ?? '40s',
}))

// Unit cell style for segmented countdown — frosted pill using the bar's text colour
const unitStyle = computed(() => ({
  color: cfg.value.textColor,
  borderColor: `${cfg.value.textColor}40`,  // 25% opacity border
  background: `${cfg.value.textColor}1a`,   // 10% opacity frosted fill
}))
</script>

<style scoped>
/* ── Bar shell ── */
.promo-bar {
  width: 100%;
  overflow: hidden;
  position: relative;
  z-index: 100;
  display: flex;
  align-items: stretch;
}

/* Heights */
.promo-bar--compact { min-height: 28px; }
.promo-bar--normal  { min-height: 36px; }
.promo-bar--tall    { min-height: 44px; }

/* Mobile toggle */
@media (max-width: 767px) {
  .promo-bar--mobile-hidden { display: none; }
}

/* ── Content wrapper ── */
.promo-bar__content {
  flex: 1;
  display: flex;
  align-items: center;
  overflow: hidden;
  text-decoration: none;
  min-width: 0;
}
.promo-bar__content--link:hover { opacity: 0.88; }

/* ── MARQUEE ──
   The WRAPPER is the only animated element.
   It contains two identical <ul> copies side by side (total = 2× content width).
   Translating the wrapper by -50% brings it back to the exact start → seamless loop.
   Speed is driven by CSS custom property --marquee-dur (set via inline style).
── */
.promo-bar__marquee-wrap {
  display: flex;          /* puts both ul copies side by side */
  width: max-content;     /* expands to fit both lists */
  flex-shrink: 0;
  /* Animation on the WRAPPER only */
  animation-name: promo-marquee;
  animation-duration: var(--marquee-dur, 40s);
  animation-timing-function: linear;
  animation-iteration-count: infinite;
}
html[dir="rtl"] .promo-bar__marquee-wrap {
  animation-name: promo-marquee-rtl;
}

/* ul copies: static, no animation */
.promo-bar__marquee {
  display: flex;
  list-style: none;
  margin: 0;
  padding: 0;
  flex-shrink: 0;
  /* NO animation here — wrapper animates instead */
}

.promo-bar__item {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  white-space: nowrap;
  padding: 0.4375rem 1.5rem;
}
.promo-bar__sep {
  margin-inline-start: 1.5rem;
  opacity: 0.5;
  font-size: 0.6rem;
}

/* ── STATIC ── */
.promo-bar__static {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  padding: 0.375rem 2.5rem;
  text-align: center;
}

/* ── ROTATE ── */
.promo-bar__rotate {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.375rem 2.5rem;
  min-height: inherit;
}
.promo-bar__rotate-msg {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  white-space: nowrap;
}

/* ── Icon ── */
.promo-bar__icon {
  font-style: normal;
  line-height: 1;
}

/* ── Countdown \u2500 old inline span (kept for safety, hidden by design) ── */
.promo-bar__countdown { display: none; }

/* ── Segmented Countdown Wrapper ── */
.promo-bar__countdown-wrap {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  flex-shrink: 0;
  padding: 0 0.875rem;
  border-inline-start: 1px solid rgba(255, 255, 255, 0.2);
  align-self: stretch;
}

/* Individual unit cell */
.promo-unit {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-width: 2rem;
  padding: 0.15rem 0.35rem;
  border-radius: 4px;
  border: 1px solid;   /* colour set via unitStyle computed */
  line-height: 1;
  gap: 0.1rem;
}

/* Large bold number */
.promo-unit__val {
  font-size: 0.875rem;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.04em;
  line-height: 1;
}

/* Tiny uppercase label below number */
.promo-unit__lbl {
  font-size: 0.45rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  opacity: 0.7;
  line-height: 1;
}

/* Colon separator */
.promo-unit__sep {
  font-size: 0.8rem;
  font-weight: 800;
  opacity: 0.6;
  margin-bottom: 0.5rem;   /* nudge up to align with number row */
  animation: promo-blink 1s step-start infinite;
  flex-shrink: 0;
}

@keyframes promo-blink {
  0%, 100% { opacity: 0.6; }
  50%       { opacity: 0.15; }
}

/* Compact bar: make units slightly smaller */
.promo-bar--compact .promo-unit__val { font-size: 0.75rem; }
.promo-bar--compact .promo-unit      { min-width: 1.6rem; padding: 0.1rem 0.25rem; }
.promo-bar--compact .promo-unit__lbl { display: none; }

/* Tall bar: can afford a touch more size */
.promo-bar--tall .promo-unit__val { font-size: 1rem; }
.promo-bar--tall .promo-unit      { min-width: 2.2rem; }



/* ── Dismiss button ── */
.promo-bar__close {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0 0.625rem;
  opacity: 0.75;
  transition: opacity 0.15s ease;
  z-index: 2;
  align-self: stretch;
}
.promo-bar__close:hover { opacity: 1; }

/* ── Keyframes ── */
@keyframes promo-marquee {
  0%   { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}
@keyframes promo-marquee-rtl {
  0%   { transform: translateX(0); }
  100% { transform: translateX(50%); }
}

/* ── Rotate transition ── */
.promo-rotate-enter-active,
.promo-rotate-leave-active {
  transition: opacity 0.4s ease, transform 0.4s ease;
}
.promo-rotate-enter-from {
  opacity: 0;
  transform: translateY(8px);
}
.promo-rotate-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* ── Bar entry transition ── */
.promo-bar-slide-enter-active,
.promo-bar-slide-leave-active {
  transition: max-height 0.3s ease, opacity 0.3s ease;
  max-height: 60px;
  overflow: hidden;
}
.promo-bar-slide-enter-from,
.promo-bar-slide-leave-to {
  max-height: 0;
  opacity: 0;
}
</style>
