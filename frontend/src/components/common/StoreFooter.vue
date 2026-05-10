<template>
  <footer class="store-footer">
    <!-- Main Footer -->
    <div class="store-footer__inner" aria-label="footer">
      <div class="container">
        <div class="footer-grid">
          <!-- Column 1: Logo & Description -->
          <div class="footer-col footer-col--brand">
            <router-link to="/" class="footer-logo" aria-label="logo">
              <img :src="logoSrc" height="64" style="height: 64px;" alt="ESEVEN STORE" class="footer-logo-img" />
            </router-link>
            <p class="footer-description">
              {{ isRtl ? 'اي سفن ستور أكبر متجر أحذية في المملكة العربية السعودية 👟 نقدم ملابس رجالية ونسائية تناسب ذوقك' : "E7seven Store is the largest shoe store in Saudi Arabia 👟 Offering men's and women's clothing to suit your style" }}
            </p>
          </div>

          <!-- Column 2: Important Links -->
          <div class="footer-col">
            <h3 class="footer-title">{{ isRtl ? 'روابط مهمة' : 'Important Links' }}</h3>
            <ul class="footer-list">
              <li><router-link to="/about-us">{{ isRtl ? 'من نحن' : 'About us' }}</router-link></li>
              <li><router-link to="/privacy-policy">{{ isRtl ? 'سياسة الخصوصية' : 'Privacy Policy' }}</router-link></li>
              <li><router-link to="/exchange-return-policy">{{ isRtl ? 'سياسة الاستبدال والاسترجاع' : 'Exchange and Return Policy' }}</router-link></li>
              <li><router-link to="/terms-conditions">{{ isRtl ? 'الشروط والأحكام' : 'Terms and Conditions' }}</router-link></li>
              <li><router-link to="/tamara-payment">{{ isRtl ? 'خدمة الدفع تمارا' : 'Tamara payment service' }}</router-link></li>
              <li><router-link to="/affiliate">{{ isRtl ? 'برنامج التسويق بالعمولة' : 'Affiliate Marketing Program' }}</router-link></li>
            </ul>
          </div>

          <!-- Column 3: Contact Us -->
          <div class="footer-col">
            <h3 class="footer-title">{{ isRtl ? 'تواصل معنا' : 'Contact Us' }}</h3>
            <ul class="footer-list footer-contacts">
              <li>
                <a :href="'https://wa.me/' + settings.storeSettings.whatsappNumber.replace('+', '')" target="_blank" rel="noreferrer">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                  <span>WhatsApp</span>
                </a>
              </li>
              <li>
                <a :href="'mailto:' + settings.storeSettings.email">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                  <span>{{ settings.storeSettings.email }}</span>
                </a>
              </li>
            </ul>
            <!-- Social Links -->
            <div class="footer-social">
              <a v-if="settings.storeSettings.social.instagram" :href="settings.storeSettings.social.instagram" target="_blank" rel="noreferrer" aria-label="Instagram" class="social-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
              </a>
              <a v-if="settings.storeSettings.social.snapchat" :href="settings.storeSettings.social.snapchat" target="_blank" rel="noreferrer" aria-label="Snapchat" class="social-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.206.793c.99 0 4.347.276 5.93 3.821.529 1.193.403 3.219.299 4.847l-.003.06c-.012.18-.022.345-.03.51.075.045.203.09.401.09.3-.016.659-.12.959-.289.089-.05.189-.074.289-.074a.721.721 0 01.69.496c.071.234.018.477-.148.647-.093.098-.586.539-1.813.8a.66.66 0 00-.471.384c-.074.18-.073.381.006.56.329.735.796 1.38 1.386 1.912.263.236.569.438.89.6.186.094.39.212.457.354.068.142.067.318-.018.472-.121.215-.385.363-.745.434-.24.048-.49.073-.744.082-.144.005-.287.014-.425.029-.195.023-.387.059-.574.11-.238.065-.462.172-.668.293-.308.183-.586.398-.834.644-.435.431-.826.954-1.194 1.381-.195.227-.39.437-.592.614-.404.354-.855.572-1.349.572-.071 0-.142-.006-.213-.018a2.21 2.21 0 01-.459-.13c-.188-.076-.376-.167-.546-.268-.342-.199-.627-.448-.846-.637-.194-.167-.376-.345-.56-.524-.209-.202-.42-.39-.649-.555a3.159 3.159 0 00-.786-.389 5.088 5.088 0 00-.573-.14c-.12-.024-.25-.04-.378-.046-.134-.006-.287-.007-.446-.014-.261-.008-.527-.033-.786-.093a1.07 1.07 0 01-.645-.404.664.664 0 01-.033-.494c.063-.151.263-.268.455-.365.312-.16.621-.363.89-.603.59-.532 1.057-1.177 1.386-1.912.079-.179.08-.38.006-.56a.66.66 0 00-.471-.384c-1.227-.261-1.72-.702-1.813-.8a.668.668 0 01-.148-.647.723.723 0 01.69-.496.54.54 0 01.289.074c.3.169.659.305.959.289.198 0 .326-.045.401-.09-.008-.165-.018-.33-.03-.51l-.003-.06c-.104-1.628-.23-3.654.299-4.847C7.859 1.069 11.216.793 12.206.793"/></svg>
              </a>
              <a v-if="settings.storeSettings.social.tiktok" :href="settings.storeSettings.social.tiktok" target="_blank" rel="noreferrer" aria-label="TikTok" class="social-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.12V9.01a6.23 6.23 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34A6.34 6.34 0 009.49 21.64a6.34 6.34 0 006.34-6.34V8.95a8.27 8.27 0 004.81 1.54V7.05a4.84 4.84 0 01-1.05-.36z"/></svg>
              </a>
              <a v-if="settings.storeSettings.social.youtube" :href="settings.storeSettings.social.youtube" target="_blank" rel="noreferrer" aria-label="YouTube" class="social-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
              </a>
            </div>
          </div>

          <!-- Column 4: Download App -->
          <div class="footer-col footer-col--app">
            <h3 class="footer-title" style="text-align: center;">{{ isRtl ? 'حمل تطبيق الجوال' : 'Download Mobile App' }}</h3>
            <div class="app-badges">
              <a :href="settings.storeSettings.apps.appstore" target="_blank" rel="noreferrer" aria-label="App Store">
                <img src="@/assets/images/appstore_v-2.0.5.png" alt="App Store" width="135" height="40" />
              </a>
              <a :href="settings.storeSettings.apps.googleplay" target="_blank" rel="noreferrer" aria-label="Google Play">
                <img src="@/assets/images/googleplay_v-2.0.5.png" alt="Google Play" width="135" height="40" />
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Footer -->
    <div class="footer-bottom">
      <div class="container footer-bottom__inner">
        <div class="footer-rights">
          <p>Copyright | 2026 <router-link to="/" class="hover-primary">ESEVEN STORE</router-link></p>
        </div>
        <div class="footer-payments">
          <span class="payment-icon">💳 Mada</span>
          <span class="payment-icon">💳 Visa</span>
          <span class="payment-icon">💳 Apple Pay</span>
          <span class="payment-icon">💳 Tamara</span>
          <span class="payment-icon">💳 Tabby</span>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useSettingsStore } from '@/stores/settingsStore'
