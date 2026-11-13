// TypeScript shapes of the API responses. Keep these in sync with the
// Laravel API Resources in backend/app/Http/Resources.

export interface HealthStatus {
  status: 'ok' | 'degraded'
  app: string
  database: 'ok' | 'unreachable'
  time: string
}
