<template>
  <div class="phone-input-wrap" :class="{ 'phone-input-wrap--loading': loading }">
    <!-- Country Code Selector -->
    <div class="phone-cc-selector" ref="selectorRef">
      <button
        type="button"
        class="phone-cc-btn"
        :disabled="disabled"
        @click="toggleDropdown"
        :aria-label="'Country code: ' + selected?.phone_code"
      >
        <!-- Flag -->
        <span v-if="selected?.flag_url" class="phone-cc-flag">
          <img :src="selected.flag_url" :alt="selected.code" class="phone-cc-flag-img" />
        </span>
        <span v-else class="phone-cc-emoji">{{ flagEmoji(selected?.code) }}</span>
        <!-- Code -->
        <span class="phone-cc-code">{{ selected?.phone_code || '+?' }}</span>
        <svg class="phone-cc-caret" viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
      </button>

      <!-- Dropdown -->
      <Transition name="cc-drop">
        <div v-if="open" class="phone-cc-dropdown">
          <div class="phone-cc-search-wrap">
            <input
              ref="searchRef"
              v-model="search"
              type="text"
              class="phone-cc-search"
              placeholder="Search..."
              @click.stop
            />
          </div>
          <div class="phone-cc-list">
            <button
              v-for="c in filteredCountries"
              :key="c.code"
              type="button"
              class="phone-cc-option"
              :class="{ active: c.code === selected?.code }"
              @click="selectCountry(c)"
            >
              <span v-if="c.flag_url" class="phone-cc-flag">
                <img :src="c.flag_url" :alt="c.code" class="phone-cc-flag-img" />
              </span>
              <span v-else class="phone-cc-emoji">{{ flagEmoji(c.code) }}</span>
              <span class="phone-cc-option-name">{{ c.name }}</span>
              <span class="phone-cc-option-code">{{ c.phone_code }}</span>
            </button>
            <div v-if="filteredCountries.length === 0" class="phone-cc-empty">No results</div>
          </div>
        </div>
      </Transition>
    </div>

    <!-- Phone number input -->
    <input
      :value="modelValue"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      type="tel"
      class="phone-cc-number"
      :class="{ 'input-error': error }"
      :placeholder="placeholder"
      :disabled="disabled"
      :autocomplete="autocomplete"
      @keydown.enter.prevent="$emit('enter')"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { fetchActiveCountries } from '@/api/services'

// ─── Props ────────────────────────────────────────────────────────────────────
const props = withDefaults(defineProps<{
  modelValue: string       // phone number (digits only, without country code)
  countryCode: string      // e.g. '+966'
  placeholder?: string
  disabled?: boolean
  error?: boolean
  autocomplete?: string
}>(), {
  placeholder: '501234567',
  disabled: false,
  error: false,
  autocomplete: 'tel-national',
})

const emit = defineEmits<{
  (e: 'update:modelValue', val: string): void
  (e: 'update:countryCode', val: string): void
  (e: 'enter'): void
}>()

// ─── State ────────────────────────────────────────────────────────────────────
const countries = ref<any[]>([])
const loading = ref(true)
const open = ref(false)
const search = ref('')
const selectorRef = ref<HTMLElement | null>(null)
const searchRef = ref<HTMLInputElement | null>(null)

const selected = computed(() =>
  countries.value.find(c => c.phone_code === props.countryCode) || countries.value[0]
)

const filteredCountries = computed(() => {
  if (!search.value.trim()) return countries.value
  const q = search.value.toLowerCase()
  return countries.value.filter(
    c => c.name?.toLowerCase().includes(q) || c.phone_code?.includes(q) || c.code?.toLowerCase().includes(q)
  )
})

// ─── Helpers ──────────────────────────────────────────────────────────────────
/** Render a flag emoji from a 2-letter ISO code (fallback when no image). */
function flagEmoji(code?: string): string {
  if (!code || code.length !== 2) return '🌐'
  return [...code.toUpperCase()].map(c => String.fromCodePoint(0x1F1E6 + c.charCodeAt(0) - 65)).join('')
}

function toggleDropdown() {
  open.value = !open.value
  if (open.value) {
    search.value = ''
    nextTick(() => searchRef.value?.focus())
  }
}

function selectCountry(c: any) {
  emit('update:countryCode', c.phone_code)
  open.value = false
  search.value = ''
}

