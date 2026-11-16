import { useEffect, useState } from 'react'
import { apiRequest, ApiError } from '../api/client'
import type { HealthStatus } from '../types'

// Temporary deploy-check page: proves the frontend can reach the API
// and the API can reach the database. Replaced by the real homepage later.
export default function StatusPage() {
  const [health, setHealth] = useState<HealthStatus | null>(null)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    apiRequest<HealthStatus>('/health')
      .then(setHealth)
      .catch((e: ApiError) => setError(`${e.message} (HTTP ${e.status})`))
  }, [])

  return (
    <section>
      <h1>{import.meta.env.VITE_APP_NAME}</h1>
      <p>API: {import.meta.env.VITE_API_URL}</p>
      {!health && !error && <p>Checking API…</p>}
      {error && <p style={{ color: 'tomato' }}>API error: {error}</p>}
      {health && (
        <ul>
          <li>API status: {health.status}</li>
          <li>Database: {health.database}</li>
          <li>Server time: {health.time}</li>
        </ul>
      )}
    </section>
  )
}
