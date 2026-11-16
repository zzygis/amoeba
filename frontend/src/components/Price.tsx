import { formatPrice } from '../lib/format'
import styles from './Price.module.css'

interface Props {
  price_cents: number
  original_price_cents: number | null
  discount_percent: number | null
  size?: 'md' | 'lg'
}

// Current price, plus the crossed-out list price and "-75%" when discounted.
export default function Price({ price_cents, original_price_cents, discount_percent, size = 'md' }: Props) {
  return (
    <div className={styles.price}>
      {discount_percent !== null && original_price_cents !== null && (
        <div className={styles.was}>
          <s aria-label={`Original price ${formatPrice(original_price_cents)}`}>{formatPrice(original_price_cents)}</s>
          <span className={styles.discount}>-{discount_percent}%</span>
        </div>
      )}
      <div className={size === 'lg' ? styles.nowLarge : styles.now}>{formatPrice(price_cents)}</div>
    </div>
  )
}
