/**
 * Bun script — Mapas spaces sync (venue_cache refresh).
 * Fetches active spaces from Mapas PHP API and upserts into venue_cache.
 *
 * Usage:
 *   bun scripts/ingestor/sync-spaces.ts [city]
 *   Default city: Fortaleza
 *
 * Called by: POST /spa/api/ingestor/sync (inline, no subprocess)
 * Also schedulable via daily cron for background refresh.
 */

import { getDb } from '../../src/lib/ingestor/db.ts'
import { fetchMapasSpaces, upsertVenueCache } from '../../src/lib/ingestor/mapas.ts'

const city = process.argv[2] ?? 'Fortaleza'

async function main() {
  console.log(`[sync-spaces] Syncing spaces for city=${city}`)
  const db = getDb()
  const spaces = await fetchMapasSpaces(city)
  const count = upsertVenueCache(db, spaces)
  console.log(`[sync-spaces] Upserted ${count} spaces into venue_cache`)
}

main().catch(err => {
  console.error('[sync-spaces] Fatal error:', err)
  process.exit(1)
})
