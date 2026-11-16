import { Link } from 'react-router-dom'
import { storeLink } from '../api/products'
import Price from '../components/Price'
import ProductCard, { ProductCardSkeleton } from '../components/ProductCard'
import { useApi } from '../hooks/useApi'
import { useDocumentTitle } from '../hooks/useDocumentTitle'
import { plural } from '../lib/format'
import type { HomeBlock } from '../types'
import styles from './HomePage.module.css'

// "/": the storefront. Nothing about the content is decided here: GET /home returns the
// blocks (which ones, their titles, order and products) from the backend's home_blocks
// table, and this page only knows how to draw each block type.
export default function HomePage() {
  useDocumentTitle()
  const { data, error } = useApi<{ data: HomeBlock[] }>('/home')

  if (error) {
    return (
      <p className="alert" role="alert">
        {error.message}
      </p>
    )
  }
  if (!data) return <HomeSkeleton />

  return (
    <>
      {data.data.map((block) => {
        switch (block.type) {
          case 'banner':
            return <BannerBlock key={block.id} block={block} />
          case 'platforms':
            return <PlatformsBlock key={block.id} block={block} />
          case 'product_row':
            return <ProductRowBlock key={block.id} block={block} />
          default:
            return null // a block type this version of the site doesn't know yet
        }
      })}
    </>
  )
}

type BlockOf<T extends HomeBlock['type']> = Extract<HomeBlock, { type: T }>

function BannerBlock({ block }: { block: BlockOf<'banner'> }) {
  const product = block.content.product
  if (!product || product.price_cents === null) return null

  return (
    <section className={styles.hero} aria-label={block.title}>
      <img src={product.cover_url} alt="" className={styles.heroCover} width={600} height={900} />
      <div className={styles.heroText}>
        <p className={styles.kicker}>{block.title}</p>
        <h1>{product.title}</h1>
        <p className={styles.heroMeta}>
          {product.platform} key · {product.region === 'GLOBAL' ? 'Works worldwide' : product.region}
        </p>
        <Price {...product} price_cents={product.price_cents} size="lg" />
        <Link to={`/games/${product.slug}`} className="btn btn-primary">
          Get the deal
        </Link>
      </div>
    </section>
  )
}

function PlatformsBlock({ block }: { block: BlockOf<'platforms'> }) {
  return (
    <nav className={styles.platforms} aria-label={block.title}>
      {block.content.platforms.map((platform) => (
        <Link key={platform.name} to={storeLink({ platforms: [platform.name] })} className={styles.platform}>
          <strong>{platform.name}</strong>
          <span>{plural(platform.count, 'product')}</span>
        </Link>
      ))}
    </nav>
  )
}

function ProductRowBlock({ block }: { block: BlockOf<'product_row'> }) {
  const { products, see_all: seeAll } = block.content
  if (products.length === 0) return null

  const link = storeLink({
    sort: seeAll.sort,
    maxPrice: seeAll.max_price !== undefined ? String(seeAll.max_price) : '',
    types: seeAll.type ? [seeAll.type] : [],
  })

  return (
    <section className={styles.row}>
      <div className={styles.rowHeading}>
        <h2>{block.title}</h2>
        <Link to={link} className={styles.seeAll}>
          See all
        </Link>
      </div>
      <ul className={styles.rowGrid}>
        {products.map((product) => (
          <li key={product.id}>
            <ProductCard product={product} />
          </li>
        ))}
      </ul>
    </section>
  )
}

// Placeholder rows while /home loads.
function HomeSkeleton() {
  return (
    <section className={styles.row} aria-busy="true">
      <ul className={styles.rowGrid}>
        {Array.from({ length: 6 }, (_, i) => (
          <li key={i}>
            <ProductCardSkeleton />
          </li>
        ))}
      </ul>
    </section>
  )
}
