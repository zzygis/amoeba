import { Link } from 'react-router-dom'
import { useApi } from '../hooks/useApi'
import { useDocumentTitle } from '../hooks/useDocumentTitle'
import { formatDate, formatPrice, plural } from '../lib/format'
import type { Order } from '../types'
import styles from './Checkout.module.css'

// "My orders": every order of the logged-in user, newest first.
export default function OrdersPage() {
  useDocumentTitle('My orders')
  const { data, error } = useApi<{ data: Order[] }>('/orders')

  if (error) {
    return (
      <p className="alert" role="alert">
        {error.message}
      </p>
    )
  }
  if (!data) return <p>Loading…</p>

  const orders = data.data

  return (
    <section>
      <h1>My orders</h1>
      {orders.length === 0 ? (
        <div className={styles.empty}>
          <p>You haven't bought anything yet.</p>
          <Link to="/" className="btn btn-primary">
            Browse games
          </Link>
        </div>
      ) : (
        <ul className={styles.lines}>
          {orders.map((order) => {
            const keyCount = order.items.reduce((sum, item) => sum + item.quantity, 0)
            return (
              <li key={order.id} className={styles.line}>
                <img src={order.items[0]?.cover_url} alt="" width={64} height={96} />
                <div className={styles.lineInfo}>
                  <Link to={`/orders/${order.id}`}>Order #{order.id}</Link>
                  <span className={styles.muted}>
                    {formatDate(order.created_at)} · {plural(keyCount, 'key')}
                  </span>
                  <span className={styles.muted}>{order.items.map((item) => item.name).join(', ')}</span>
                </div>
                <div className={styles.lineEnd}>
                  <span className={styles.lineTotal}>{formatPrice(order.total_cents)}</span>
                  <Link to={`/orders/${order.id}`} className="btn btn-outline">
                    View keys
                  </Link>
                </div>
              </li>
            )
          })}
        </ul>
      )}
    </section>
  )
}
