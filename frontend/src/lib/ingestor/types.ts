// Connector interface — all platform connectors implement this.
// Add V2 platforms (Bilheteria Virtual, Uhhuu, etc.) by implementing Connector.

export interface RawEvent {
  externalId: string
  sourceUrl: string
  title: string
  subtitle?: string
  descriptionShort?: string
  descriptionLong?: string
  startAt: string        // ISO 8601
  endAt: string          // ISO 8601
  price?: string         // e.g. "Gratuito", "R$ 20,00", "A partir de R$ 30,00"
  language?: string
  tags?: string[]
  avatarUrl?: string
  venueName?: string
  venueCity?: string
  links?: Record<string, string>  // e.g. { sympla: "https://..." }
}

export interface Connector {
  platform: string
  fetchEvents(city: string): Promise<RawEvent[]>
}

// Shared venue type for venue_cache
export interface VenueCacheEntry {
  mapasSpaceId: number
  name: string
  normalizedName: string
  city: string
  lat: number | null
  lng: number | null
}

// Match result returned by matchVenue()
export type MatchStatus = 'matched' | 'suggested' | 'pending'

export interface MatchResult {
  matchStatus: MatchStatus
  mapasSpaceId: number | null
  matchScore: number
}

// Auto-approve result returned by applyAutoApprove()
export type ReviewStatus = 'pending' | 'auto_approved' | 'approved' | 'rejected'

export interface AutoApproveResult {
  reviewStatus: ReviewStatus
  matchNote: string | null  // first missing required field, or null if approved
}

// Minimal event shape needed by applyAutoApprove (called from sync script AND PATCH handler)
export interface ApprovableEvent {
  matchStatus: MatchStatus
  mapasSpaceId: number | null
  title: string | null
  startAt: string | null
  endAt: string | null
  descriptionShort: string | null
}
