/**
 * Mapas Culturais space fetcher.
 * Fetches spaces from the PHP API and upserts them into venue_cache.
 * Used by: sync.ts API route (inline, no subprocess) and sync-spaces.ts (cron).
 */

import type BetterSqlite3 from 'better-sqlite3'

type Database = BetterSqlite3.Database
import type { VenueCacheEntry } from './types.ts'
import { normalizeStr } from './match.ts'

interface MapasSpace {
  id: number
  name: string
  shortDescription?: string
  location?: {
    latitude?: number | string
    longitude?: number | string
  }
  endereco?: {
    municipio?: string
  }
  // API may return more fields — only use what we need
}

function getApiUrl(): string {
  const url = process.env['PHP_API_URL'] ?? process.env['MAPAS_API_URL'] ?? 'http://localhost:8080'
  return url.replace(/\/$/, '')
}

/**
 * Fetch all active spaces from Mapas API for the given city.
 * Paginates with @limit=100 until no more results.
 */
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
      '@select': 'id,name,location,endereco',
      'endereco.municipio': `ILIKE(${city})`,
    })

    const url = `${baseUrl}/api/espaco/find?${params}`

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

/**
 * Upsert spaces into venue_cache.
 * Returns the count of upserted rows.
 */
export function upsertVenueCache(db: Database, spaces: MapasSpace[]): number {
  const stmt = db.prepare(`
    INSERT INTO venue_cache (mapas_space_id, name, normalized_name, city, lat, lng, synced_at)
    VALUES (?, ?, ?, ?, ?, ?, ?)
    ON CONFLICT (mapas_space_id) DO UPDATE SET
      name = excluded.name,
      normalized_name = excluded.normalized_name,
      city = excluded.city,
      lat = excluded.lat,
      lng = excluded.lng,
      synced_at = excluded.synced_at
  `)

  const syncedAt = new Date().toISOString()
  let count = 0

  const upsertMany = db.transaction((rows: VenueCacheEntry[]) => {
    for (const row of rows) {
      stmt.run(row.mapasSpaceId, row.name, row.normalizedName, row.city, row.lat, row.lng, syncedAt)
      count++
    }
  })

  const entries: VenueCacheEntry[] = spaces.map(s => ({
    mapasSpaceId: s.id,
    name: s.name,
    normalizedName: normalizeStr(s.name),
    city: s.endereco?.municipio ?? '',
    lat: s.location?.latitude != null ? Number(s.location.latitude) : null,
    lng: s.location?.longitude != null ? Number(s.location.longitude) : null,
  }))

  upsertMany(entries)
  return count
}

/**
 * Load all venue_cache rows into memory for matching.
 */
export function loadVenueCache(db: Database): VenueCacheEntry[] {
  return db.prepare(`
    SELECT mapas_space_id AS mapasSpaceId, name, normalized_name AS normalizedName, city, lat, lng
    FROM venue_cache
  `).all() as VenueCacheEntry[]
}
