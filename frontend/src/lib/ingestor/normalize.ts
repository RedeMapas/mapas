import type { Connector, RawEvent } from './types.ts'

export interface NormalizedEvent {
  platform: string
  externalId: string
  sourceUrl: string
  title: string
  subtitle: string | null
  descriptionShort: string | null
  descriptionLong: string | null
  startAt: string
  endAt: string
  startTime: string | null
  endTime: string | null
  price: string | null
  language: string | null
  tags: string | null
  avatarUrl: string | null
  links: string | null
  venueName: string | null
  venueAddress: string | null
  venueCity: string | null
  telefone: string | null
  email: string | null
  site: string | null
  cep: string | null
  acessibilidade: boolean
  classificacaoEtaria: string | null
  latitude: number | null
  longitude: number | null
  rawJson: string
}

export function normalizeEvent(raw: RawEvent, platform: string): NormalizedEvent {
  return {
    platform,
    externalId: String(raw.externalId),
    sourceUrl: raw.sourceUrl,
    title: raw.title.trim(),
    subtitle: raw.subtitle?.trim() ?? null,
    descriptionShort: raw.descriptionShort?.trim() ?? null,
    descriptionLong: raw.descriptionLong?.trim() ?? null,
    startAt: raw.startAt,
    endAt: raw.endAt,
    startTime: raw.startTime?.trim() ?? null,
    endTime: raw.endTime?.trim() ?? null,
    price: raw.price?.trim() ?? null,
    language: raw.language?.trim() ?? null,
    tags: raw.tags && raw.tags.length > 0 ? JSON.stringify(raw.tags) : null,
    avatarUrl: raw.avatarUrl ?? null,
    links: raw.links && Object.keys(raw.links).length > 0
      ? JSON.stringify(raw.links)
      : null,
    venueName: raw.venueName?.trim() ?? null,
    venueAddress: raw.venueAddress?.trim() ?? null,
    venueCity: raw.venueCity?.trim() ?? null,
    telefone: raw.telefone?.trim() ?? null,
    email: raw.email?.trim() ?? null,
    site: raw.site?.trim() ?? null,
    cep: raw.cep?.trim() ?? null,
    acessibilidade: raw.acessibilidade ?? false,
    classificacaoEtaria: raw.classificacaoEtaria?.trim() ?? null,
    latitude: raw.latitude ?? null,
    longitude: raw.longitude ?? null,
    rawJson: JSON.stringify(raw),
  }
}
