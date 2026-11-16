import { useEffect, useState } from 'react'
import { apiRequest, ApiError } from '../api/client'

interface ApiResult<T> {
  path: string | null // which request this result belongs to
  data: T | null
  error: ApiError | null
}

// Loads `path` from the API and re-loads whenever it changes.
// If the path changes before the previous request finishes, that request is
// cancelled, so a slow old response can never overwrite a newer one.
export function useApi<T>(path: string) {
  const [result, setResult] = useState<ApiResult<T>>({ path: null, data: null, error: null })

  useEffect(() => {
    const controller = new AbortController()

    apiRequest<T>(path, { signal: controller.signal })
      .then((data) => setResult({ path, data, error: null }))
      .catch((error: unknown) => {
        if (controller.signal.aborted) return
        const apiError = error instanceof ApiError ? error : new ApiError(0, 'Something went wrong.')
        setResult({ path, data: null, error: apiError })
      })

    return () => controller.abort()
  }, [path])

  // Still loading while the stored result is for a different (older) path.
  // The old data is kept meanwhile, so the page doesn't flash empty between pages.
  const loading = result.path !== path
  return { data: result.data, error: loading ? null : result.error, loading }
}
