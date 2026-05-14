// ─── API Response Envelope ───
export interface ApiResponse<T = any> {
  success: boolean
  data: T
  message?: string | null
  meta?: PaginationMeta
}

export interface PaginationMeta {
  total: number
  page: number
  perPage: number
  lastPage: number
}

// ─── Settings / Init ───
export interface InitData {
  storeName: string
  storeTagline: string | null
  logo: string | null
  favicon: string | null
  storeEmail: string | null
  storePhone: string | null
  whatsappNumber: string | null
  storeAddress: string | null
  currencies: ApiCurrency[]
  languages: ApiLanguage[]
  defaultCurrency: string
  defaultLanguage: string
  paymentMethods: PaymentMethod[]
  features: StoreFeatures
  seo: SeoSettings
  socialLinks: SocialLinks
  maintenance: MaintenanceInfo
  announcementText?: string | null
  announcementLink?: string | null
}

export interface ApiCurrency {
  code: string
  name: string
  symbol: string
  exchangeRate: number
  decimalPlaces: number
  isDefault: boolean
}

export interface ApiLanguage {
  code: string
  name: string
  direction: 'ltr' | 'rtl'
  isDefault: boolean
}

export interface PaymentMethod {
  id: string
  name: string
  fee?: number
}

export interface StoreFeatures {
  guestCheckout: boolean
  wishlist: boolean
  reviews: boolean
  reviewsRequireApproval: boolean
}

export interface SeoSettings {
  siteTitle: string | null
  metaDescription: string | null
  metaKeywords: string | null
}

export interface SocialLinks {
  facebook: string | null
  twitter: string | null
  instagram: string | null
  youtube: string | null
  tiktok: string | null
  snapchat?: string | null
}

export interface MaintenanceInfo {
  enabled: boolean
  message: string | null
}

// ─── Category ───
export interface Category {
  id: number
  name: string
  slug: string
  description?: string | null
  image?: string | null
  parentId?: number | null
  productCount?: number
  children?: Category[]
  sortOrder?: number
}

// ─── Brand ───
export interface Brand {
  id: number
  name: string
  slug: string
  logo?: string | null
}

// ─── Banner ───
export interface Banner {
  id: number
  title?: string | null
  subtitle?: string | null
  image: string | null
  mobileImage?: string | null
  linkUrl?: string | null
  position: string
  sortOrder: number
}

// ─── Product (listing) ───
export interface Product {
  id: number
  name: string
  slug: string
  sku?: string
  price: PriceValue
  comparePrice?: PriceValue | null
  flashSalePrice?: PriceValue | null
  discountPercent?: number | null
  currency: string
  primaryImage: string | null
  rating?: number | null
  reviewCount?: number | null
  inStock: boolean
  stockQuantity?: number | null
  isNew: boolean
  isFeatured: boolean
  category?: { id: number; name: string; slug: string } | null
  brand?: { id: number; name: string; slug: string } | null
}

export interface PriceValue {
  raw: number
  formatted: string
}

// ─── Product Detail ───
export interface ProductDetail extends Product {
  shortDescription?: string | null
  description?: string | null
  weight?: string | null
  trackStock: boolean
  allowBackorders: boolean
  images: ProductImage[]
  variants: ProductVariant[]
  tags: ProductTag[]
  reviews: ReviewSummary
  seo: {
    metaTitle?: string | null
    metaDescription?: string | null
    metaKeywords?: string | null
  }
}

export interface ProductImage {
  id: number
  url: string | null
  altText: string
  isPrimary: boolean
  sortOrder: number
}

export interface ProductVariant {
  id: number
  sku: string
  name: string
  price: PriceValue
  stockQuantity: number
  inStock: boolean
  attributes: Record<string, string>
}

export interface ProductTag {
  id: number
  name: string
  slug: string
}

export interface ReviewSummary {
  average: number
  count: number
  distribution: Record<number, number>
}

// ─── Review ───
export interface Review {
  id: number
  rating: number
  title?: string | null
  body?: string | null
  customerName: string
  adminReply?: string | null
  createdAt: string
}

// ─── Cart ───
export interface CartData {
  items: CartItem[]
  itemCount: number
  subtotal: PriceValue
  discountAmount: PriceValue
  couponCode?: string | null
  shippingAmount: PriceValue
  total: PriceValue
  currency: string
}

export interface CartItem {
  id: number
  productId: number
  variantId?: number | null
  productName: string
  productSlug?: string
  variantName?: string | null
  image?: string | null
  quantity: number
  unitPrice: PriceValue
  lineTotal: PriceValue
  inStock: boolean
}

// ─── User ───
export interface User {
  id: number
  name: string
  email: string
  phone?: string | null
  avatar?: string | null
  createdAt?: string
}

// ─── Address ───
export interface Address {
  id: number
  firstName: string
  lastName: string
  phone: string
  addressLine1: string
  addressLine2?: string
  city: string
  state?: string
  country: string
  postalCode?: string
  isDefault: boolean
}

// ─── Order ───
export interface Order {
  id: number
  orderNumber: string
  status: string
  statusLabel: string
  paymentStatus: string
  paymentMethod: string
  subtotal: PriceValue
  discountAmount: PriceValue
  shippingAmount: PriceValue
  taxAmount: PriceValue
  total: PriceValue
  currency: string
  couponCode?: string
  notes?: string
  createdAt: string
  items?: OrderItem[]
  shippingAddress?: Partial<Address>
  billingAddress?: Partial<Address>
  tracking?: {
    carrier?: string
    trackingNumber?: string
    trackingUrl?: string
    estimatedDelivery?: string
  }
  statusHistory?: {
    status: string
    note?: string
    createdAt: string
  }[]
}

export interface OrderItem {
  id: number
  productName: string
  sku: string
  quantity: number
  unitPrice: PriceValue
  total: PriceValue
  variantName?: string
  productImage?: string
}

// ─── Flash Sale ───
export interface FlashSale {
  id: number
  name: string
  startsAt: string
  endsAt: string
  products: Product[]
}

// ─── Blog ───
export interface BlogPost {
  id: number
  title: string
  slug: string
  excerpt?: string
  content?: string
  image?: string | null
  createdAt: string
}

// ─── CMS Page ───
export interface CmsPage {
  id: number
  title: string
  slug: string
  content: string
}
