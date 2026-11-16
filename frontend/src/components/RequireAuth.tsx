import type { ReactNode } from 'react'
import { Navigate, useLocation } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'

// Wrap pages that need an account. Guests are sent to /login and brought back afterwards.
export default function RequireAuth({ children }: { children: ReactNode }) {
  const { user, ready } = useAuth()
  const location = useLocation()

  if (!ready) return <p>Loading…</p>
  if (!user) {
    const back = location.pathname + location.search
    return <Navigate to={`/login?redirect=${encodeURIComponent(back)}`} replace />
  }
  return children
}
