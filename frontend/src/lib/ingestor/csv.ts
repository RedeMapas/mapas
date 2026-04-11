// CSV export for Mapas Culturais event import
// Column order must match Mapas CSV import spec

// Required columns (verify with Mapas admin before production use)
const HEADERS = [
  'NOME',
  'DATA_INICIO',
  'DATA_FIM',
  'DESCRICAO_CURTA',
  'DESCRICAO_LONGA',
  'LOCAL_ID',       // mapas_space_id
  'PROPRIETARIO',
  'AVATAR',
  'PRECO',
  'TAGS',
  'FONTE',          // source_url (for reference)
]

interface ExportableEvent {
  title: string
  startAt: string
  endAt: string
  descriptionShort: string | null
  descriptionLong: string | null
  mapasSpaceId: number | null
  proprietario: string | null
  avatarUrl: string | null
  price: string | null
  tags: string | null   // JSON array string
  sourceUrl: string
}

function escapeField(value: string | null | undefined): string {
  if (value == null || value === '') return ''
  const str = String(value)
  // If field contains comma, newline, or double-quote — wrap in quotes and escape inner quotes
  if (str.includes(',') || str.includes('\n') || str.includes('"')) {
    return `"${str.replace(/"/g, '""')}"`
  }
  return str
}

function formatDate(iso: string): string {
  // Convert ISO 8601 to Brazilian format DD/MM/YYYY HH:MM
  try {
    const d = new Date(iso)
    const pad = (n: number) => String(n).padStart(2, '0')
    return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`
  } catch {
    return iso
  }
}

function parseTags(tagsJson: string | null): string {
  if (!tagsJson) return ''
  try {
    const arr = JSON.parse(tagsJson)
    return Array.isArray(arr) ? arr.join(';') : ''
  } catch {
    return ''
  }
}

export function generateCSV(events: ExportableEvent[]): string {
  const rows: string[] = [HEADERS.join(',')]

  for (const evt of events) {
    const row = [
      escapeField(evt.title),
      escapeField(formatDate(evt.startAt)),
      escapeField(formatDate(evt.endAt)),
      escapeField(evt.descriptionShort),
      escapeField(evt.descriptionLong),
      escapeField(evt.mapasSpaceId != null ? String(evt.mapasSpaceId) : null),
      escapeField(evt.proprietario),
      escapeField(evt.avatarUrl),
      escapeField(evt.price),
      escapeField(parseTags(evt.tags)),
      escapeField(evt.sourceUrl),
    ]
    rows.push(row.join(','))
  }

  return rows.join('\n')
}

export function csvFilename(): string {
  const now = new Date()
  const pad = (n: number) => String(n).padStart(2, '0')
  const date = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`
  const time = `${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`
  return `mapas-export-${date}-${time}.csv`
}
