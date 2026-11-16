import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { productsPath, readStoreQuery, SORT_LABELS, storeQueryString, TYPE_LABELS, type StoreQuery } from '../api/products'
import Pagination from '../components/Pagination'
import ProductCard, { ProductCardSkeleton } from '../components/ProductCard'
import StoreFilters from '../components/StoreFilters'
import { useApi } from '../hooks/useApi'
import { useDocumentTitle } from '../hooks/useDocumentTitle'
import type { FilterOptions, Paginated, Product, SortOption, Wrapped } from '../types'
import styles from './StorePage.module.css'

// "/store": search results with filters, sorting and pages, like eneba.com/store.
// Everything lives in the URL: /store?search=elden&platform=Steam&sort=price_asc&page=2
export default function StorePage() {
  const [params, setParams] = useSearchParams()
  const query = readStoreQuery(params)
  const [showFilters, setShowFilters] = useState(false) // only matters on narrow screens

  const { data, error, loading } = useApi<Paginated<Product>>(productsPath(query))
  const { data: options } = useApi<Wrapped<FilterOptions>>('/products/filters')
  useDocumentTitle(query.search ? `Search: ${query.search}` : 'Store')

  // Any filter change goes back to page 1.
  function update(changes: Partial<StoreQuery>) {
    setParams(storeQueryString({ ...query, page: 1, ...changes }))
  }

  const pageLink = (page: number) => `/store?${storeQueryString({ ...query, page })}`

  // Chips for the active filters, each removable, plus "Clear all".
  const chips: { label: string; remove: Partial<StoreQuery> }[] = [
    ...(query.search ? [{ label: `Text: ${query.search}`, remove: { search: '' } }] : []),
    ...query.types.map((t) => ({ label: TYPE_LABELS[t], remove: { types: query.types.filter((x) => x !== t) } })),
    ...query.platforms.map((p) => ({ label: p, remove: { platforms: query.platforms.filter((x) => x !== p) } })),
    ...query.regions.map((r) => ({ label: r, remove: { regions: query.regions.filter((x) => x !== r) } })),
    ...(query.minPrice ? [{ label: `From €${query.minPrice}`, remove: { minPrice: '' } }] : []),
    ...(query.maxPrice ? [{ label: `Up to €${query.maxPrice}`, remove: { maxPrice: '' } }] : []),
  ]

  return (
    <div className={styles.layout}>
      <aside className={`${styles.sidebar} ${showFilters ? styles.open : ''}`} aria-label="Filters">
        <StoreFilters options={options?.data ?? null} query={query} onChange={update} />
      </aside>

      <div>
        <div className={styles.heading}>
          <h1>{query.search ? `Results for “${query.search}”` : 'All products'}</h1>
          <div className={styles.controls}>
            <button
              type="button"
              className={`btn btn-outline ${styles.filterToggle}`}
              aria-expanded={showFilters}
              onClick={() => setShowFilters((open) => !open)}
            >
              Filters{chips.length > 0 && ` (${chips.length})`}
            </button>
            <label className={styles.sort}>
              <span className="visually-hidden">Sort by</span>
              <select value={query.sort} onChange={(e) => update({ sort: e.target.value as SortOption })}>
                {Object.entries(SORT_LABELS).map(([value, label]) => (
                  <option key={value} value={value}>
                    {label}
                  </option>
                ))}
              </select>
            </label>
          </div>
        </div>

        <div className={styles.chipsRow}>
          {chips.map((chip) => (
            <button key={chip.label} type="button" className={styles.chip} onClick={() => update(chip.remove)}>
              {chip.label} <span aria-hidden="true">×</span>
              <span className="visually-hidden">(remove filter)</span>
            </button>
          ))}
          {chips.length > 0 && (
            <Link to="/store" className={styles.clearAll}>
              Clear all
            </Link>
          )}
          {data && (
            <p className={styles.count} aria-live="polite">
              Results found: <strong>{data.meta.total}</strong>
            </p>
          )}
        </div>

        {error && (
          <p className="alert" role="alert">
            {error.message}
          </p>
        )}

        {!data && loading && (
          <div className={styles.grid}>
            {Array.from({ length: 8 }, (_, i) => (
              <ProductCardSkeleton key={i} />
            ))}
          </div>
        )}

        {data && data.data.length === 0 && (
          <div className={styles.empty}>
            <p>No games match these filters.</p>
            <Link to="/store" className="btn btn-primary">
              Show all games
            </Link>
          </div>
        )}

        {data && data.data.length > 0 && (
          <>
            {/* While the next page loads, keep the old results visible but dimmed. */}
            <ul className={styles.grid} style={{ opacity: loading ? 0.5 : 1 }} aria-busy={loading}>
              {data.data.map((product) => (
                <li key={product.id}>
                  <ProductCard product={product} />
                </li>
              ))}
            </ul>
            <Pagination current={data.meta.current_page} last={data.meta.last_page} linkTo={pageLink} />
          </>
        )}
      </div>
    </div>
  )
}
