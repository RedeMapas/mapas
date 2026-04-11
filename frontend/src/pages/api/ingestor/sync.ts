import type { APIRoute } from 'astro'
import { getDb } from '../../../lib/ingestor/db.ts'
import { fetchMapasSpaces, upsertVenueCache } from '../../../lib/ingestor/mapas.ts'
import { spawn } from 'child_process'
import { resolve, dirname } from 'path'
import { fileURLToPath } from 'url'
import { mkdirSync, existsSync } from 'fs'

const __filename = fileURLToPath(import.meta.url)
const SCRIPTS_DIR = resolve(__filename, '../../../../../../../scripts/ingestor')

export const POST: APIRoute = async () => {
  try {
    // Ensure data directory exists
    const dbPath = process.env['INGESTOR_DB_PATH']
      ?? resolve(__filename, '../../../../../data/ingestor.db')
    const dbDir = dirname(dbPath)
    if (!existsSync(dbDir)) {
      mkdirSync(dbDir, { recursive: true })
    }

    const db = getDb()

    // Concurrent sync guard with 2-hour staleness window
    const staleThreshold = new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString()
    const running = db.prepare(`
      SELECT id FROM crawl_runs
      WHERE platform = 'sympla' AND status = 'running' AND started_at > ?
      ORDER BY started_at DESC LIMIT 1
    `).get(staleThreshold)

    if (running) {
      return new Response(
        JSON.stringify({ error: 'Sincronização em andamento — aguarde.' }),
        { status: 409, headers: { 'Content-Type': 'application/json' } },
      )
    }

    // Step 1: Sync spaces inline (direct function call, no subprocess)
    console.log('[sync] Fetching Mapas spaces inline...')
    const spaces = await fetchMapasSpaces('Fortaleza')
    const count = upsertVenueCache(db, spaces)
    console.log(`[sync] Upserted ${count} spaces into venue_cache`)

    // Step 2: Spawn sympla sync as detached background process
    const scriptPath = resolve(SCRIPTS_DIR, 'sync-sympla.ts')
    const nodePath = process.env['NODE_PATH'] || 'node'

    console.log(`[sync] Spawning node ${scriptPath}`)

    const child = spawn(nodePath, ['--experimental-strip-types', scriptPath, 'Fortaleza'], {
      detached: true,
      stdio: 'ignore',
      env: { ...process.env },
    })
    child.unref()

    return new Response(
      JSON.stringify({ message: 'Sincronização iniciada', pid: child.pid }),
      { status: 202, headers: { 'Content-Type': 'application/json' } },
    )
  } catch (err) {
    console.error('[sync] Fatal error:', err)
    return new Response(
      JSON.stringify({ 
        error: 'Erro ao iniciar sincronização',
        details: err instanceof Error ? err.message : String(err)
      }),
      { status: 500, headers: { 'Content-Type': 'application/json' } },
    )
  }
}
