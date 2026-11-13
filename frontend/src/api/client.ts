// Small wrapper around fetch() for talking to the Laravel API.
// Every request goes through here, so the base URL, JSON headers and
// error handling live in one place. (The bearer token is added in the auth step.)

const API_URL = import.meta.env.VITE_API_URL

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

export async function apiRequest<T>(path: string, options: RequestInit = {}): Promise<T> {
  let response: Response
  try {
    response = await fetch(`${API_URL}${path}`, {
      ...options,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        ...options.headers,
      },
    })
  } catch {
    // fetch only throws when the server can't be reached at all (offline, CORS, DNS).
    throw new ApiError(0, 'Could not reach the server. Please try again.')
  }

  // 204 No Content has no body to parse.
  const body = response.status === 204 ? null : await response.json().catch(() => null)

  if (!response.ok) {
    throw new ApiError(response.status, body?.message ?? 'Something went wrong.', body?.errors)
  }

  return body as T
}
