import { describe, it, expect } from 'bun:test'
import { normalizeStr, levenshtein, scoreMatch, matchVenue, applyAutoApprove } from './match.ts'
import type { VenueCacheEntry, ApprovableEvent } from './types.ts'

// ── normalizeStr ─────────────────────────────────────────────────────────────

describe('normalizeStr', () => {
  it('lowercases and removes accents', () => {
    expect(normalizeStr('Teatro Municipal')).toBe('teatro municipal')
    expect(normalizeStr('Cineteatro São Luiz')).toBe('cineteatro sao luiz')
    expect(normalizeStr('Centro Cultural Dragão do Mar')).toBe('centro cultural dragao do mar')
  })

  it('removes punctuation', () => {
    expect(normalizeStr('Teatro José de Alencar!')).toBe('teatro jose de alencar')
    expect(normalizeStr('Café & Bar')).toBe('cafe bar')
  })

  it('collapses whitespace', () => {
    expect(normalizeStr('  Teatro   Municipal  ')).toBe('teatro municipal')
  })

  it('handles empty string', () => {
    expect(normalizeStr('')).toBe('')
  })

  it('handles only punctuation', () => {
    expect(normalizeStr('!!!')).toBe('')
  })
})

// ── levenshtein ───────────────────────────────────────────────────────────────

describe('levenshtein', () => {
  it('returns 0 for identical strings', () => {
    expect(levenshtein('teatro', 'teatro')).toBe(0)
  })

  it('returns length of non-empty string when other is empty', () => {
    expect(levenshtein('', 'teatro')).toBe(6)
    expect(levenshtein('teatro', '')).toBe(6)
  })

  it('returns 1 for one-char difference', () => {
    expect(levenshtein('teatr', 'teatro')).toBe(1)
  })

  it('handles substitutions', () => {
    expect(levenshtein('cat', 'cut')).toBe(1)
    expect(levenshtein('cat', 'dog')).toBe(3)
  })
})

// ── scoreMatch ────────────────────────────────────────────────────────────────

describe('scoreMatch', () => {
  it('returns 100 for identical strings', () => {
    expect(scoreMatch('Teatro Municipal', 'Teatro Municipal')).toBe(100)
  })

  it('returns 100 for identical after normalization', () => {
    expect(scoreMatch('Teatro São Luiz', 'teatro sao luiz')).toBe(100)
  })

  it('returns 0 for empty strings', () => {
    expect(scoreMatch('', 'teatro')).toBe(0)
    expect(scoreMatch('teatro', '')).toBe(0)
    expect(scoreMatch('', '')).toBe(0)
  })

  it('returns high score for near-identical names', () => {
    // "Teatro José de Alencar" vs "Teatro Jose de Alencar" (accent diff only → identical after normalize)
    const score = scoreMatch('Teatro José de Alencar', 'Teatro Jose de Alencar')
    expect(score).toBe(100)
  })

  it('returns mid score for similar names', () => {
    // "Teatro José Alencar" vs "Teatro José de Alencar" (missing "de")
    const score = scoreMatch('Teatro José Alencar', 'Teatro José de Alencar')
    expect(score).toBeGreaterThan(70)
    expect(score).toBeLessThan(100)
  })

  it('returns low score for completely different names', () => {
    const score = scoreMatch('Teatro Municipal', 'Centro Cultural')
    expect(score).toBeLessThanOrEqual(50)
  })
})

// ── matchVenue ────────────────────────────────────────────────────────────────

const sampleCache: VenueCacheEntry[] = [
  { mapasSpaceId: 1, name: 'Cineteatro São Luiz', normalizedName: 'cineteatro sao luiz', city: 'Fortaleza', lat: -3.73, lng: -38.52 },
  { mapasSpaceId: 2, name: 'Centro Dragão do Mar de Arte e Cultura', normalizedName: 'centro dragao do mar de arte e cultura', city: 'Fortaleza', lat: -3.72, lng: -38.53 },
  { mapasSpaceId: 3, name: 'Teatro José de Alencar', normalizedName: 'teatro jose de alencar', city: 'Fortaleza', lat: -3.73, lng: -38.54 },
]

describe('matchVenue', () => {
  it('returns matched for exact name match', () => {
    const result = matchVenue('Cineteatro São Luiz', sampleCache)
    expect(result.matchStatus).toBe('matched')
    expect(result.mapasSpaceId).toBe(1)
    expect(result.matchScore).toBe(100)
  })

  it('returns pending for null venue name', () => {
    const result = matchVenue(null, sampleCache)
    expect(result.matchStatus).toBe('pending')
    expect(result.mapasSpaceId).toBeNull()
  })

  it('returns pending for empty cache', () => {
    const result = matchVenue('Cineteatro São Luiz', [])
    expect(result.matchStatus).toBe('pending')
  })

  it('returns suggested for partial match (75–89)', () => {
    // "Teatro Jose Alencar" vs cache entry "Teatro José de Alencar"
    // normalized: 'teatro jose alencar' (19) vs 'teatro jose de alencar' (22), distance=3 → score=87
    const result = matchVenue('Teatro Jose Alencar', sampleCache)
    expect(result.matchStatus).toBe('suggested')
    expect(result.matchScore).toBeGreaterThanOrEqual(75)
    expect(result.matchScore).toBeLessThan(90)
  })
})

// ── applyAutoApprove ──────────────────────────────────────────────────────────

const baseMatchedEvent: ApprovableEvent = {
  matchStatus: 'matched',
  mapasSpaceId: 1,
  title: 'Tim Bernardes ao vivo',
  startAt: '2026-05-24T21:00:00Z',
  endAt: '2026-05-25T01:00:00Z',
  descriptionShort: 'Show acústico no Cineteatro São Luiz',
}

describe('applyAutoApprove', () => {
  it('auto-approves when matched + all required fields', () => {
    const result = applyAutoApprove(baseMatchedEvent)
    expect(result.reviewStatus).toBe('auto_approved')
    expect(result.matchNote).toBeNull()
  })

  it('returns pending when match_status is suggested', () => {
    const result = applyAutoApprove({ ...baseMatchedEvent, matchStatus: 'suggested' })
    expect(result.reviewStatus).toBe('pending')
  })

  it('returns pending when match_status is pending', () => {
    const result = applyAutoApprove({ ...baseMatchedEvent, matchStatus: 'pending', mapasSpaceId: null })
    expect(result.reviewStatus).toBe('pending')
  })

  it('returns pending + match_note when descriptionShort is null', () => {
    const result = applyAutoApprove({ ...baseMatchedEvent, descriptionShort: null })
    expect(result.reviewStatus).toBe('pending')
    expect(result.matchNote).toBe('missing: descriptionShort')
  })

  it('returns pending + match_note when mapasSpaceId is null', () => {
    const result = applyAutoApprove({ ...baseMatchedEvent, mapasSpaceId: null })
    expect(result.reviewStatus).toBe('pending')
    expect(result.matchNote).toBe('missing: mapasSpaceId')
  })

  it('returns pending + match_note for first missing field', () => {
    const result = applyAutoApprove({ ...baseMatchedEvent, title: null })
    expect(result.reviewStatus).toBe('pending')
    expect(result.matchNote).toBe('missing: title')
  })

  it('is idempotent', () => {
    const a = applyAutoApprove(baseMatchedEvent)
    const b = applyAutoApprove(baseMatchedEvent)
    expect(a).toEqual(b)
  })
})
