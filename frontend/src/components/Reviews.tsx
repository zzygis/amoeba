import { useCallback, useEffect, useState, type FormEvent } from 'react'
import { ApiError, apiRequest, apiSend } from '../api/client'
import { productPath } from '../api/products'
import { useAuth } from '../auth/useAuth'
import { formatDate, plural } from '../lib/format'
import type { Review, ReviewPage, Wrapped } from '../types'
import styles from './Reviews.module.css'

// Read-only star display, e.g. ★★★★☆ for 4. Screen readers get "4 out of 5 stars".
export function Stars({ rating }: { rating: number }) {
  const full = Math.round(rating)
  return (
    <span className={styles.stars} role="img" aria-label={`${rating} out of 5 stars`}>
      {'★'.repeat(full)}
      <span className={styles.starsEmpty}>{'★'.repeat(5 - full)}</span>
    </span>
  )
}

interface Props {
  slug: string
  average: number | null
  count: number
}

// The reviews section of a product page: summary, list with "Load more", and a
// form for customers who bought the game and haven't reviewed it yet.
export default function Reviews({ slug, average, count }: Props) {
  const { user } = useAuth()
  const [reviews, setReviews] = useState<Review[]>([])
  const [page, setPage] = useState(1)
  const [lastPage, setLastPage] = useState(1)
  const [canReview, setCanReview] = useState(false)
  const [total, setTotal] = useState(count)
  const [summary, setSummary] = useState(average)

  const fetchPage = useCallback(
    (pageToLoad: number) => apiRequest<ReviewPage>(`${productPath(slug)}/reviews?page=${pageToLoad}`),
    [slug],
  )

  function show(response: ReviewPage) {
    const first = response.meta.current_page === 1
    setReviews((current) => (first ? response.data : [...current, ...response.data]))
    setPage(response.meta.current_page)
    setLastPage(response.meta.last_page)
    setTotal(response.meta.total)
    setCanReview(response.can_review)
  }

  // Load the first page, and again after logging in/out (can_review depends on who you are).
  useEffect(() => {
    let cancelled = false
    fetchPage(1)
      .then((response) => !cancelled && show(response))
      .catch(() => {})
    return () => {
      cancelled = true
    }
  }, [fetchPage, user?.id])

  function added(review: Review) {
    setReviews((current) => [review, ...current])
    setSummary((previous) => ((previous ?? 0) * total + review.rating) / (total + 1))
    setTotal((previous) => previous + 1)
    setCanReview(false)
  }

  return (
    <section id="reviews" className={styles.reviews}>
      <h2>Reviews</h2>
      <p className={styles.summary}>
        {total > 0 && summary !== null ? (
          <>
            <Stars rating={Math.round(summary * 10) / 10} /> <strong>{summary.toFixed(1)}</strong> / 5 from{' '}
            {plural(total, 'review')}
          </>
        ) : (
          'No reviews yet.'
        )}
      </p>

      {canReview && <ReviewForm slug={slug} onAdded={added} />}
      {!user && <p className={styles.muted}>Bought this game? Log in to write a review.</p>}

      <ul className={styles.list}>
        {reviews.map((review) => (
          <li key={review.id} className={styles.review}>
            <div className={styles.reviewHead}>
              <strong>{review.author}</strong>
              <Stars rating={review.rating} />
              <span className={styles.muted}>{formatDate(review.created_at)}</span>
            </div>
            <p>{review.body}</p>
          </li>
        ))}
      </ul>

      {page < lastPage && (
        <button type="button" className="btn btn-outline" onClick={() => fetchPage(page + 1).then(show).catch(() => {})}>
          Load more reviews
        </button>
      )}
    </section>
  )
}

function ReviewForm({ slug, onAdded }: { slug: string; onAdded: (review: Review) => void }) {
  const [rating, setRating] = useState(5)
  const [body, setBody] = useState('')
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [busy, setBusy] = useState(false)

  async function submit(event: FormEvent) {
    event.preventDefault()
    setBusy(true)
    setErrors({})
    try {
      const response = await apiSend<Wrapped<Review>>('POST', `${productPath(slug)}/reviews`, { rating, body })
      onAdded(response.data)
    } catch (e) {
      setErrors(e instanceof ApiError ? { body: [e.message], ...e.errors } : { body: ['Could not save your review.'] })
      setBusy(false)
    }
  }

  return (
    <form className={styles.form} onSubmit={submit}>
      <h3>Write a review</h3>
      <fieldset className={styles.ratingInput}>
        <legend>Your rating</legend>
        {[1, 2, 3, 4, 5].map((value) => (
          <label key={value} className={value <= rating ? styles.on : ''}>
            <input
              type="radio"
              name="rating"
              value={value}
              checked={rating === value}
              onChange={() => setRating(value)}
              className="visually-hidden"
            />
            <span aria-hidden="true">★</span>
            <span className="visually-hidden">{plural(value, 'star')}</span>
          </label>
        ))}
      </fieldset>
      <div className="field">
        <label htmlFor="review-body">Your review</label>
        <textarea
          id="review-body"
          rows={4}
          maxLength={2000}
          value={body}
          onChange={(e) => setBody(e.target.value)}
          aria-invalid={Boolean(errors.body)}
          aria-describedby={errors.body ? 'review-error' : undefined}
        />
        {errors.body && (
          <p id="review-error" className="field-error">
            {errors.body[0]}
          </p>
        )}
      </div>
      <button type="submit" className="btn btn-primary" disabled={busy}>
        {busy ? 'Posting…' : 'Post review'}
      </button>
    </form>
  )
}
