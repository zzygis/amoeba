import { Link } from 'react-router-dom'
import { useDocumentTitle } from '../hooks/useDocumentTitle'

export default function NotFoundPage() {
  useDocumentTitle('Page not found')

  return (
    <div style={{ textAlign: 'center', padding: 'var(--space-8) 0' }}>
      <h1>Page not found</h1>
      <p style={{ color: 'var(--color-text-muted)' }}>This page doesn't exist.</p>
      <Link to="/">Back to all games</Link>
    </div>
  )
}
