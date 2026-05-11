<template>
  <div class="home-page">
    <!-- 1. Hero Banner Slider -->
    <section class="s-block banners-slider">
      <div class="container">
        <HeroSlider :slides="heroSlides" />
      </div>
    </section>

    <!-- 2. Double Banner #1 -->
    <DoubleBanner :banners="doubleBanner1" />

    <!-- 3. Double Banner #2 -->
    <DoubleBanner :banners="doubleBanner2" />

    <!-- 4. Full-Width Banner (Nike Mind) -->
    <FullWidthBanner
      desktop-image="/banners/41d39492-d9d6-4b05-af45-9346a8d13f8d.webp"
      mobile-image="/banners/08732d95-1bc7-4b4b-b9e4-af803a378e7b.webp"
      link="/category/nike-mind"
    />

    <!-- 5. Product Slider — Trend Shoes -->
    <ProductSlider title="Trend Shoes" :products="trendShoes" />

    <!-- 6. Full-Width Banner -->
    <FullWidthBanner
      desktop-image="/banners/77e72542-347e-4a21-8432-b95adf8bcc8c.webp"
      mobile-image="/banners/bd30415e-22fc-47a5-bf69-994c301693b3.webp"
      link="/category/trend-shoes"
    />

    <!-- 7. Product Slider — Best Seller -->
    <ProductSlider title="Best Seller" :products="bestSeller" />

    <!-- 8. Full-Width Banner -->
    <FullWidthBanner
      desktop-image="/banners/2df640d7-c336-4f05-97cb-e0004308ab56.webp"
      mobile-image="/banners/6b44c0fb-2ed0-4be6-8a82-35fdd30cddb3.webp"
      link="/category/winter-clothes"
    />

    <!-- 9. Product Slider — Winter Clothes -->
    <ProductSlider title="ملابس شتوية" :products="winterClothes" />

    <!-- 10. Full-Width Banner -->
    <FullWidthBanner
      desktop-image="/banners/c2b91ac5-7cfe-4c67-a56e-20ac3f342fc5.webp"
      link="/category/on-cloud"
    />

    <!-- 11. Product Slider — On Cloud Shoes -->
    <ProductSlider title="On Cloud Shoes" :products="onCloudShoes" view-all-link="/category/on-cloud" />

    <!-- 12. Full-Width Banner -->
    <FullWidthBanner
      desktop-image="/banners/dc39aa3e-6ae4-410f-9b55-ab39b989cc2c.webp"
      link="/category/asics"
    />

    <!-- 13. Product Slider — ASICS Shoes -->
    <ProductSlider title="ASICS Shoes" :products="asicsShoes" />

    <!-- 14. Full-Width Banner -->
    <FullWidthBanner
      desktop-image="/banners/3e55e26b-84fc-4664-9f7e-6e6dabc31a0d.webp"
      mobile-image="/banners/bb9d15e7-1b33-4e92-9c53-5ef8ef98cb73.webp"
      link="/category/women-bags"
    />

    <!-- 15. Product Slider — Women's Bags -->
    <ProductSlider title="Women's bags" :products="womenBags" />

    <!-- 16. Full-Width Banner -->
    <FullWidthBanner
      desktop-image="/banners/ddc70bcc-6f77-49e2-aff6-888c5f9b3c67.webp"
      link="/category/watches"
    />

    <!-- 17. Product Slider — New Balance -->
    <ProductSlider title="New Balance" :products="newBalance" />

    <!-- 18. Full-Width Banner -->
    <FullWidthBanner
      desktop-image="/banners/f65fa2a3-b64f-47e4-8cf5-e17716a5b8b3.webp"
      link="/category/trend"
    />

    <!-- 19. Product Slider — Puma Shoes -->
    <ProductSlider title="Puma Shoes" :products="pumaShoes" />

    <!-- 20. Product Slider — Women's Heels -->
    <ProductSlider title="كعوب نسائية -هيلز نسائي" :products="womenHeels" />

    <!-- 21. Fixed Banner -->
    <section class="s-block fixed-banner-section">
      <div class="container">
        <router-link to="/category/unisex-shoes" class="fixed-banner__link" aria-label="Banner">
          <img
            src="/banners/62174094-6428-4cd1-ae86-e8c5045e677e.webp"
            alt="fixed banner"
            class="fixed-banner__img"
            loading="lazy"
            width="1200"
            height="300"
          />
        </router-link>
      </div>
    </section>

    <!-- 22. Customer Reviews -->
    <TestimonialsSlider title="Customers Reviews" :reviews="customerReviews" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import HeroSlider from '@/components/home/HeroSlider.vue'
import DoubleBanner from '@/components/home/DoubleBanner.vue'
import FullWidthBanner from '@/components/home/FullWidthBanner.vue'
import ProductSlider from '@/components/home/ProductSlider.vue'
import TestimonialsSlider from '@/components/home/TestimonialsSlider.vue'

