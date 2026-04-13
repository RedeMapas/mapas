export interface RawEvent {
  externalId: string
  sourceUrl: string
  title: string
  subtitle?: string
  descriptionShort?: string
  descriptionLong?: string
  startAt: string
  endAt: string
  startTime?: string
  endTime?: string
  price?: string
  language?: string
  tags?: string[]
  avatarUrl?: string
  venueName?: string
  venueAddress?: string
  venueCity?: string
  telefone?: string
  email?: string
  site?: string
  cep?: string
  acessibilidade?: boolean
  classificacaoEtaria?: string
  latitude?: number
  longitude?: number
  links?: Record<string, string>
  terms?: { area?: string[]; tag?: string[]; linguagem?: string[] }
  seals?: Array<{ id: number; name: string; shortDescription?: string }>
}

export interface Connector {
  platform: string
  fetchEvents(city: string): Promise<RawEvent[]>
}

export interface VenueCacheEntry {
  mapasSpaceId: number
  name: string
  normalizedName: string
  city: string
  lat: number | null
  lng: number | null
  endereco?: string | null
  telefone?: string | null
  email?: string | null
  site?: string | null
  cep?: string | null
  acessibilidade?: boolean
}

export type MatchStatus = 'matched' | 'suggested' | 'pending'

export interface MatchResult {
  matchStatus: MatchStatus
  mapasSpaceId: number | null
  matchScore: number
}

export type ReviewStatus = 'pending' | 'auto_approved' | 'approved' | 'rejected'

export interface AutoApproveResult {
  reviewStatus: ReviewStatus
  matchNote: string | null
}

export interface ApprovableEvent {
  matchStatus: MatchStatus
  mapasSpaceId: number | null
  title: string | null
  startAt: string | null
  endAt: string | null
  descriptionShort: string | null
}

export interface Language {
  id: number
  nome: string
}

export interface EventLanguage {
  eventId: number
  languageId: number
}

export interface Seal {
  id: number
  externalId: number | null
  nome: string
  descricao: string | null
}

export interface EventSeal {
  eventId: number
  sealId: number
}
