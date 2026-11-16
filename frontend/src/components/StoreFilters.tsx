import { useState, type FormEvent } from 'react'
import { TYPE_LABELS, type StoreQuery } from '../api/products'
import { formatPrice } from '../lib/format'
import PlatformIcon from './PlatformIcon'
import type { FilterOptions, ProductType } from '../types'
import styles from './StoreFilters.module.css'

interface Props {
  options: FilterOptions | null
  query: StoreQuery
  onChange: (changes: Partial<StoreQuery>) => void
}

// Left sidebar of the store page: price range, platform and region checkboxes.
export default function StoreFilters({ options, query, onChange }: Props) {
  function toggle(list: string[], value: string): string[] {
    return list.includes(value) ? list.filter((item) => item !== value) : [...list, value]
  }

  return (
    <div className={styles.filters}>
      {/* key: when the URL's prices change (e.g. "Clear all"), the inputs start over from them. */}
      <PriceRange
        key={`${query.minPrice}-${query.maxPrice}`}
        min={query.minPrice}
        max={query.maxPrice}
        placeholderMax={options ? String(Math.ceil(options.price.max_cents / 100)) : ''}
        onApply={(minPrice, maxPrice) => onChange({ minPrice, maxPrice })}
      />

      <fieldset className={styles.group}>
        <legend>Product type</legend>
        {options?.types.map((type) => (
          <label key={type.name} className={styles.option}>
            <input
              type="checkbox"
              checked={query.types.includes(type.name)}
              onChange={() => onChange({ types: toggle(query.types, type.name) as ProductType[] })}
            />
            <span>{TYPE_LABELS[type.name]}</span>
            <span className={styles.count}>{type.count}</span>
          </label>
        ))}
      </fieldset>

      <fieldset className={styles.group}>
        <legend>Platforms</legend>
        {options?.platforms.map((platform) => (
          <label key={platform.name} className={styles.option}>
            <input
              type="checkbox"
              checked={query.platforms.includes(platform.name)}
              onChange={() => onChange({ platforms: toggle(query.platforms, platform.name) })}
            />
            <span className={styles.platformIcon}>
              <PlatformIcon platform={platform.name} />
            </span>
            <span>{platform.name}</span>
            <span className={styles.count}>{platform.count}</span>
          </label>
        ))}
      </fieldset>

      <fieldset className={styles.group}>
        <legend>Region</legend>
        {options?.regions.map((region) => (
          <label key={region.name} className={styles.option}>
            <input
              type="checkbox"
              checked={query.regions.includes(region.name)}
              onChange={() => onChange({ regions: toggle(query.regions, region.name) })}
            />
            <span>{region.name === 'GLOBAL' ? 'Global' : region.name.charAt(0) + region.name.slice(1).toLowerCase()}</span>
            <span className={styles.count}>{region.count}</span>
          </label>
        ))}
      </fieldset>

      {options && (
        <p className={styles.hint}>
          Prices from {formatPrice(options.price.min_cents)} to {formatPrice(options.price.max_cents)}
        </p>
      )}
    </div>
  )
}

function PriceRange(props: {
  min: string
  max: string
  placeholderMax: string
  onApply: (min: string, max: string) => void
}) {
  const [min, setMin] = useState(props.min)
  const [max, setMax] = useState(props.max)

  function apply(event: FormEvent) {
    event.preventDefault()
    props.onApply(min.trim(), max.trim())
  }

  return (
    <form className={styles.group} onSubmit={apply}>
      <fieldset className={styles.plain}>
        <legend>Price range (EUR)</legend>
        <div className={styles.priceRow}>
          <label className="visually-hidden" htmlFor="min-price">
            Minimum price
          </label>
          <input
            id="min-price"
            type="number"
            min={0}
            step="0.01"
            inputMode="decimal"
            placeholder="0"
            value={min}
            onChange={(e) => setMin(e.target.value)}
          />
          <span aria-hidden="true">–</span>
          <label className="visually-hidden" htmlFor="max-price">
            Maximum price
          </label>
          <input
            id="max-price"
            type="number"
            min={0}
            step="0.01"
            inputMode="decimal"
            placeholder={props.placeholderMax}
            value={max}
            onChange={(e) => setMax(e.target.value)}
          />
        </div>
        <button type="submit" className="btn btn-outline btn-block">
          Apply
        </button>
      </fieldset>
    </form>
  )
}