// === HERO SLIDES ===
const heroSlides = [
  {
    image: '/banners/62174094-6428-4cd1-ae86-e8c5045e677e.webp',
    link: '/category/trend-shoes',
    alt: 'Trend Shoes Collection'
  },
  {
    image: '/banners/05580cfe-34c9-44af-8421-db9e35675c89.webp',
    link: '/category/trend-shoes',
    alt: 'Best Sellers'
  }
]

// === DOUBLE BANNERS ===
const doubleBanner1 = [
  { image: '/banners/3de4e3f7-ba66-4c06-8e09-2d9ea3722da0.webp', link: '/category/asics' },
  { image: '/banners/638fa0cb-af57-4f0e-991c-e528ac469d77.webp', link: '/category/watches' }
]
const doubleBanner2 = [
  { image: '/banners/3471c97b-a350-45d3-81f6-61207b6ba601.webp', link: '/category/on-cloud' },
  { image: '/banners/4e9d459c-6e49-4412-960a-5658495015bc.webp', link: '/category/nike' }
]

// === PLACEHOLDER PRODUCT IMAGE GENERATOR ===
function makeProduct(id: number, name: string, price: number, oldPrice?: number, subtitle?: string) {
  const colors = ['e8d5c4', 'c4d5e8', 'd5e8c4', 'e8c4d5', 'c4e8d5', 'd5c4e8', 'f0e0d0', 'e0f0d0']
  const bgColor = colors[id % colors.length]
  return {
    id,
    slug: name.toLowerCase().replace(/[^a-z0-9]+/g, '-'),
    name,
    subtitle: subtitle || undefined,
    image: `https://placehold.co/400x400/${bgColor}/666?text=${encodeURIComponent(name.split(' ').slice(0, 2).join('+'))}`,
    price,
    oldPrice: oldPrice || undefined,
    discount: oldPrice ? Math.round((1 - price / oldPrice) * 100) : undefined,
    currency: 'SAR'
  }
}

// === PRODUCT DATA ===
const trendShoes = [
  makeProduct(1, 'Nike Air Max 90', 599, 799, 'Nike'),
  makeProduct(2, 'Jordan 1 Retro High', 899, undefined, 'Jordan'),
  makeProduct(3, 'Nike Dunk Low', 549, 699, 'Nike'),
  makeProduct(4, 'Adidas Yeezy 350', 1099, undefined, 'Adidas'),
  makeProduct(5, 'Nike Air Force 1', 449, 599, 'Nike'),
  makeProduct(6, 'New Balance 550', 499, undefined, 'New Balance'),
  makeProduct(7, 'Puma RS-X', 389, 499, 'Puma')
]

const bestSeller = [
  makeProduct(10, 'Nike Vomero 5', 649, 849, 'Nike'),
  makeProduct(11, 'ASICS Gel-1130', 549, undefined, 'ASICS'),
  makeProduct(12, 'Nike V2K Run', 599, 749, 'Nike'),
  makeProduct(13, 'Jordan 4 Retro', 999, undefined, 'Jordan'),
  makeProduct(14, 'Nike Blazer Mid', 449, 549, 'Nike'),
  makeProduct(15, 'On Cloud 5', 699, undefined, 'On Cloud'),
  makeProduct(16, 'NB 2002R', 599, 799, 'New Balance'),
  makeProduct(17, 'Puma Suede XL', 349, undefined, 'Puma')
]

const winterClothes = [
  makeProduct(20, 'North Face Jacket', 899, 1199, 'North Face'),
  makeProduct(21, 'Nike Tech Fleece', 599, undefined, 'Nike'),
  makeProduct(22, 'Adidas Hoodie', 449, 599, 'Adidas'),
  makeProduct(23, 'Puma Winter Coat', 749, undefined, 'Puma'),
  makeProduct(24, 'Jordan Windbreaker', 549, 699, 'Jordan'),
  makeProduct(25, 'NB Padded Jacket', 649, undefined, 'New Balance')
]

const onCloudShoes = [
  makeProduct(30, 'On Cloud 5', 699, undefined, 'On'),
  makeProduct(31, 'On Cloudmonster', 799, undefined, 'On'),
  makeProduct(32, 'On Cloudswift 3', 749, 899, 'On'),
  makeProduct(33, 'On Cloudnova', 649, undefined, 'On'),
  makeProduct(34, 'On Cloudsurfer', 699, 849, 'On'),
  makeProduct(35, 'On Roger Pro', 899, undefined, 'On'),
  makeProduct(36, 'On Cloudflyer 4', 749, undefined, 'On'),
  makeProduct(37, 'On Cloudace 3', 799, 999, 'On')
]

const asicsShoes = [
  makeProduct(40, 'ASICS Gel-1130', 549, undefined, 'ASICS'),
  makeProduct(41, 'ASICS Gel-Kayano 30', 799, 999, 'ASICS'),
  makeProduct(42, 'ASICS GT-2160', 599, undefined, 'ASICS'),
  makeProduct(43, 'ASICS Gel-Nimbus 25', 749, 899, 'ASICS'),
  makeProduct(44, 'ASICS GEL-NYC', 649, undefined, 'ASICS'),
  makeProduct(45, 'Onitsuka Tiger', 549, 699, 'Onitsuka'),
  makeProduct(46, 'ASICS EX89', 499, undefined, 'ASICS'),
  makeProduct(47, 'ASICS Gel-Venture', 399, 549, 'ASICS')
]

