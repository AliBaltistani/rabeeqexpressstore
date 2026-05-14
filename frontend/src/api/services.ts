import apiClient from './client'
import type {
  ApiResponse, InitData, Category, Brand, Banner,
  Product, ProductDetail, Review, CartData, FlashSale,
  User, Address, Order, BlogPost, CmsPage, PaginationMeta,
} from '@/types'

// ─── Helper to unwrap API envelope ───
function unwrap<T>(response: { data: ApiResponse<T> }): T {
  return response.data.data
}

function unwrapPaginated<T>(response: { data: ApiResponse<T[]> & { meta?: PaginationMeta } }): { data: T[]; meta: PaginationMeta | undefined } {
  return { data: response.data.data, meta: response.data.meta }
}

// ═══════════════════════════════════════════
// INIT
// ═══════════════════════════════════════════
export async function fetchInit(): Promise<InitData> {
  return unwrap(await apiClient.get('/init'))
}

// ═══════════════════════════════════════════
// CATEGORIES
// ═══════════════════════════════════════════
export async function fetchCategories(): Promise<Category[]> {
  return unwrap(await apiClient.get('/categories'))
}

export async function fetchCategoryBySlug(slug: string): Promise<Category> {
  return unwrap(await apiClient.get(`/categories/${slug}`))
}

// ═══════════════════════════════════════════
// BRANDS
// ═══════════════════════════════════════════
export async function fetchBrands(): Promise<Brand[]> {
  return unwrap(await apiClient.get('/brands'))
}

// ═══════════════════════════════════════════
// BANNERS
// ═══════════════════════════════════════════
export async function fetchBanners(position?: string): Promise<Banner[]> {
  const params: Record<string, string> = {}
  if (position) params.position = position
  return unwrap(await apiClient.get('/banners', { params }))
}

// ═══════════════════════════════════════════
// PRODUCTS
// ═══════════════════════════════════════════
export interface ProductsParams {
  category?: string
  brand?: string
  search?: string
  tags?: string
  ids?: string
  minPrice?: number
  maxPrice?: number
  rating?: number
  sortBy?: string
  page?: number
  perPage?: number
}

export async function fetchProducts(params?: ProductsParams) {
  return unwrapPaginated<Product>(await apiClient.get('/products', { params }))
}

export async function fetchProductBySlug(slug: string) {
  return unwrap<ProductDetail>(await apiClient.get(`/products/${slug}`))
}


export async function fetchFeaturedProducts(limit = 8): Promise<Product[]> {
  return unwrap(await apiClient.get('/products/featured', { params: { limit } }))
}

export async function fetchNewArrivals(limit = 8): Promise<Product[]> {
  return unwrap(await apiClient.get('/products/new-arrivals', { params: { limit } }))
}

export async function fetchBestSellers(limit = 8): Promise<Product[]> {
  return unwrap(await apiClient.get('/products/best-sellers', { params: { limit } }))
}

export async function searchProducts(q: string, page = 1, perPage = 12) {
  return unwrapPaginated<Product>(await apiClient.get('/products/search', { params: { q, page, perPage } }))
}

// ═══════════════════════════════════════════
// REVIEWS
// ═══════════════════════════════════════════
export async function fetchProductReviews(slug: string, page = 1) {
  return unwrapPaginated<Review>(await apiClient.get(`/products/${slug}/reviews`, { params: { page } }))
}

export async function submitReview(slug: string, data: { rating: number; title?: string; body: string }) {
  return unwrap(await apiClient.post(`/products/${slug}/reviews`, data))
}

// ═══════════════════════════════════════════
// FLASH SALES
// ═══════════════════════════════════════════
export async function fetchActiveFlashSales(): Promise<FlashSale | null> {
  try {
    return unwrap(await apiClient.get('/flash-sales/active'))
  } catch {
    return null
  }
}

// ═══════════════════════════════════════════
// CART
// ═══════════════════════════════════════════
export async function fetchCart(): Promise<CartData> {
  return unwrap(await apiClient.get('/cart'))
}

export async function addCartItem(productId: number, quantity: number, variantId?: number | null) {
  return unwrap(await apiClient.post('/cart/items', { productId, quantity, variantId }))
}

export async function updateCartItem(id: number, quantity: number) {
  return unwrap(await apiClient.put(`/cart/items/${id}`, { quantity }))
}

export async function removeCartItem(id: number) {
  return unwrap(await apiClient.delete(`/cart/items/${id}`))
}

export async function clearCartApi() {
  return unwrap(await apiClient.delete('/cart'))
}

