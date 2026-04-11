import { describe, it, expect } from 'bun:test'
import { generateCSV } from './csv.ts'

const baseEvent = {
  title: 'Tim Bernardes ao vivo',
  startAt: '2026-05-24T21:00:00.000Z',
  endAt: '2026-05-25T01:00:00.000Z',
  descriptionShort: 'Show acústico no Cineteatro',
  descriptionLong: null,
  mapasSpaceId: 42,
  proprietario: '123',
  avatarUrl: 'https://example.com/img.jpg',
  price: 'R$ 80,00',
  tags: '["música", "show"]',
  sourceUrl: 'https://www.sympla.com.br/evento/tim-bernardes/119059',
}

describe('generateCSV', () => {
  it('returns header-only for empty array', () => {
    const csv = generateCSV([])
    const lines = csv.split('\n')
    expect(lines).toHaveLength(1)
    expect(lines[0]).toContain('NOME')
    expect(lines[0]).toContain('DATA_INICIO')
    expect(lines[0]).toContain('LOCAL_ID')
  })

  it('generates correct columns for full event', () => {
    const csv = generateCSV([baseEvent])
    const lines = csv.split('\n')
    expect(lines).toHaveLength(2)
    expect(lines[1]).toContain('Tim Bernardes ao vivo')
    expect(lines[1]).toContain('42')
    expect(lines[1]).toContain('123')
  })

  it('escapes title containing comma', () => {
    const evt = { ...baseEvent, title: 'Show, ao vivo' }
    const csv = generateCSV([evt])
    expect(csv).toContain('"Show, ao vivo"')
  })

  it('escapes description containing double quotes', () => {
    const evt = { ...baseEvent, descriptionShort: 'Show "especial" no teatro' }
    const csv = generateCSV([evt])
    expect(csv).toContain('"Show ""especial"" no teatro"')
  })

  it('handles null proprietario as empty field', () => {
    const evt = { ...baseEvent, proprietario: null }
    const csv = generateCSV([evt])
    const lines = csv.split('\n')
    // Proprietario column should be empty, not omitted
    expect(lines[1]).toContain(',,')  // empty field between LOCAL_ID and AVATAR
  })

  it('parses tags from JSON array', () => {
    const csv = generateCSV([baseEvent])
    expect(csv).toContain('música;show')
  })

  it('handles null tags gracefully', () => {
    const evt = { ...baseEvent, tags: null }
    const csv = generateCSV([evt])
    const lines = csv.split('\n')
    expect(lines).toHaveLength(2)
  })

  it('generates one row per event', () => {
    const csv = generateCSV([baseEvent, baseEvent, baseEvent])
    const lines = csv.split('\n')
    expect(lines).toHaveLength(4)  // 1 header + 3 events
  })
})
