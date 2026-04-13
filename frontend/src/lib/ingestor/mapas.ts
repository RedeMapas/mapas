import type BetterSqlite3 from 'better-sqlite3'

type Database = BetterSqlite3.Database
import type { VenueCacheEntry } from './types.ts'
import { normalizeStr } from './match.ts'
import { sanitizeString } from './sanitize.ts'

interface MapasSpace {
  id: number
  name: string
  shortDescription?: string
  location?: {
    latitude?: number | string
    longitude?: number | string
  }
  endereco?: {
    En_Endereco?: string
    En_Num?: string
    En_Bairro?: string
    En_Complemento?: string
    En_Municipio?: string
    En_Estado?: string
    En_CEP?: string
    municipio?: string
  }
  telefonePublico?: string
  telefone1?: string
  telefone2?: string
  emailPublico?: string
  site?: string
  acessibilidade?: boolean | number | string
}

function getApiUrl(): string {
  const url = process.env['PHP_API_URL'] ?? process.env['MAPAS_API_URL'] ?? 'http://localhost'
  return url.replace(/\/$/, '')
}

export async function fetchMapasSpaces(city: string = 'Fortaleza'): Promise<MapasSpace[]> {
  const baseUrl = getApiUrl()
  const allSpaces: MapasSpace[] = []
  let offset = 0
  const limit = 100

  console.log(`[mapas] Fetching spaces for city=${city}`)

  while (true) {
    const params = new URLSearchParams({
      'status': 'EQ(1)',
      '@limit': String(limit),
      '@offset': String(offset),
      '@select': 'id,name,location,endereco,telefonePublico,telefone1,telefone2,emailPublico,site,acessibilidade',
      'endereco.En_Municipio': `ILIKE(${city})`,
    })

    const url = `${baseUrl}/api/space/find?${params}`

    try {
      const res = await fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      })
      if (!res.ok) {
        console.error(`[mapas] API error: HTTP ${res.status}`)
        break
      }

      const data = await res.json()
      if (!Array.isArray(data) || data.length === 0) break

      allSpaces.push(...data)
      console.log(`[mapas] Fetched ${data.length} spaces (total: ${allSpaces.length})`)

      if (data.length < limit) break
      offset += limit
    } catch (err) {
      console.error('[mapas] fetch error:', err)
      break
    }
  }

  return allSpaces
}

function buildAddress(s: MapasSpace): string | null {
  const parts = [
    s.endereco?.En_Endereco,
    s.endereco?.En_Num,
    s.endereco?.En_Bairro,
    s.endereco?.En_Complemento,
  ].filter(Boolean).map(p => String(p).trim())
  return parts.length > 0 ? parts.join(', ') : null
}

function getPhone(s: MapasSpace): string | null {
  return s.telefonePublico || s.telefone1 || s.telefone2 || null
}

export function upsertVenueCache(db: Database, spaces: MapasSpace[]): number {
  const stmt = db.prepare(`
    INSERT INTO venue_cache (mapas_space_id, name, normalized_name, city, endereco, cep, telefone, email, site, acessibilidade, lat, lng, synced_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON CONFLICT (mapas_space_id) DO UPDATE SET
      name = excluded.name,
      normalized_name = excluded.normalized_name,
      city = excluded.city,
      endereco = excluded.endereco,
      cep = excluded.cep,
      telefone = excluded.telefone,
      email = excluded.email,
      site = excluded.site,
      acessibilidade = excluded.acessibilidade,
      lat = excluded.lat,
      lng = excluded.lng,
      synced_at = excluded.synced_at
  `)

  const syncedAt = new Date().toISOString()
  let count = 0

  const upsertMany = db.transaction((rows: VenueCacheEntry[]) => {
    for (const row of rows) {
      stmt.run(
        row.mapasSpaceId, row.name, row.normalizedName, row.city,
        row.endereco ?? null, row.cep ?? null, row.telefone ?? null,
        row.email ?? null, row.site ?? null,
        row.acessibilidade ? 1 : 0,
        row.lat, row.lng, syncedAt,
      )
      count++
    }
  })

  const entries: VenueCacheEntry[] = spaces.map(s => ({
    mapasSpaceId: s.id,
    name: sanitizeString(s.name, 255) ?? s.name,
    normalizedName: normalizeStr(s.name),
    city: sanitizeString(s.endereco?.municipio ?? s.endereco?.En_Municipio, 100) ?? '',
    endereco: sanitizeString(buildAddress(s), 255),
    cep: sanitizeString(s.endereco?.En_CEP, 20),
    telefone: sanitizeString(getPhone(s), 50),
    email: sanitizeString(s.emailPublico, 100),
    site: sanitizeString(s.site, 255),
    acessibilidade: !!s.acessibilidade,
    lat: s.location?.latitude != null ? Number(s.location.latitude) : null,
    lng: s.location?.longitude != null ? Number(s.location.longitude) : null,
  }))

  upsertMany(entries)
  return count
}

export function loadVenueCache(db: Database): VenueCacheEntry[] {
  return db.prepare(`
    SELECT mapas_space_id AS mapasSpaceId, name, normalized_name AS normalizedName, city, lat, lng
    FROM venue_cache
  `).all() as VenueCacheEntry[]
}
