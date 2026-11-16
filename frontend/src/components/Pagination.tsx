import { Link } from 'react-router-dom'
import { pageNumbers } from '../lib/pagination'
import styles from './Pagination.module.css'

interface Props {
  current: number
  last: number
  linkTo: (page: number) => string
}

// Numbered pagination: ‹ 1 … 4 5 6 … 10 ›
export default function Pagination({ current, last, linkTo }: Props) {
  if (last <= 1) return null

  return (
    <nav className={styles.pagination} aria-label="Pagination">
      {current > 1 ? (
        <Link to={linkTo(current - 1)} className={styles.page} aria-label="Previous page">
          ‹
        </Link>
      ) : (
        <span className={`${styles.page} ${styles.disabled}`} aria-hidden="true">
          ‹
        </span>
      )}

      {pageNumbers(current, last).map((page, index) =>
        page === '…' ? (
          <span key={`gap-${index}`} className={styles.gap}>
            …
          </span>
        ) : (
          <Link
            key={page}
            to={linkTo(page)}
            className={`${styles.page} ${page === current ? styles.current : ''}`}
            aria-current={page === current ? 'page' : undefined}
          >
            {page}
          </Link>
        ),
      )}

      {current < last ? (
        <Link to={linkTo(current + 1)} className={styles.page} aria-label="Next page">
          ›
        </Link>
      ) : (
        <span className={`${styles.page} ${styles.disabled}`} aria-hidden="true">
          ›
        </span>
      )}
    </nav>
  )
}
