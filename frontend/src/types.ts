// TypeScript shapes of the API responses. Keep these in sync with the
// Laravel API Resources in backend/app/Http/Resources.

export interface HealthStatus {
  status: 'ok' | 'degraded'
  app: string
  database: 'ok' | 'unreachable'
  time: string
}

// Laravel wraps a single resource in { data: ... }.
export interface Wrapped<T> {
  data: T
}

// Laravel's paginated resource collection (only the fields we use).
export interface Paginated<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

// ProductResource.php
export interface Product {
  id: number
  slug: string
  name: string // "Elden Ring Steam Key GLOBAL"
  title: string // "Elden Ring"
  platform: string
  region: string
  // The best offer: the cheapest seller that still has keys ("From €X"). Null when sold out.
  offer_id: number | null
  price_cents: number | null
  original_price_cents: number | null // the publisher's list price
  discount_percent: number | null
  type: ProductType
  cover_url: string
  cover_portrait: boolean // false: only a wide image exists (some DLC)
  stock: number
}

export type ProductType = 'game' | 'dlc' | 'software'

// OfferResource.php: one seller's offer for a product.
export interface Offer {
  id: number
  price_cents: number
  discount_percent: number | null
  stock: number // this seller's keys
  seller: {
    name: string
    rating: number // 0-10
    rating_label: string // "Superb", "Excellent", ...
    verified: boolean
  }
}

export interface Trailer {
  title: string
  poster: string
  hls_url: string // HLS stream from Steam
}

// ProductDetailResource.php
export interface ProductDetail extends Product {
  game: {
    description: string
    developer: string
    publisher: string
    release_date: string | null
    genres: string[]
    modes: string[]
    languages: string[]
    screenshots: string[]
    requirements: [label: string, value: string][]
    trailer: Trailer | null
  }
  offers: Offer[] // every seller's offer, cheapest first, sold-out last
  other_listings: Product[]
  rating: { average: number | null; count: number }
}

// GET /products/filters
export interface FilterOptions {
  types: { name: ProductType; count: number }[]
  platforms: { name: string; count: number }[]
  regions: { name: string; count: number }[]
  price: { min_cents: number; max_cents: number }
}

export type SortOption = 'popular' | 'price_asc' | 'price_desc' | 'discount' | 'newest' | 'oldest' | 'az'

// ReviewResource.php
export interface Review {
  id: number
  author: string
  rating: number
  body: string
  created_at: string
}

export interface ReviewPage extends Paginated<Review> {
  can_review: boolean
}

// POST /coupons/check
export interface Coupon {
  code: string
  percent_off: number
}

// CartResource.php
// A cart line is one seller's offer for one product.
export interface CartLine {
  offer: Offer
  product: Pick<Product, 'id' | 'slug' | 'name' | 'platform' | 'cover_url' | 'cover_portrait'>
  quantity: number
  line_total_cents: number
}

export interface Cart {
  items: CartLine[]
  count: number
  total_cents: number
}

// OrderResource.php
export type PaymentMethod = 'card' | 'paypal'

export interface OrderItem {
  id: number
  product_slug: string
  name: string
  seller_name: string
  platform: string
  cover_url: string
  price_cents: number
  quantity: number
  keys?: string[] // only on the order page, never in the list
}

export interface Order {
  id: number
  created_at: string
  payment_method: PaymentMethod
  subtotal_cents: number
  discount_cents: number
  coupon_code: string | null
  total_cents: number
  items: OrderItem[]
}

// UserResource.php / AuthController.php
export interface User {
  id: number
  name: string
  email: string
  is_admin: boolean
}

export interface AuthResponse {
  token: string
  user: User
}

// GET /home: the homepage blocks (HomeController.php). The page draws them in this order.
export type HomeBlock =
  | { id: number; type: 'banner'; title: string; content: { product: Product | null } }
  | { id: number; type: 'platforms'; title: string; content: { platforms: { name: string; count: number }[] } }
  | {
      id: number
      type: 'product_row'
      title: string
      content: {
        products: Product[]
        // Store query for the "See all" link, e.g. {sort: "discount", max_price: 10}
        see_all: { sort?: SortOption; max_price?: number; type?: ProductType }
      }
    }
