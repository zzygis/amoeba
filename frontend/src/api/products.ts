// API paths for the catalog (backend/routes/api.php), and the store page's URL format.
import type { ProductType, SortOption } from '../types'

export interface StoreQuery {
  search: string
  types: ProductType[]
  platforms: string[]
  regions: string[]
  minPrice: string // euros, as typed; '' = no limit
  maxPrice: string
  sort: SortOption
  page: number
}

export const SORT_LABELS: Record<SortOption, string> = {
  stock: 'Availability: Most keys first',
  popular: 'Popularity: Most popular',
  price_asc: 'Price: Low to High',
  price_desc: 'Price: High to Low',
  discount: 'Discount: Biggest first',
  newest: 'Date: Newest first',
  oldest: 'Date: Oldest first',
  az: 'Alphabet: A-Z',
}

export const TYPE_LABELS: Record<ProductType, string> = {
  game: 'Games',
  dlc: 'DLC',
  software: 'Software',
}

const DEFAULT_QUERY: StoreQuery = {
  search: '',
  types: [],
  platforms: [],
  regions: [],
  minPrice: '',
  maxPrice: '',
  sort: 'stock', // most keys first
  page: 1,
}

// The store page keeps its state in the URL (?search=&platform=Steam,Xbox&sort=...),
// so filtered results can be bookmarked/shared and the back button works.
export function readStoreQuery(params: URLSearchParams): StoreQuery {
  const list = (key: string) => (params.get(key) ?? '').split(',').filter(Boolean)
  const sort = params.get('sort')
  return {
    search: params.get('search')?.trim() ?? '',
    types: list('type').filter((type): type is ProductType => type in TYPE_LABELS),
    platforms: list('platform'),
    regions: list('region'),
    minPrice: params.get('min_price') ?? '',
    maxPrice: params.get('max_price') ?? '',
    sort: sort && sort in SORT_LABELS ? (sort as SortOption) : 'stock',
    page: Math.max(1, Math.floor(Number(params.get('page'))) || 1),
  }
}

// The inverse of readStoreQuery: only non-default values end up in the URL.
export function storeQueryString(query: Partial<StoreQuery>): string {
  // Undefined values fall back to the defaults instead of overwriting them.
  const given = Object.fromEntries(Object.entries(query).filter(([, value]) => value !== undefined))
  const full: StoreQuery = { ...DEFAULT_QUERY, ...given }
  const params = new URLSearchParams()
  if (full.search) params.set('search', full.search)
  if (full.types.length) params.set('type', full.types.join(','))
  if (full.platforms.length) params.set('platform', full.platforms.join(','))
  if (full.regions.length) params.set('region', full.regions.join(','))
  if (full.minPrice) params.set('min_price', full.minPrice)
  if (full.maxPrice) params.set('max_price', full.maxPrice)
  if (full.sort !== 'stock') params.set('sort', full.sort)
  if (full.page > 1) params.set('page', String(full.page))
  return params.toString()
}

// The API takes the same parameter names (its sort also defaults to "stock").
export function productsPath(query: Partial<StoreQuery> & { perPage?: number }): string {
  const params = new URLSearchParams(storeQueryString(query))
  if (query.perPage) params.set('per_page', String(query.perPage))
  const qs = params.toString()
  return qs ? `/products?${qs}` : '/products'
}

export function productPath(slug: string): string {
  return `/products/${encodeURIComponent(slug)}`
}

export function storeLink(query: Partial<StoreQuery>): string {
  const qs = storeQueryString(query)
  return qs ? `/store?${qs}` : '/store'
}
