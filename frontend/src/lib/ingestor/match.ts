import type { VenueCacheEntry, MatchResult, AutoApproveResult, ApprovableEvent, MatchStatus } from './types.ts'

// ── String normalization ─────────────────────────────────────────────────────

/**
 * Normalize a venue name for fuzzy comparison:
 * - Remove accents (NFD decompose → strip combining marks)
 * - Lowercase
 * - Remove non-alphanumeric characters (punctuation, parens, dashes)
 * - Collapse whitespace
 */
export function normalizeStr(s: string): string {
  return s
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')   // strip combining diacritics
    .toLowerCase()
    .replace(/[^a-z0-9\s]/g, ' ')      // non-alphanumeric → space
    .replace(/\s+/g, ' ')              // collapse whitespace
    .trim()
}

// ── Levenshtein distance ─────────────────────────────────────────────────────

export function levenshtein(a: string, b: string): number {
  if (a === b) return 0
  if (a.length === 0) return b.length
  if (b.length === 0) return a.length

  const prev = Array.from({ length: b.length + 1 }, (_, i) => i)
  const curr = new Array<number>(b.length + 1)

  for (let i = 1; i <= a.length; i++) {
    curr[0] = i
    for (let j = 1; j <= b.length; j++) {
      const cost = a[i - 1] === b[j - 1] ? 0 : 1
      curr[j] = Math.min(
        curr[j - 1] + 1,       // insertion
        prev[j] + 1,           // deletion
        prev[j - 1] + cost,    // substitution
      )
    }
    prev.splice(0, prev.length, ...curr)
  }

  return prev[b.length]!
}

/**
 * Compute match score 0–100 between two normalized venue name strings.
 * score = 100 - floor((distance / max(len_a, len_b)) * 100), clamped 0–100.
 * Empty strings return 0.
 */
export function scoreMatch(a: string, b: string): number {
  if (!a || !b) return 0
  const na = normalizeStr(a)
  const nb = normalizeStr(b)
  if (!na || !nb) return 0
  const dist = levenshtein(na, nb)
  const maxLen = Math.max(na.length, nb.length)
  return Math.max(0, Math.min(100, 100 - Math.floor((dist / maxLen) * 100)))
}

// ── Venue matching ───────────────────────────────────────────────────────────

const MATCH_THRESHOLD   = 90   // score ≥ 90  → matched
const SUGGEST_THRESHOLD = 75   // score 75–89 → suggested
                                // score < 75  → pending

/**
 * Find the best matching venue from venue_cache for a given raw venue name.
 * Returns the match result with status and best mapas_space_id.
 */
export function matchVenue(
  venueName: string | null,
  venueCache: VenueCacheEntry[],
): MatchResult {
  if (!venueName || venueCache.length === 0) {
    return { matchStatus: 'pending', mapasSpaceId: null, matchScore: 0 }
  }

  let bestScore = 0
  let bestEntry: VenueCacheEntry | null = null

  for (const entry of venueCache) {
    const score = scoreMatch(venueName, entry.name)
    if (score > bestScore) {
      bestScore = score
      bestEntry = entry
    }
  }

  const matchStatus: MatchStatus =
    bestScore >= MATCH_THRESHOLD   ? 'matched'
    : bestScore >= SUGGEST_THRESHOLD ? 'suggested'
    : 'pending'

  return {
    matchStatus,
    mapasSpaceId: matchStatus !== 'pending' && bestEntry ? bestEntry.mapasSpaceId : null,
    matchScore: bestScore,
  }
}

// ── Auto-approve ─────────────────────────────────────────────────────────────

// Required fields for auto-approval (beyond match_status='matched')
const REQUIRED_FIELDS: Array<keyof ApprovableEvent> = [
  'title',
  'startAt',
  'endAt',
  'mapasSpaceId',
  'descriptionShort',
]

/**
 * Shared auto-approve logic. Called from:
 * - sync-sympla.ts (after matching each event)
 * - PATCH /api/ingestor/events/[id] (when curator selects a space)
 *
 * Pure function — no DB side effects. Caller is responsible for persisting result.
 *
 * Auto-approves if: match_status = 'matched' AND all required fields present.
 * Suggested events are NEVER auto-approved (require curator decision).
 */
export function applyAutoApprove(event: ApprovableEvent): AutoApproveResult {
  if (event.matchStatus !== 'matched') {
    return { reviewStatus: 'pending', matchNote: null }
  }

  for (const field of REQUIRED_FIELDS) {
    if (event[field] == null || event[field] === '') {
      return {
        reviewStatus: 'pending',
        matchNote: `missing: ${String(field)}`,
      }
    }
  }

  return { reviewStatus: 'auto_approved', matchNote: null }
}
