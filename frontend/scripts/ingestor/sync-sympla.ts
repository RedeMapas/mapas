/**
 * Bun script — Sympla event sync.
 * Fetches events from Sympla, matches venues, writes to SQLite.
 *
 * Usage:
 *   bun scripts/ingestor/sync-sympla.ts [city]
 *   Default city: Fortaleza
 *
 * Spawned by: POST /spa/api/ingestor/sync (detached)
 * Also schedulable via cron for daily syncs.
 */

import { getDb } from '../../src/lib/ingestor/db.ts'
import { SymplaConnector } from '../../src/lib/ingestor/sympla.ts'
import { normalizeEvent } from '../../src/lib/ingestor/normalize.ts'
import { matchVenue, applyAutoApprove } from '../../src/lib/ingestor/match.ts'
import { loadVenueCache } from '../../src/lib/ingestor/mapas.ts'

const city = process.argv[2] ?? 'Fortaleza'
const db = getDb()

// Register a crawl_run row
db.exec(`INSERT INTO crawl_runs (platform, status, started_at) VALUES ('sympla', 'running', '${new Date().toISOString()}')`)
const runId = db.prepare('SELECT last_insert_rowid() as id').get() as { id: number }
const RUN_ID = runId.id

// Crash recovery: always update status on exit
function handleExit(status: 'done' | 'error', message?: string) {
  try {
    db.prepare(`
      UPDATE crawl_runs SET status = ?, finished_at = ?, error_message = ? WHERE id = ?
    `).run(status, new Date().toISOString(), message ?? null, RUN_ID)
  } catch {
    // DB may be closed — ignore
  }
}

process.on('uncaughtException', (err: Error) => {
  console.error('[sync-sympla] Uncaught exception:', err)
  handleExit('error', err.message)
  process.exit(1)
})

process.on('unhandledRejection', (reason: unknown) => {
  const msg = reason instanceof Error ? reason.message : String(reason)
  console.error('[sync-sympla] Unhandled rejection:', msg)
  handleExit('error', msg)
  process.exit(1)
})

// ── Main sync ────────────────────────────────────────────────────────────────

async function sync() {
  console.log(`[sync-sympla] Starting sync for city=${city}, runId=${RUN_ID}`)

  const venueCache = loadVenueCache(db)
  console.log(`[sync-sympla] Loaded ${venueCache.length} venues from cache`)

  const connector = new SymplaConnector()
  const rawEvents = await fetchWithRetry(() => connector.fetchEvents(city), 3)

  const syncedAt = new Date().toISOString()
  let eventsNew = 0

  // Count before to determine new events
  const beforeCount = (db.prepare('SELECT COUNT(*) as n FROM events').get() as { n: number }).n

  const insertStmt = db.prepare(`
    INSERT OR IGNORE INTO events (
      platform, external_id, source_url, title, subtitle,
      description_short, description_long, start_at, end_at,
      price, language, tags, avatar_url, links,
      mapas_space_id, match_score, match_status, match_note,
      review_status, import_status, raw_json, synced_at
    ) VALUES (
      ?, ?, ?, ?, ?,
      ?, ?, ?, ?,
      ?, ?, ?, ?, ?,
      ?, ?, ?, ?,
      ?, 'pending', ?, ?
    )
  `)

  const updatePendingStmt = db.prepare(`
    UPDATE events SET
      source_url = ?, title = ?, subtitle = ?,
      description_short = ?, start_at = ?, end_at = ?,
      avatar_url = ?, links = ?,
      mapas_space_id = ?, match_score = ?, match_status = ?, match_note = ?,
      review_status = ?, raw_json = ?, synced_at = ?
    WHERE platform = ? AND external_id = ? AND review_status = 'pending'
  `)

  const processAll = db.transaction(() => {
    for (const raw of rawEvents) {
      const norm = normalizeEvent(raw, 'sympla')
      const match = matchVenue(norm.venueName, venueCache)
      const approve = applyAutoApprove({
        matchStatus: match.matchStatus,
        mapasSpaceId: match.mapasSpaceId,
        title: norm.title,
        startAt: norm.startAt,
        endAt: norm.endAt,
        descriptionShort: norm.descriptionShort,
      })

      // Try to insert (ignored if already exists)
      insertStmt.run(
        norm.platform, norm.externalId, norm.sourceUrl, norm.title, norm.subtitle,
        norm.descriptionShort, norm.descriptionLong, norm.startAt, norm.endAt,
        norm.price, norm.language, norm.tags, norm.avatarUrl, norm.links,
        match.mapasSpaceId, match.matchScore, match.matchStatus, approve.matchNote,
        approve.reviewStatus, norm.rawJson, syncedAt,
      )

      // Update if still pending (re-run matching in case venues changed)
      updatePendingStmt.run(
        norm.sourceUrl, norm.title, norm.subtitle,
        norm.descriptionShort, norm.startAt, norm.endAt,
        norm.avatarUrl, norm.links,
        match.mapasSpaceId, match.matchScore, match.matchStatus, approve.matchNote,
        approve.reviewStatus, norm.rawJson, syncedAt,
        norm.platform, norm.externalId,
      )
    }
  })

  processAll()

  const afterCount = (db.prepare('SELECT COUNT(*) as n FROM events').get() as { n: number }).n
  eventsNew = afterCount - beforeCount

  db.prepare(`
    UPDATE crawl_runs SET status = 'done', events_fetched = ?, events_new = ?, finished_at = ? WHERE id = ?
  `).run(rawEvents.length, eventsNew, new Date().toISOString(), RUN_ID)

  console.log(`[sync-sympla] Done. fetched=${rawEvents.length} new=${eventsNew}`)
}

async function fetchWithRetry<T>(fn: () => Promise<T>, attempts: number): Promise<T> {
  for (let i = 0; i < attempts; i++) {
    try {
      return await fn()
    } catch (err) {
      if (i === attempts - 1) throw err
      const delay = Math.pow(2, i) * 1000
      console.warn(`[sync-sympla] Attempt ${i + 1} failed, retrying in ${delay}ms...`)
      await new Promise(r => setTimeout(r, delay))
    }
  }
  throw new Error('unreachable')
}

sync().catch(err => {
  console.error('[sync-sympla] Fatal error:', err)
  handleExit('error', err?.message ?? String(err))
  process.exit(1)
})
