import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export interface Currency {
  code: string
  name: string
  symbol: string
  amount: number
  country_code: string
}

export interface Language {
  name: string
  code: string
  url: string
  is_rtl: boolean
  country_code: string
}

export const useSettingsStore = defineStore('settings', () => {
  // State
  const currencies = ref<Record<string, Currency>>({
    SAR: { code: 'SAR', name: 'Saudi Riyal', symbol: 'SAR', amount: 1, country_code: 'sa' },
    AED: { code: 'AED', name: 'UAE Dirham', symbol: 'AED', amount: 0.979296, country_code: 'ae' },
    BHD: { code: 'BHD', name: 'Bahraini Dinar', symbol: 'BHD', amount: 0.100804, country_code: 'bh' },
    KWD: { code: 'KWD', name: 'Kuwaiti Dinar', symbol: 'KWD', amount: 0.082122, country_code: 'kw' },
    QAR: { code: 'QAR', name: 'Qatari Riyal', symbol: 'QAR', amount: 0.972096, country_code: 'qa' },
  })

  const languages = ref<Record<string, Language>>({
    AR: { name: 'العربية', code: 'ar', url: '', is_rtl: true, country_code: 'sa' },
    EN: { name: 'English', code: 'en', url: '', is_rtl: false, country_code: 'gb' },
  })

  const currentCurrencyCode = ref(localStorage.getItem('currency') || 'SAR')
  const currentLanguageCode = ref(localStorage.getItem('language') || 'en')

  // Store settings
  const storeSettings = ref({
    storeName: 'E-SEVEN STORE',
    storeDescription: "E7seven Store is the largest shoe store in Saudi Arabia 👟",
    whatsappNumber: '+966566229730',
    email: 'eseven.store@gmail.com',
    announcementText: 'What can the "Save" code do for you if you order two or more items? 👀',
    announcementLink: '',
    social: {
      instagram: 'https://instagram.com/eseven.store',
      snapchat: 'https://www.snapchat.com/add/eseven-store',
      tiktok: 'https://www.tiktok.com/@essven.store',
      youtube: 'https://www.youtube.com/@eseven-store/featured',
    },
    apps: {
      appstore: 'https://apps.apple.com/sa/app/eseven-store/id6453605018',
      googleplay: 'https://play.google.com/store/apps/details?id=com.salla.esevenstore&pli=1',
    },
  })

  // Getters
  const currentCurrency = computed(() => currencies.value[currentCurrencyCode.value])
  const currentLanguage = computed(() => languages.value[currentLanguageCode.value.toUpperCase()])
  const isRtl = computed(() => currentLanguage.value?.is_rtl ?? false)

  // Actions
  function setCurrency(code: string) {
    currentCurrencyCode.value = code
    localStorage.setItem('currency', code)
  }

  function setLanguage(code: string) {
    currentLanguageCode.value = code
    localStorage.setItem('language', code)
    const lang = languages.value[code.toUpperCase()]
    if (lang) {
      document.documentElement.lang = lang.code
      document.documentElement.dir = lang.is_rtl ? 'rtl' : 'ltr'
    }
  }

  // Initialize direction on load
  function initDirection() {
    const lang = currentLanguage.value
    if (lang) {
      document.documentElement.lang = lang.code
      document.documentElement.dir = lang.is_rtl ? 'rtl' : 'ltr'
    }
  }

  return {
    currencies, languages, currentCurrencyCode, currentLanguageCode,
    storeSettings, currentCurrency, currentLanguage, isRtl,
    setCurrency, setLanguage, initDirection,
  }
})
