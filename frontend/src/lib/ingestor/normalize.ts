import type { RawEvent } from './types.ts'

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
  price: string | null
  language: string | null
  tags: string | null           // JSON array string
  avatarUrl: string | null
  links: string | null          // JSON object string
  venueName: string | null      // for matching, not stored in events table
  venueCity: string | null      // for matching, not stored in events table
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
    price: raw.price?.trim() ?? null,
    language: raw.language?.trim() ?? null,
    tags: raw.tags && raw.tags.length > 0 ? JSON.stringify(raw.tags) : null,
    avatarUrl: raw.avatarUrl ?? null,
    links: raw.links && Object.keys(raw.links).length > 0
      ? JSON.stringify(raw.links)
      : null,
    venueName: raw.venueName?.trim() ?? null,
    venueCity: raw.venueCity?.trim() ?? null,
    rawJson: JSON.stringify(raw),
  }
}
