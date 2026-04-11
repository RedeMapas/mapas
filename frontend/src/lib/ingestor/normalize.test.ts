import { describe, it, expect } from 'bun:test'
import { normalizeEvent } from './normalize.ts'
import type { RawEvent } from './types.ts'

const baseRaw: RawEvent = {
  externalId: '119059',
  sourceUrl: 'https://bileto.sympla.com.br/event/119059',
  title: '  Tim Bernardes - Despedida  ',
  subtitle: undefined,
  descriptionShort: undefined,
  startAt: '2026-05-24T21:00:00+00:00',
  endAt: '2026-05-25T01:00:00+00:00',
  price: undefined,
  language: undefined,
  tags: undefined,
  avatarUrl: 'https://assets.bileto.sympla.com.br/img.jpg',
  venueName: 'Cineteatro São Luiz',
  venueCity: 'Fortaleza',
  links: { sympla: 'https://bileto.sympla.com.br/event/119059' },
}

describe('normalizeEvent', () => {
  it('trims title whitespace', () => {
    const result = normalizeEvent(baseRaw, 'sympla')
    expect(result.title).toBe('Tim Bernardes - Despedida')
  })

  it('preserves external_id as string', () => {
    const result = normalizeEvent(baseRaw, 'sympla')
    expect(result.externalId).toBe('119059')
    expect(typeof result.externalId).toBe('string')
  })

  it('sets null for missing optional fields', () => {
    const result = normalizeEvent(baseRaw, 'sympla')
    expect(result.subtitle).toBeNull()
    expect(result.descriptionShort).toBeNull()
    expect(result.price).toBeNull()
    expect(result.language).toBeNull()
    expect(result.tags).toBeNull()
  })

  it('serializes links as JSON string', () => {
    const result = normalizeEvent(baseRaw, 'sympla')
    expect(result.links).toBe('{"sympla":"https://bileto.sympla.com.br/event/119059"}')
  })

  it('sets null for empty links object', () => {
    const result = normalizeEvent({ ...baseRaw, links: {} }, 'sympla')
    expect(result.links).toBeNull()
  })

  it('serializes tags array as JSON string', () => {
    const result = normalizeEvent({ ...baseRaw, tags: ['música', 'show'] }, 'sympla')
    expect(result.tags).toBe('["música","show"]')
  })

  it('sets null for empty tags array', () => {
    const result = normalizeEvent({ ...baseRaw, tags: [] }, 'sympla')
    expect(result.tags).toBeNull()
  })

  it('stores raw_json for debugging', () => {
    const result = normalizeEvent(baseRaw, 'sympla')
    expect(result.rawJson).toBeTruthy()
    const parsed = JSON.parse(result.rawJson)
    expect(parsed.externalId).toBe('119059')
  })

  it('platform is passed through correctly', () => {
    const result = normalizeEvent(baseRaw, 'sympla')
    expect(result.platform).toBe('sympla')
  })
})
