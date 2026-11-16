// Unit tests for the small pure helpers the pages rely on. Run with: npm test
import { describe, expect, it } from 'vitest'
import { productsPath, readStoreQuery, storeLink, storeQueryString } from '../api/products'
import { discountCents } from './discount'
import { formatPrice, plural } from './format'
import { pageNumbers } from './pagination'
import { platformInfo, regionLabel } from './platforms'
import { safeRedirect } from './redirect'

describe('pageNumbers', () => {
  it('lists every page when there are few', () => {
    expect(pageNumbers(1, 3)).toEqual([1, 2, 3])
  })

  it('shows first, last and the neighbours of the current page with gaps', () => {
    expect(pageNumbers(5, 10)).toEqual([1, '…', 4, 5, 6, '…', 10])
  })

  it('has no gap next to the edges', () => {
    expect(pageNumbers(1, 10)).toEqual([1, 2, '…', 10])
    expect(pageNumbers(10, 10)).toEqual([1, '…', 9, 10])
  })
})

describe('safeRedirect', () => {
  it('allows paths on this site', () => {
    expect(safeRedirect('/checkout')).toBe('/checkout')
    expect(safeRedirect('/store?page=2')).toBe('/store?page=2')
  })

  it('refuses other sites (open redirect) and falls back to the home page', () => {
    expect(safeRedirect('https://evil.example')).toBe('/')
    expect(safeRedirect('//evil.example')).toBe('/')
    expect(safeRedirect('javascript:alert(1)')).toBe('/')
    expect(safeRedirect(null)).toBe('/')
  })
})

describe('discountCents', () => {
  it('rounds down like the backend', () => {
    expect(discountCents(3998, 20)).toBe(799) // 799.6
    expect(discountCents(998, 20)).toBe(199) // 199.6
    expect(discountCents(1000, 10)).toBe(100)
    expect(discountCents(0, 50)).toBe(0)
  })
})

describe('store URL', () => {
  it('reads filters from the URL and writes them back unchanged', () => {
    const qs = 'search=elden&type=game%2Cdlc&platform=Steam%2CXbox&region=GLOBAL&min_price=5&max_price=30&sort=price_asc&page=2'
    const query = readStoreQuery(new URLSearchParams(qs))

    expect(query).toEqual({
      search: 'elden',
      types: ['game', 'dlc'],
      platforms: ['Steam', 'Xbox'],
      regions: ['GLOBAL'],
      minPrice: '5',
      maxPrice: '30',
      sort: 'price_asc',
      page: 2,
    })
    expect(storeQueryString(query)).toBe(qs)
  })

  it('ignores unknown values and bad page numbers', () => {
    const query = readStoreQuery(new URLSearchParams('sort=random&type=movies&page=-3'))
    expect(query.sort).toBe('popular')
    expect(query.types).toEqual([])
    expect(query.page).toBe(1)
  })

  it('leaves defaults out of links and API paths', () => {
    expect(storeLink({})).toBe('/store')
    expect(storeLink({ platforms: ['Xbox'], page: 1 })).toBe('/store?platform=Xbox')
    expect(productsPath({ sort: 'discount', perPage: 6 })).toBe('/products?sort=discount&per_page=6')
  })

  it('treats undefined like "not given" (homepage "See all" links pass optional values)', () => {
    expect(storeLink({ sort: undefined, maxPrice: '10', types: [] })).toBe('/store?max_price=10')
  })
})

describe('format', () => {
  it('formats cents as euros', () => {
    expect(formatPrice(1999)).toBe('€19.99')
    expect(formatPrice(5)).toBe('€0.05')
  })

  it('pluralises', () => {
    expect(plural(1, 'key')).toBe('1 key')
    expect(plural(3, 'key')).toBe('3 keys')
  })
})

describe('platforms', () => {
  it('describes where a key can be activated', () => {
    expect(regionLabel('GLOBAL')).toBe('Can be activated worldwide')
    expect(regionLabel('EUROPE')).toBe('Can be activated in Europe')
  })

  it('knows which platforms are PC (they show system requirements)', () => {
    expect(platformInfo('Steam').isPc).toBe(true)
    expect(platformInfo('Xbox').isPc).toBe(false)
    expect(platformInfo('Unknown').activation.length).toBeGreaterThan(0)
  })
})
