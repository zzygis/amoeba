import { useState } from 'react'
import { Link, useLocation, useParams } from 'react-router-dom'
import CheckoutSteps from '../components/CheckoutSteps'
import { useApi } from '../hooks/useApi'
import { useDocumentTitle } from '../hooks/useDocumentTitle'
import { formatDate, formatPrice, plural } from '../lib/format'
import { platformInfo } from '../lib/platforms'
import type { Order, OrderItem, Wrapped } from '../types'
import NotFoundPage from './NotFoundPage'
import styles from './Checkout.module.css'

// Step 3 of checkout ("Get your product"), and the page for any past order: shows the keys.
export default function OrderPage() {
  const { id = '' } = useParams()
  const { state } = useLocation()
  const justPaid = Boolean((state as { justPaid?: boolean } | null)?.justPaid)
  const { data, error } = useApi<Wrapped<Order>>(`/orders/${encodeURIComponent(id)}`)
  const order = data?.data
  useDocumentTitle(order ? `Order #${order.id}` : 'Order')

  if (error?.status === 404) return <NotFoundPage />
  if (error) {
    return (
      <p className="alert" role="alert">
        {error.message}
      </p>
    )
  }
  if (!order) return <p>Loading…</p>

  return (
    <>
      {justPaid && <CheckoutSteps current={3} />}

      {justPaid ? (
        <div className={styles.success} role="status">
          <h1>Thank you! Your order is complete.</h1>
          <p>Your keys are below. You can find them again any time under “My orders”.</p>
        </div>
      ) : (
        <p>
          <Link to="/orders">← My orders</Link>
        </p>
      )}

      <div className={styles.layout}>
        <section className={styles.panel}>
          <h2>{justPaid ? 'Your keys' : `Order #${order.id}`}</h2>
          <ul className={styles.lines}>
            {order.items.map((item) => (
              <OrderItemRow key={item.id} item={item} />
            ))}
          </ul>
        </section>

        <aside className={`${styles.panel} ${styles.summary}`} aria-label="Order summary">
          <h2>Order #{order.id}</h2>
          <div className={styles.summaryRow}>
            <span className={styles.muted}>Date</span>
            <span>{formatDate(order.created_at)}</span>
          </div>
          <div className={styles.summaryRow}>
            <span className={styles.muted}>Paid with</span>
            <span>{order.payment_method === 'paypal' ? 'PayPal' : 'Card'} (demo)</span>
          </div>
          {order.discount_cents > 0 && (
            <>
              <div className={styles.summaryRow}>
                <span className={styles.muted}>Subtotal</span>
                <span>{formatPrice(order.subtotal_cents)}</span>
              </div>
              <div className={styles.summaryRow}>
                <span className={styles.muted}>Discount ({order.coupon_code})</span>
                <span className={styles.good}>-{formatPrice(order.discount_cents)}</span>
              </div>
            </>
          )}
          <div className={styles.totalRow}>
            <span>Total:</span>
            <strong>{formatPrice(order.total_cents)}</strong>
          </div>
          <Link to="/" className="btn btn-primary btn-block">
            Continue shopping
          </Link>
        </aside>
      </div>
    </>
  )
}

function OrderItemRow({ item }: { item: OrderItem }) {
  return (
    <li className={styles.line} style={{ gridTemplateColumns: '64px minmax(0, 1fr)' }}>
      <img src={item.cover_url} alt="" width={64} height={96} />
      <div className={styles.lineInfo}>
        <Link to={`/games/${item.product_slug}`}>{item.name}</Link>
        <span className={styles.muted}>
          {plural(item.quantity, 'key')} × {formatPrice(item.price_cents)} · sold by {item.seller_name}
        </span>
        <span className={styles.muted}>
          Redeem on {item.platform} ({platformInfo(item.platform).worksOn})
        </span>
        <ul className={styles.keys} aria-label={`Keys for ${item.name}`}>
          {item.keys?.map((code) => (
            <KeyRow key={code} code={code} />
          ))}
        </ul>
      </div>
    </li>
  )
}

function KeyRow({ code }: { code: string }) {
  const [copied, setCopied] = useState(false)

  async function copy() {
    try {
      await navigator.clipboard.writeText(code)
      setCopied(true)
      setTimeout(() => setCopied(false), 2000)
    } catch {
      // Clipboard blocked: the code is still visible and selectable.
    }
  }

  return (
    <li className={styles.key}>
      <code>{code}</code>
      <button type="button" className={`btn btn-outline ${styles.copy}`} onClick={copy}>
        {copied ? 'Copied!' : 'Copy'}
      </button>
    </li>
  )
}