export async function applyCouponApi(code: string) {
  return unwrap(await apiClient.post('/cart/coupon', { code }))
}

export async function removeCouponApi() {
  return unwrap(await apiClient.delete('/cart/coupon'))
}

// ═══════════════════════════════════════════
// AUTH
// ═══════════════════════════════════════════
export async function loginApi(email: string, password: string): Promise<{ user: User; token: string }> {
  return unwrap(await apiClient.post('/auth/login', { email, password }))
}

export async function registerApi(data: { name: string; email: string; password: string; password_confirmation: string }) {
  return unwrap(await apiClient.post('/auth/register', data))
}

export async function logoutApi() {
  return unwrap(await apiClient.post('/auth/logout'))
}

export async function forgotPasswordApi(email: string) {
  return unwrap(await apiClient.post('/auth/forgot-password', { email }))
}

export async function resetPasswordApi(data: { email: string; token: string; password: string; password_confirmation: string }) {
  return unwrap(await apiClient.post('/auth/reset-password', data))
}

export async function fetchMe(): Promise<User> {
  return unwrap(await apiClient.get('/auth/me'))
}

// ═══════════════════════════════════════════
// PROFILE
// ═══════════════════════════════════════════
export async function fetchProfile(): Promise<User> {
  return unwrap(await apiClient.get('/profile'))
}

export async function updateProfile(data: Partial<User>) {
  return unwrap(await apiClient.put('/profile', data))
}

export async function updatePassword(data: { current_password: string; password: string; password_confirmation: string }) {
  return unwrap(await apiClient.put('/profile/password', data))
}

// ═══════════════════════════════════════════
// ADDRESSES
// ═══════════════════════════════════════════
export async function fetchAddresses(): Promise<Address[]> {
  return unwrap(await apiClient.get('/addresses'))
}

export async function addAddress(data: Partial<Address>) {
  return unwrap(await apiClient.post('/addresses', data))
}

export async function updateAddress(id: number, data: Partial<Address>) {
  return unwrap(await apiClient.put(`/addresses/${id}`, data))
}

export async function deleteAddress(id: number) {
  return unwrap(await apiClient.delete(`/addresses/${id}`))
}

// ═══════════════════════════════════════════
// ORDERS
// ═══════════════════════════════════════════
export async function fetchOrders(page = 1) {
  return unwrapPaginated<Order>(await apiClient.get('/orders', { params: { page } }))
}

export async function fetchOrderByNumber(orderNumber: string): Promise<Order> {
  return unwrap(await apiClient.get(`/orders/${orderNumber}`))
}

// ═══════════════════════════════════════════
// WISHLIST
// ═══════════════════════════════════════════
export async function fetchWishlist(): Promise<Product[]> {
  return unwrap(await apiClient.get('/wishlist'))
}

export async function addWishlistItem(productId: number) {
  return unwrap(await apiClient.post('/wishlist', { productId }))
}

export async function removeWishlistItem(productId: number) {
  return unwrap(await apiClient.delete(`/wishlist/${productId}`))
}

// ═══════════════════════════════════════════
// CHECKOUT
// ═══════════════════════════════════════════
export async function fetchShippingRates(addressData: any) {
  return unwrap<any[]>(await apiClient.post('/checkout/shipping-rates', addressData))
}

export async function placeOrder(data: any) {
  return unwrap<any>(await apiClient.post('/checkout/place-order', data))
}

export async function fetchOrderSuccess(orderNumber: string) {
  return unwrap<any>(await apiClient.get(`/checkout/order-success/${orderNumber}`))
}

// ═══════════════════════════════════════════
// FLASH SALES
// ═══════════════════════════════════════════
export async function fetchActiveFlashSale() {
  return unwrap<any>(await apiClient.get('/flash-sales/active'))
}

// ═══════════════════════════════════════════
// BLOG
// ═══════════════════════════════════════════
export async function fetchBlogPosts(page = 1) {
  return unwrapPaginated<BlogPost>(await apiClient.get('/blog/posts', { params: { page } }))
}

export async function fetchBlogPost(slug: string): Promise<BlogPost> {
  return unwrap(await apiClient.get(`/blog/posts/${slug}`))
}

export async function fetchBlogCategories() {
  return unwrap(await apiClient.get('/blog/categories'))
}

// ═══════════════════════════════════════════
// CMS PAGES
// ═══════════════════════════════════════════
export async function fetchCmsPage(slug: string): Promise<CmsPage> {
  return unwrap(await apiClient.get(`/pages/${slug}`))
}
