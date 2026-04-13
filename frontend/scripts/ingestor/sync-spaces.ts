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
