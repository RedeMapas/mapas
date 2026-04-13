const CONTROL_CHARS_RE = /[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/g
const MULTI_SPACE_RE = /\s+/g
const LEADING_TRAILING_QUOTES_RE = /^"+|"+$/g

export function sanitizeString(value: unknown, maxLen?: number): string | null {
  if (value == null) return null
  let s = String(value)
  s = s.replace(/<[^>]*>/g, '')
  s = s.replace(LEADING_TRAILING_QUOTES_RE, '')
  s = s.replace(CONTROL_CHARS_RE, '')
  s = s.replace(MULTI_SPACE_RE, ' ')
  s = s.trim()
  if (s.length === 0) return null
  if (maxLen && s.length > maxLen) s = s.slice(0, maxLen)
  return s
}

export function truncate(s: string | null, maxLen: number): string | null {
  if (!s) return null
  return s.length > maxLen ? s.slice(0, maxLen) : s
}

const SKIP_NAMES = new Set(['', 'sem nome'])

export function isSkippableName(name: unknown): boolean {
  if (!name || typeof name !== 'string') return true
  const trimmed = name.trim().toLowerCase()
  return trimmed.length === 0 || SKIP_NAMES.has(trimmed)
}