import logoImage from '@/assets/images/iEP6VGV6IrUHSpWx0M39HR3cvuGuKmQXUBAcE30B.png'

const settings = useSettingsStore()
const isRtl = computed(() => settings.isRtl)
const logoSrc = logoImage
</script>

<style scoped>
.store-footer {
  background: var(--footer-bg);
  color: var(--footer-text-color);
  padding-top: 2.5rem;
  margin-top: auto;
}

.footer-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}
@media (min-width: 1024px) {
  .footer-grid {
    grid-template-columns: 2fr 1fr 1fr 1fr;
    gap: 1.5rem;
  }
}

.footer-col--brand {
  grid-column: span 2;
}
@media (min-width: 1024px) {
  .footer-col--brand { grid-column: span 1; }
}

.footer-logo {
  display: inline-block;
  margin-bottom: 0.5rem;
  text-decoration: none;
}
.footer-logo-img {
  height: 64px;
  width: auto;
}
@media (max-width: 1023px) {
  .footer-logo { display: flex; justify-content: center; }
}

.footer-description {
  font-size: 0.875rem;
  line-height: 1.5rem;
  margin: 1rem 0;
  max-width: 24rem;
}
@media (max-width: 1023px) {
  .footer-description { text-align: center; max-width: 100%; }
}

.footer-title {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 1rem;
  color: var(--store-text-primary);
}

.footer-list {
  list-style: none;
  margin: 0;
  padding: 0;
}
.footer-list li {
  margin-bottom: 0.5rem;
}
.footer-list a {
  font-size: 0.875rem;
  color: var(--footer-text-color);
  text-decoration: none;
  transition: color 0.15s ease;
}
.footer-list a:hover {
  color: var(--color-primary);
}

.footer-contacts a {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.footer-social {
  display: flex;
  gap: 0.75rem;
  margin-top: 1rem;
}
.social-link {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 50%;
  background: var(--bg-secondary);
  color: var(--footer-text-color);
  transition: all 0.15s ease;
}
.social-link:hover {
  background: var(--color-primary);
  color: #fff;
}

.footer-col--app {
  grid-column: span 2;
}
@media (min-width: 1024px) {
  .footer-col--app { grid-column: span 1; }
}

.app-badges {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin-top: 0.75rem;
}
.app-badges a {
  display: block;
  transition: opacity 0.15s ease;
}
.app-badges a:hover { opacity: 0.8; }
.app-badges img { height: 40px; width: auto; }

/* Bottom Footer */
.footer-bottom {
  background: var(--bottom-footer-bg);
  border-top: 1px solid var(--product-border-color);
  margin-top: 2rem;
}
.footer-bottom__inner {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  flex-direction: column-reverse;
  gap: 0.75rem;
  padding-top: 1rem;
  padding-bottom: 1rem;
}
@media (min-width: 1024px) {
  .footer-bottom__inner {
    flex-direction: row;
    justify-content: space-between;
  }
}

.footer-rights {
  font-size: 0.875rem;
  color: #9ca3af;
}
.hover-primary:hover { color: var(--color-primary); }

.footer-payments {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  justify-content: center;
}
.payment-icon {
  font-size: 0.75rem;
  color: #9ca3af;
  background: var(--bg-secondary);
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
}
</style>