const womenBags = [
  makeProduct(50, 'Chanel Classic Flap', 4999, undefined, 'Chanel'),
  makeProduct(51, 'Gucci Marmont', 3499, 3999, 'Gucci'),
  makeProduct(52, 'LV Neverfull MM', 4299, undefined, 'Louis Vuitton'),
  makeProduct(53, 'Dior Saddle Bag', 3999, undefined, 'Dior'),
  makeProduct(54, 'Prada Re-Edition', 2999, 3499, 'Prada'),
  makeProduct(55, 'YSL Loulou', 2799, undefined, 'YSL'),
  makeProduct(56, 'Bottega Cassette', 3199, 3699, 'Bottega'),
  makeProduct(57, 'Celine Triomphe', 2999, undefined, 'Celine')
]

const newBalance = [
  makeProduct(60, 'NB 990v6', 899, undefined, 'New Balance'),
  makeProduct(61, 'NB 2002R', 599, 749, 'New Balance'),
  makeProduct(62, 'NB 550', 499, undefined, 'New Balance'),
  makeProduct(63, 'NB 530', 449, 549, 'New Balance'),
  makeProduct(64, 'NB 1906R', 699, undefined, 'New Balance'),
  makeProduct(65, 'NB 574', 399, 499, 'New Balance'),
  makeProduct(66, 'NB 327', 449, undefined, 'New Balance'),
  makeProduct(67, 'NB 9060', 699, 849, 'New Balance')
]

const pumaShoes = [
  makeProduct(70, 'Puma Suede XL', 349, undefined, 'Puma'),
  makeProduct(71, 'Puma RS-X', 399, 499, 'Puma'),
  makeProduct(72, 'Puma Palermo', 349, undefined, 'Puma'),
  makeProduct(73, 'Puma Speedcat OG', 449, 549, 'Puma'),
  makeProduct(74, 'Puma Clyde', 399, undefined, 'Puma'),
  makeProduct(75, 'Puma MB.03', 599, 749, 'Puma'),
  makeProduct(76, 'Puma Fenty', 449, undefined, 'Puma')
]

const womenHeels = [
  makeProduct(80, 'Jimmy Choo Bing', 2999, undefined, 'Jimmy Choo'),
  makeProduct(81, 'Manolo Blahnik', 3499, 3999, 'Manolo'),
  makeProduct(82, 'Christian Louboutin', 3999, undefined, 'Louboutin'),
  makeProduct(83, 'Valentino Garavani', 2799, 3299, 'Valentino'),
  makeProduct(84, 'Stuart Weitzman', 1999, undefined, 'S. Weitzman'),
  makeProduct(85, 'Gianvito Rossi', 2499, 2999, 'G. Rossi'),
  makeProduct(86, 'Aquazzura Tequila', 2299, undefined, 'Aquazzura'),
  makeProduct(87, 'Amina Muaddi', 2699, 3199, 'Amina Muaddi')
]

// === CUSTOMER REVIEWS ===
const customerReviews = [
  { name: 'سلوى الحوطي', avatar: 'https://placehold.co/96/e8d5c4/666?text=S', rating: 5, text: 'الشوز مريح جدا' },
  { name: 'Gharam .', avatar: 'https://placehold.co/96/c4d5e8/666?text=G', rating: 5, text: 'Very comfortable shoes and fast delivery!' },
  { name: 'محمد العتيبي', avatar: 'https://placehold.co/96/d5e8c4/666?text=M', rating: 5, text: 'جودة ممتازة وسعر مناسب' },
  { name: 'Sarah K.', avatar: 'https://placehold.co/96/e8c4d5/666?text=SK', rating: 5, text: 'Amazing quality, will order again!' },
  { name: 'عبدالله الشمري', avatar: 'https://placehold.co/96/c4e8d5/666?text=A', rating: 4, text: 'الحذاء جميل والتوصيل سريع' },
  { name: 'Nora A.', avatar: 'https://placehold.co/96/d5c4e8/666?text=N', rating: 5, text: 'Best store for shoes in Saudi!' },
  { name: 'فهد القحطاني', avatar: 'https://placehold.co/96/f0e0d0/666?text=F', rating: 5, text: 'تجربة رائعة' },
  { name: 'Lina M.', avatar: 'https://placehold.co/96/e0f0d0/666?text=L', rating: 5, text: 'عجبتني' }
]
</script>

<style scoped>
.home-page {
  background: var(--bg-primary, #fff);
}

/* Shared s-block spacing to match the Salla template */
.s-block {
  margin-bottom: 0.25rem;
}

/* Fixed banner section */
.fixed-banner-section {
  padding: 0.5rem 0;
}
.fixed-banner__link {
  display: block;
  overflow: hidden;
  transition: transform 0.3s ease;
}
.fixed-banner__link:hover {
  transform: translateY(-2px);
}
.fixed-banner__img {
  width: 100%;
  height: auto;
  max-width: 100%;
  object-fit: contain;
  display: block;
}
</style>