// Close on outside click
function onClickOutside(e: MouseEvent) {
  if (selectorRef.value && !selectorRef.value.contains(e.target as Node)) {
    open.value = false
  }
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────
onMounted(async () => {
  document.addEventListener('mousedown', onClickOutside)
  try {
    const result = await fetchActiveCountries()
    countries.value = result || []
    // Set default to SA if not already set and countries loaded
    if (!props.countryCode && countries.value.length) {
      const sa = countries.value.find(c => c.code === 'SA') || countries.value[0]
      emit('update:countryCode', sa.phone_code)
    }
  } catch {
    // fallback: inline defaults
    countries.value = [
      { code: 'SA', name: 'Saudi Arabia', phone_code: '+966', flag_url: null },
      { code: 'AE', name: 'UAE',          phone_code: '+971', flag_url: null },
      { code: 'KW', name: 'Kuwait',       phone_code: '+965', flag_url: null },
      { code: 'QA', name: 'Qatar',        phone_code: '+974', flag_url: null },
      { code: 'BH', name: 'Bahrain',      phone_code: '+973', flag_url: null },
      { code: 'OM', name: 'Oman',         phone_code: '+968', flag_url: null },
      { code: 'EG', name: 'Egypt',        phone_code: '+20',  flag_url: null },
    ]
  } finally {
    loading.value = false
  }
})

onUnmounted(() => document.removeEventListener('mousedown', onClickOutside))
</script>

<style scoped>
/* ─ Wrapper ─ */
.phone-input-wrap {
  display: flex;
  align-items: stretch;
  border: 1.5px solid #ddd;
  border-radius: 8px;
  overflow: visible;
  background: #fff;
  transition: border-color 0.15s;
  position: relative;
}
.phone-input-wrap:focus-within {
  border-color: var(--color-primary, #888);
  box-shadow: 0 0 0 3px rgba(136,136,136,0.12);
}

/* ─ Selector button ─ */
.phone-cc-selector { position: relative; flex-shrink: 0; }
.phone-cc-btn {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 0 10px;
  background: #f9f9f9;
  border: none;
  border-right: 1.5px solid #ddd;
  border-radius: 6px 0 0 6px;
  cursor: pointer;
  height: 100%;
  min-height: 42px;
  font-size: 0.875rem;
  color: #333;
  white-space: nowrap;
  transition: background 0.15s;
}
.phone-cc-btn:hover:not(:disabled) { background: #f0f0f0; }
.phone-cc-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.phone-cc-flag { display: flex; align-items: center; }
.phone-cc-flag-img { width: 20px; height: 14px; object-fit: cover; border-radius: 2px; }
.phone-cc-emoji { font-size: 1.1rem; line-height: 1; }
.phone-cc-code { font-weight: 600; letter-spacing: 0.02em; }
.phone-cc-caret { opacity: 0.5; flex-shrink: 0; }

/* ─ Dropdown ─ */
.phone-cc-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  z-index: 999;
  background: #fff;
  border: 1.5px solid #ddd;
  border-radius: 10px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  width: 240px;
  overflow: hidden;
}

.phone-cc-search-wrap { padding: 8px; border-bottom: 1px solid #eee; }
.phone-cc-search {
  width: 100%;
  padding: 6px 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 0.875rem;
  outline: none;
  box-sizing: border-box;
}
.phone-cc-search:focus { border-color: var(--color-primary, #888); }

.phone-cc-list { max-height: 200px; overflow-y: auto; }
.phone-cc-option {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 8px 12px;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 0.875rem;
  text-align: left;
  transition: background 0.1s;
}
.phone-cc-option:hover, .phone-cc-option.active { background: #f5f5f5; }
.phone-cc-option.active { font-weight: 600; }
.phone-cc-option-name { flex: 1; color: #333; }
.phone-cc-option-code { color: #888; font-size: 0.8rem; white-space: nowrap; }
.phone-cc-empty { padding: 12px; text-align: center; color: #aaa; font-size: 0.875rem; }

/* ─ Number input ─ */
.phone-cc-number {
  flex: 1;
  border: none;
  outline: none;
  padding: 0 12px;
  font-size: 0.9375rem;
  background: transparent;
  min-width: 0;
  border-radius: 0 6px 6px 0;
}
.phone-cc-number::placeholder { color: #aaa; }
.phone-cc-number:disabled { opacity: 0.5; }

/* ─ Error state ─ */
.phone-input-wrap:has(.input-error) { border-color: #dc2626; }

/* ─ Transition ─ */
.cc-drop-enter-active, .cc-drop-leave-active { transition: opacity 0.15s, transform 0.15s; }
.cc-drop-enter-from, .cc-drop-leave-to { opacity: 0; transform: translateY(-6px); }

/* ─ RTL ─ */
html[dir="rtl"] .phone-cc-btn { border-right: none; border-left: 1.5px solid #ddd; border-radius: 0 6px 6px 0; }
html[dir="rtl"] .phone-cc-number { border-radius: 6px 0 0 6px; }
html[dir="rtl"] .phone-cc-dropdown { left: auto; right: 0; }
</style>
