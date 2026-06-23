<template>
  <Teleport to="body">
    <transition name="modal-fade">
      <div v-if="isOpen" class="modal-overlay" @click.self="close">
        <div class="modal-container">
          <!-- Close Button -->
          <button class="modal-close" @click="close" :aria-label="$t('common.close')">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>

          <!-- Shimmer Loading State -->
          <div v-if="isLoading" class="modal-shimmer">
            <div class="shimmer-title"></div>
            <div class="shimmer-row" v-for="i in 2" :key="'lang-'+i"></div>
            <div class="shimmer-title" style="margin-top: 1.5rem;"></div>
            <div class="shimmer-row" v-for="i in 5" :key="'curr-'+i"></div>
            <div class="shimmer-btn"></div>
          </div>

          <!-- Loaded Content -->
          <div v-else class="modal-content">
            <!-- Language Section -->
            <h3 class="modal-section-title">{{ $t('common.language') }}</h3>
            <div class="modal-options">
              <label
                v-for="(lang, key) in languages"
                :key="key"
                class="modal-option"
                :class="{ 'modal-option--active': selectedLanguage === lang.code }"
              >
                <span class="radio-outer">
                  <span class="radio-inner" :class="{ active: selectedLanguage === lang.code }"></span>
                </span>
                <span class="option-name">{{ lang.name }}</span>
                <input
                  type="radio"
                  :value="lang.code"
                  v-model="selectedLanguage"
                  class="sr-only"
                  name="language"
                />
              </label>
            </div>

            <!-- Currency Section -->
            <h3 class="modal-section-title" style="margin-top: 1.5rem;">{{ $t('common.currency') }}</h3>
            <div class="modal-options">
              <label
                v-for="(currency, key) in currencies"
                :key="key"
                class="modal-option"
                :class="{ 'modal-option--active': selectedCurrency === currency.code }"
              >
                <span class="radio-outer">
                  <span class="radio-inner" :class="{ active: selectedCurrency === currency.code }"></span>
                </span>
                <span class="option-name">{{ currency.name }}</span>
                <span class="option-code">{{ currency.code }}</span>
                <input
                  type="radio"
                  :value="currency.code"
                  v-model="selectedCurrency"
                  class="sr-only"
                  name="currency"
                />
              </label>
            </div>

            <!-- OK Button -->
            <button class="modal-ok-btn" @click="apply">{{ $t('common.ok') }}</button>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useSettingsStore } from '@/stores/settingsStore'
import { useLanguage } from '@/composables/useLanguage'

const props = defineProps<{ isOpen: boolean }>()
const emit = defineEmits(['close'])

const settings = useSettingsStore()
const { switchLanguage } = useLanguage()
const languages = computed(() => settings.languageList)
const currencies = computed(() => settings.currencyList)

const selectedLanguage = ref(settings.currentLanguageCode)
const selectedCurrency = ref(settings.currentCurrencyCode)
const isLoading = ref(false)

// Reset selections when modal opens
watch(() => props.isOpen, (val) => {
  if (val) {
    selectedLanguage.value = settings.currentLanguageCode
    selectedCurrency.value = settings.currentCurrencyCode
    // Simulate shimmer loading
    isLoading.value = true
    setTimeout(() => {
      isLoading.value = false
    }, 600)
  }
})

function close() {
  emit('close')
}

function apply() {
  switchLanguage(selectedLanguage.value)
  settings.setCurrency(selectedCurrency.value)
  close()
  // Reload page to refetch all API data with new lang/currency
  window.location.reload()
}
</script>

<style scoped>
/* ── Overlay ── */
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 500;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding-top: 5vh;
}

/* ── Container ── */
.modal-container {
  background: #fff;
  border-radius: 0.5rem;
  width: 100%;
  max-width: 400px;
  position: relative;
  box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
  max-height: 85vh;
  overflow-y: auto;
}

/* ── Close ── */
.modal-close {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  z-index: 1;
  color: #ef4444;
  cursor: pointer;
  background: none;
  border: none;
  padding: 0.25rem;
  display: flex;
  transition: opacity 0.15s ease;
}
html[dir="rtl"] .modal-close { right: auto; left: 0.75rem; }
.modal-close:hover { opacity: 0.7; }

/* ── Content ── */
.modal-content {
  padding: 1.5rem 1.5rem 1.25rem;
}

/* ── Section Title ── */
.modal-section-title {
  font-size: 1rem;
  font-weight: 700;
  color: #111827;
  margin-bottom: 0.75rem;
}

/* ── Options List ── */
.modal-options {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.modal-option {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 0;
  border-bottom: 1px solid #f3f4f6;
  cursor: pointer;
  transition: background 0.1s ease;
  user-select: none;
}
.modal-option:last-child { border-bottom: none; }
.modal-option:hover { background: #fafafa; }

/* ── Custom Radio ── */
.radio-outer {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 2px solid #d1d5db;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: border-color 0.15s ease;
}
.modal-option--active .radio-outer {
  border-color: #3b82f6;
}
.radio-inner {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: transparent;
  transition: background 0.15s ease;
}
.radio-inner.active {
  background: #3b82f6;
}

.option-name {
  flex: 1;
  font-size: 0.9375rem;
  color: #374151;
}
.option-code {
  font-size: 0.8125rem;
  color: #9ca3af;
  font-weight: 400;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0,0,0,0);
  white-space: nowrap;
  border: 0;
}

/* ── OK Button ── */
.modal-ok-btn {
  width: 100%;
  margin-top: 1.25rem;
  padding: 0.75rem;
  background: #111827;
  color: #fff;
  border: none;
  border-radius: 0.375rem;
  font-size: 0.9375rem;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  transition: background 0.15s ease;
}
.modal-ok-btn:hover { background: #1f2937; }

/* ── Shimmer Loading ── */
.modal-shimmer {
  padding: 1.5rem 1.5rem 1.25rem;
}

.shimmer-title {
  width: 80px;
  height: 18px;
  background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
  margin-bottom: 0.75rem;
}
.shimmer-row {
  width: 100%;
  height: 44px;
  background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
  margin-bottom: 0.375rem;
}
.shimmer-btn {
  width: 100%;
  height: 44px;
  background: linear-gradient(90deg, #e5e7eb 25%, #d1d5db 50%, #e5e7eb 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 6px;
  margin-top: 1.25rem;
}

@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

/* ── Transitions ── */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s ease;
}
.modal-fade-enter-active .modal-container,
.modal-fade-leave-active .modal-container {
  transition: transform 0.2s ease, opacity 0.2s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
.modal-fade-enter-from .modal-container {
  transform: scale(0.95);
  opacity: 0;
}
.modal-fade-leave-to .modal-container {
  transform: scale(0.95);
  opacity: 0;
}
</style>
