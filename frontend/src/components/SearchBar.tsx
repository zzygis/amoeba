import { useEffect, useId, useRef, useState, type FormEvent, type KeyboardEvent } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { apiRequest } from '../api/client'
import { productsPath, storeLink } from '../api/products'
import { formatPrice } from '../lib/format'
import type { Paginated, Product } from '../types'
import styles from './SearchBar.module.css'

// Header search box with suggestions while typing. The URL (?search=...) stays the
// source of truth for the results page, so results can be shared and Back works.
export default function SearchBar() {
  const [params] = useSearchParams()
  const current = params.get('search') ?? ''

  // `key`: when the URL's search changes (back button, clicking the logo), React
  // re-creates the form, so the box starts again from the new URL value.
  return <SearchForm key={current} initial={current} />
}

function SearchForm({ initial }: { initial: string }) {
  const navigate = useNavigate()
  const listId = useId()
  const formRef = useRef<HTMLFormElement>(null)
  const [query, setQuery] = useState(initial)
  const [suggestions, setSuggestions] = useState<Product[]>([])
  const [open, setOpen] = useState(false)
  const [active, setActive] = useState(-1) // highlighted suggestion (keyboard)

  // Fetch suggestions 250 ms after the user stops typing (not on every key press).
  useEffect(() => {
    const term = query.trim()
    if (term.length < 2) return
    const controller = new AbortController()
    const timer = setTimeout(() => {
      apiRequest<Paginated<Product>>(productsPath({ search: term, perPage: 12 }), { signal: controller.signal })
        .then((response) => {
          // One entry per game (not "Elden Ring" three times for three platforms): its cheapest
          // listing, so "from €X" is true. A Map keeps the games in the API's popularity order.
          // Sold-out listings (no price) only count when nothing else of that game is for sale.
          const cheapest = new Map<string, Product>()
          const cents = (p: Product) => p.price_cents ?? Number.POSITIVE_INFINITY
          for (const product of response.data) {
            const seen = cheapest.get(product.title)
            if (!seen || cents(product) < cents(seen)) cheapest.set(product.title, product)
          }
          setSuggestions([...cheapest.values()].slice(0, 6))
          setActive(-1)
        })
        .catch(() => {})
    }, 250)
    return () => {
      clearTimeout(timer)
      controller.abort()
    }
  }, [query])

  const showList = open && query.trim().length >= 2 && suggestions.length > 0

  function go(path: string) {
    setOpen(false)
    // Going to a product page: empty the box (a store search keeps its text via the URL).
    if (path.startsWith('/games/')) setQuery('')
    navigate(path)
  }

  function handleSubmit(event: FormEvent) {
    event.preventDefault()
    if (active >= 0 && showList) return go(`/games/${suggestions[active].slug}`)
    const term = query.trim()
    go(term ? storeLink({ search: term }) : '/store')
  }

  // Arrow keys move through the list, Escape closes it (combobox pattern).
  function handleKeyDown(event: KeyboardEvent) {
    if (!showList) return
    if (event.key === 'ArrowDown') {
      event.preventDefault()
      setActive((i) => (i + 1) % suggestions.length)
    } else if (event.key === 'ArrowUp') {
      event.preventDefault()
      setActive((i) => (i <= 0 ? suggestions.length - 1 : i - 1))
    } else if (event.key === 'Escape') {
      setOpen(false)
    }
  }

  return (
    <form
      ref={formRef}
      role="search"
      className={styles.form}
      onSubmit={handleSubmit}
      // Close when focus leaves the whole search box (input + list).
      onBlur={(event) => !formRef.current?.contains(event.relatedTarget) && setOpen(false)}
    >
      <label htmlFor="search" className="visually-hidden">
        Search games
      </label>
      <input
        id="search"
        type="search"
        className={styles.input}
        placeholder="Search for games, e.g. Red Dead Redemption"
        value={query}
        maxLength={100}
        autoComplete="off"
        role="combobox"
        aria-expanded={showList}
        aria-controls={listId}
        aria-autocomplete="list"
        aria-activedescendant={showList && active >= 0 ? `${listId}-${active}` : undefined}
        onChange={(event) => {
          setQuery(event.target.value)
          setOpen(true)
        }}
        onFocus={() => setOpen(true)}
        onKeyDown={handleKeyDown}
      />
      <button type="submit" className={styles.button} aria-label="Search">
        <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true">
          <circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" strokeWidth="2.5" />
          <path d="M16.5 16.5L21 21" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" />
        </svg>
      </button>

      {showList && (
        <ul id={listId} role="listbox" className={styles.suggestions} aria-label="Suggestions">
          {suggestions.map((product, index) => (
            <li
              key={product.id}
              id={`${listId}-${index}`}
              role="option"
              aria-selected={index === active}
              className={index === active ? styles.active : ''}
              // mousedown (not click) fires before the input's blur closes the list.
              onMouseDown={(event) => {
                event.preventDefault()
                go(`/games/${product.slug}`)
              }}
              onMouseEnter={() => setActive(index)}
            >
              <img src={product.cover_url} alt="" width={36} height={54} />
              <span className={styles.suggestionName}>{product.title}</span>
              <span className={styles.suggestionPrice}>
                {product.price_cents === null ? 'Out of stock' : `from ${formatPrice(product.price_cents)}`}
              </span>
            </li>
          ))}
          <li
            role="option"
            aria-selected={false}
            className={styles.seeAll}
            onMouseDown={(event) => {
              event.preventDefault()
              go(storeLink({ search: query.trim() }))
            }}
          >
            See all results for “{query.trim()}”
          </li>
        </ul>
      )}
    </form>
  )
}
