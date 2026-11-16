// Small wrapper around fetch() for talking to the Laravel API.
// Every request goes through here, so the base URL, JSON headers, the login
// token and error handling live in one place.

const API_URL = import.meta.env.VITE_API_URL
const TOKEN_KEY = 'auth_token'

// Thrown for any non-2xx response. Laravel errors look like
// { "message": "...", "errors": { "field": ["..."] } }.
export class ApiError extends Error {
  status: number
  errors: Record<string, string[]>

  constructor(status: number, message: string, errors: Record<string, string[]> = {}) {
    super(message)
    this.status = status
    this.errors = errors
  }
}

// The Sanctum token is kept in localStorage so a page refresh keeps you logged in.
// (Storage can throw in private mode or when blocked; then we simply aren't remembered.)
export function getToken(): string | null {
  try {
    return localStorage.getItem(TOKEN_KEY)
  } catch {
    return null
  }
}

export function setToken(token: string | null) {
  try {
    if (token) localStorage.setItem(TOKEN_KEY, token)
    else localStorage.removeItem(TOKEN_KEY)
  } catch {
    // ignore, see above
  }
}

// Called when the API says our token is no longer valid (401), so the app can log out.
let unauthorizedHandler: (() => void) | null = null
export function onUnauthorized(handler: () => void) {
  unauthorizedHandler = handler
}

export async function apiRequest<T>(path: string, options: RequestInit = {}): Promise<T> {
  const token = getToken()

  let response: Response
  try {
    response = await fetch(`${API_URL}${path}`, {
      ...options,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...options.headers,
      },
    })
  } catch (error) {
    // We cancelled it ourselves (e.g. the user navigated away): not a real failure.
    if (options.signal?.aborted) throw error
    // Otherwise fetch only throws when the server can't be reached at all (offline, CORS, DNS).
    throw new ApiError(0, 'Could not reach the server. Please try again.')
  }

  // 204 No Content has no body to parse.
  const body = response.status === 204 ? null : await response.json().catch(() => null)

  if (response.status === 401 && token) {
    setToken(null)
    unauthorizedHandler?.()
  }

  if (!response.ok) {
    const message = response.status === 429 ? 'Too many attempts. Please wait a minute and try again.' : body?.message
    throw new ApiError(response.status, message ?? 'Something went wrong.', body?.errors)
  }

  return body as T
}

// POST/PUT/DELETE with an optional JSON body.
export function apiSend<T>(method: 'POST' | 'PUT' | 'DELETE', path: string, data?: unknown): Promise<T> {
  return apiRequest<T>(path, { method, body: data === undefined ? undefined : JSON.stringify(data) })
}
