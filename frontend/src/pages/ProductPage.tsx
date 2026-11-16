import { useRef, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { productPath } from '../api/products'
import Price from '../components/Price'
import ProductCard from '../components/ProductCard'
import SellerAvatar from '../components/SellerAvatar'
import Reviews, { Stars } from '../components/Reviews'
import TrailerPlayer from '../components/TrailerPlayer'
import WishlistButton from '../components/WishlistButton'
import { useAddToCart } from '../hooks/useAddToCart'
import { useApi } from '../hooks/useApi'
import { useDocumentTitle } from '../hooks/useDocumentTitle'
import { formatDate, formatPrice } from '../lib/format'
import { platformInfo, regionLabel } from '../lib/platforms'
import type { Offer, Product, ProductDetail, Wrapped } from '../types'
import NotFoundPage from './NotFoundPage'
import styles from './ProductPage.module.css'

// "/games/:slug": one product listing, laid out like an eneba.com product page.
export default function ProductPage() {
  const { slug = '' } = useParams()
  const { data, error, loading } = useApi<Wrapped<ProductDetail>>(productPath(slug))
  // While switching between listings, don't show the previous product's data.
  const product = loading ? undefined : data?.data
  useDocumentTitle(product?.name)

  if (error?.status === 404) return <NotFoundPage />
  if (error) {
    return (
      <p className="alert" role="alert">
        {error.message}
      </p>
    )
  }
  if (!product) return <p className={styles.muted}>Loading…</p>

  return <ProductView key={product.id} product={product} />
}

function ProductView({ product }: { product: ProductDetail }) {
  const navigate = useNavigate()
  const { addToCart, adding, error } = useAddToCart()
  const platform = platformInfo(product.platform)
  const { game } = product
  // The featured offer: the cheapest seller that still has keys (the API sorts offers that way).
  const best = product.offers.find((offer) => offer.stock > 0) ?? null
  const otherSellers = product.offers.filter((offer) => offer.stock > 0).length - 1

  async function buyNow(offer: Offer) {
    const ok = await addToCart({ offerId: offer.id, name: product.name, seller: offer.seller.name }, { showDialog: false })
    if (ok) navigate('/cart')
  }

  return (
    <article>
      <nav className={styles.breadcrumbs} aria-label="Breadcrumb">
        <Link to="/store">Store</Link>
        <span aria-hidden="true">›</span>
        <Link to={`/store?search=${encodeURIComponent(product.title)}`}>{product.title}</Link>
        <span aria-hidden="true">›</span>
        <span>{product.platform}</span>
      </nav>

      {/* ---------- Top: cover, key facts, price box ---------- */}
      <div className={styles.top}>
        <img
          className={`${styles.cover} ${product.cover_portrait ? '' : styles.coverWide}`}
          src={product.cover_url}
          alt={product.title}
          width={600}
          height={900}
        />

        <div className={styles.summary}>
          <h1 className={styles.title}>{product.name}</h1>
          {product.rating.count > 0 && product.rating.average !== null && (
            <a href="#reviews" className={styles.ratingLink}>
              <Stars rating={product.rating.average} /> {product.rating.average.toFixed(1)} ·{' '}
              {product.rating.count === 1 ? '1 review' : `${product.rating.count} reviews`}
            </a>
          )}

          <ul className={styles.facts}>
            <li>
              <strong>{product.region === 'GLOBAL' ? 'Global' : product.region}</strong>
              <span>{regionLabel(product.region)}</span>
            </li>
            <li>
              <strong>{product.platform}</strong>
              <span>Activate/redeem on {product.platform}</span>
            </li>
            <li>
              <strong>Digital key</strong>
              <span>This is a digital edition of the product (CD key)</span>
              <span className={styles.good}>Instant delivery</span>
            </li>
            <li>
              <strong>Works on</strong>
              <span>{platform.worksOn}</span>
            </li>
          </ul>
        </div>

        <aside className={styles.buyBox} aria-label="Featured offer">
          <p className={styles.kicker}>Featured offer</p>
          {best ? (
            <>
              <div className={styles.priceRow}>
                <Price
                  price_cents={best.price_cents}
                  original_price_cents={product.original_price_cents}
                  discount_percent={best.discount_percent}
                  size="lg"
                />
                <WishlistButton product={product} />
              </div>
              <SellerLine offer={best} />
              <p className={styles.good}>In stock{best.stock <= 5 && ` (only ${best.stock} left)`}</p>

              <div className={styles.buyButtons}>
                <button type="button" className="btn btn-primary" disabled={adding} onClick={() => buyNow(best)}>
                  Buy now
                </button>
                <button
                  type="button"
                  className="btn btn-outline"
                  disabled={adding}
                  onClick={() => addToCart({ offerId: best.id, name: product.name, seller: best.seller.name })}
                >
                  Add to cart
                </button>
              </div>
              {otherSellers > 0 && (
                <a href="#offers" className={styles.moreOffers}>
                  +{otherSellers} more {otherSellers === 1 ? 'offer' : 'offers'} from other sellers
                </a>
              )}
            </>
          ) : (
            <>
              <div className={styles.priceRow}>
                <p className={styles.soldOut}>Out of stock</p>
                <WishlistButton product={product} />
              </div>
              <p className={styles.small}>Every seller has sold out. Save it to your wishlist to find it again.</p>
            </>
          )}
          {error && (
            <p className="field-error" role="alert">
              {error}
            </p>
          )}
          <p className={styles.small}>Demo store: no real payment is taken.</p>
        </aside>
      </div>

      {game.trailer && (
        <section className={styles.section} aria-label="Trailer">
          <div className={styles.trailer}>
            {/* Steam's trailer thumbnails are tiny; the first full-size screenshot makes a sharper poster. */}
            <TrailerPlayer trailer={game.trailer} poster={game.screenshots[0] ?? game.trailer.poster} />
          </div>
        </section>
      )}

      {game.screenshots.length > 0 && <Screenshots urls={game.screenshots} title={product.title} />}

      {/* ---------- Every seller's offer, to compare and choose ---------- */}
      <section id="offers" className={styles.section}>
        <h2>Recommended offers</h2>
        <p className={styles.muted}>
          {product.offers.length === 1
            ? 'Sold by 1 seller.'
            : `Compare ${product.offers.length} sellers: cheapest first. Every key is delivered instantly.`}
        </p>
        <ul className={styles.offers}>
          {product.offers.map((offer) => (
            <OfferRow
              key={offer.id}
              offer={offer}
              featured={offer.id === best?.id}
              listPrice={product.original_price_cents}
              onBuy={() => buyNow(offer)}
              onAdd={() => addToCart({ offerId: offer.id, name: product.name, seller: offer.seller.name })}
              busy={adding}
            />
          ))}
        </ul>
      </section>

      {/* ---------- Same game on other platforms/regions ---------- */}
      {product.other_listings.length > 0 && (
        <section className={styles.section}>
          <h2>Other platforms and regions</h2>
          <ul className={styles.offers}>
            {product.other_listings.map((other) => (
              <li key={other.id} className={styles.offer}>
                <div>
                  <strong>{other.name}</strong>
                  <span className={styles.muted}>{platformInfo(other.platform).worksOn}</span>
                </div>
                <div className={styles.offerPrice}>
                  {other.price_cents === null ? (
                    <span className={styles.soldOut}>Out of stock</span>
                  ) : (
                    <>
                      {other.discount_percent !== null && <span className={styles.good}>-{other.discount_percent}%</span>}
                      <span className={styles.muted}>from</span>
                      <strong>{formatPrice(other.price_cents)}</strong>
                    </>
                  )}
                  <Link to={`/games/${other.slug}`} className="btn btn-outline">
                    View
                  </Link>
                </div>
              </li>
            ))}
          </ul>
        </section>
      )}

      {/* ---------- Product details ---------- */}
      <section className={styles.section}>
        <h2>Product details</h2>
        <ul className={styles.tags}>
          {[...game.genres, ...game.modes].map((tag) => (
            <li key={tag}>{tag}</li>
          ))}
        </ul>

        <div className={styles.detailsGrid}>
          <div>
            <h3>Description</h3>
            {game.description.split('\n\n').map((paragraph) => (
              <p key={paragraph}>{paragraph}</p>
            ))}

            <h3>Activation guide</h3>
            <ol className={styles.steps}>
              {platform.activation.map((step) => (
                <li key={step}>{step}</li>
              ))}
            </ol>

            {platform.isPc && game.requirements.length > 0 && (
              <>
                <h3>Minimum system requirements</h3>
                <dl className={styles.table}>
                  {game.requirements.map(([label, value]) => (
                    <div key={label}>
                      <dt>{label}</dt>
                      <dd>{value}</dd>
                    </div>
                  ))}
                </dl>
              </>
            )}
          </div>

          <div>
            <h3>Other details</h3>
            <dl className={styles.table}>
              {game.release_date && (
                <div>
                  <dt>Release date</dt>
                  <dd>{formatDate(game.release_date)}</dd>
                </div>
              )}
              <div>
                <dt>Developer</dt>
                <dd>{game.developer}</dd>
              </div>
              <div>
                <dt>Publisher</dt>
                <dd>{game.publisher}</dd>
              </div>
              <div>
                <dt>Works on</dt>
                <dd>{platform.worksOn}</dd>
              </div>
              <div>
                <dt>Languages</dt>
                <dd>{game.languages.join(', ')}</dd>
              </div>
            </dl>
          </div>
        </div>
      </section>

      <SimilarProducts slug={product.slug} />

      <Reviews slug={product.slug} average={product.rating.average} count={product.rating.count} />
    </article>
  )
}

// "(K) Sold by KeyCrate ✓ · 9.92 Superb"
function SellerLine({ offer }: { offer: Offer }) {
  return (
    <p className={styles.seller}>
      <SellerAvatar name={offer.seller.name} imageUrl={offer.seller.avatar_url} size={40} />
      <span className={styles.muted}>Sold by</span> <strong>{offer.seller.name}</strong>
      {offer.seller.verified && (
        <span className={styles.verified} title="Verified seller">
          ✓ Verified
        </span>
      )}
      <span className={styles.rating}>
        {offer.seller.rating.toFixed(2)} {offer.seller.rating_label}
      </span>
    </p>
  )
}

// One seller's offer in "Recommended offers".
function OfferRow(props: {
  offer: Offer
  featured: boolean
  listPrice: number | null
  busy: boolean
  onBuy: () => void
  onAdd: () => void
}) {
  const { offer } = props
  const soldOut = offer.stock === 0

  return (
    <li className={`${styles.offer} ${props.featured ? styles.featuredOffer : ''}`}>
      <div>
        <SellerLine offer={offer} />
        <span className={soldOut ? styles.soldOut : styles.muted}>
          {soldOut ? 'Sold out' : `${offer.stock} ${offer.stock === 1 ? 'key' : 'keys'} available`}
        </span>
      </div>
      <div className={styles.offerPrice}>
        <div className={styles.offerAmount}>
          {offer.discount_percent !== null && props.listPrice !== null && (
            <span className={styles.muted}>
              <s>{formatPrice(props.listPrice)}</s> <span className={styles.good}>-{offer.discount_percent}%</span>
            </span>
          )}
          <strong>{formatPrice(offer.price_cents)}</strong>
        </div>
        <button type="button" className="btn btn-primary" disabled={soldOut || props.busy} onClick={props.onBuy}>
          Buy now
        </button>
        <button
          type="button"
          className={`btn btn-outline ${styles.iconButton}`}
          disabled={soldOut || props.busy}
          onClick={props.onAdd}
          aria-label={`Add ${offer.seller.name}'s offer to cart`}
          title="Add to cart"
        >
          + Cart
        </button>
      </div>
    </li>
  )
}

// "Gamers also viewed": games sharing genres with this one (GET /products/{slug}/similar).
function SimilarProducts({ slug }: { slug: string }) {
  const { data } = useApi<{ data: Product[] }>(`${productPath(slug)}/similar`)
  if (!data || data.data.length === 0) return null

  return (
    <section className={styles.section}>
      <h2>Gamers also viewed</h2>
      <ul className={styles.similar}>
        {data.data.map((product) => (
          <li key={product.id}>
            <ProductCard product={product} />
          </li>
        ))}
      </ul>
    </section>
  )
}

// Horizontal strip of screenshots; clicking one opens it large in a dialog.
function Screenshots({ urls, title }: { urls: string[]; title: string }) {
  const dialogRef = useRef<HTMLDialogElement>(null)
  const [openIndex, setOpenIndex] = useState(0)

  function open(index: number) {
    setOpenIndex(index)
    dialogRef.current?.showModal()
  }

  function step(delta: number) {
    setOpenIndex((index) => (index + delta + urls.length) % urls.length)
  }

  return (
    <section className={styles.section} aria-label="Screenshots">
      <ul className={styles.shots}>
        {urls.map((url, index) => (
          <li key={url}>
            <button type="button" onClick={() => open(index)} aria-label={`Open screenshot ${index + 1}`}>
              <img src={url} alt="" loading="lazy" width={1920} height={1080} />
            </button>
          </li>
        ))}
      </ul>

      <dialog
        ref={dialogRef}
        className={styles.lightbox}
        aria-label={`${title} screenshots`}
        onClick={(event) => event.target === dialogRef.current && dialogRef.current?.close()}
        onKeyDown={(event) => {
          if (event.key === 'ArrowRight') step(1)
          if (event.key === 'ArrowLeft') step(-1)
        }}
      >
        <img src={urls[openIndex]} alt={`${title} screenshot ${openIndex + 1} of ${urls.length}`} />
        <div className={styles.lightboxBar}>
          <button type="button" className="btn btn-outline" onClick={() => step(-1)} aria-label="Previous screenshot">
            ‹
          </button>
          <span>
            {openIndex + 1} / {urls.length}
          </span>
          <button type="button" className="btn btn-outline" onClick={() => step(1)} aria-label="Next screenshot">
            ›
          </button>
          <button type="button" className="btn btn-primary" onClick={() => dialogRef.current?.close()}>
            Close
          </button>
        </div>
      </dialog>
    </section>
  )
}
