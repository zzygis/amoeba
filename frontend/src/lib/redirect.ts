// Where to go after logging in: the ?redirect= value, but only if it's a path on
// this site. "//evil.com" or "https://evil.com" would otherwise send users away (open redirect).
export function safeRedirect(value: string | null): string {
  if (value && value.startsWith('/') && !value.startsWith('//')) return value
  return '/'
}
