import { createI18n } from 'vue-i18n'
import en from './en.json'
import ar from './ar.json'

const savedLocale = localStorage.getItem('language') || 'en'

const i18n = createI18n({
  legacy: false,
  locale: savedLocale,
  fallbackLocale: 'en',
  messages: { en, ar },
})

export default i18n
