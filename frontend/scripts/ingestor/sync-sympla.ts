import { getDb } from '../../src/lib/ingestor/db.ts'
import { SymplaConnector } from '../../src/lib/ingestor/sympla.ts'
import { normalizeEvent } from '../../src/lib/ingestor/normalize.ts'
import { matchVenue, applyAutoApprove } from '../../src/lib/ingestor/match.ts'
import { loadVenueCache } from '../../src/lib/ingestor/mapas.ts'
import { sanitizeString, isSkippableName } from '../../src/lib/ingestor/sanitize.ts'

const city = process.argv[2] ?? 'Fortaleza'
const db = getDb()

db.exec(`INSERT INTO crawl_runs (platform, status, started_at) VALUES ('sympla', 'running', '${new Date().toISOString()}')`)
const runId = db.prepare('SELECT last_insert_rowid() as id').get() as { id: number }
const RUN_ID = runId.id

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

async function sync() {
  console.log(`[sync-sympla] Starting sync for city=${city}, runId=${RUN_ID}`)

  const venueCache = loadVenueCache(db)
  console.log(`[sync-sympla] Loaded ${venueCache.length} venues from cache`)

  const connector = new SymplaConnector()
  const rawEvents = await fetchWithRetry(() => connector.fetchEvents(city), 3)

  const syncedAt = new Date().toISOString()
  let eventsNew = 0
  let eventsSkipped = 0

  const beforeCount = (db.prepare('SELECT COUNT(*) as n FROM events').get() as { n: number }).n

  const insertStmt = db.prepare(`
    INSERT OR IGNORE INTO events (
      platform, external_id, source_url, title, subtitle,
      description_short, description_long, start_at, end_at, start_time, end_time,
      price, language, tags, avatar_url, links,
      venue_name, venue_address, venue_city,
      telefone, email, site, cep,
      acessibilidade, classificacao_etaria, latitude, longitude,
      mapas_space_id, match_score, match_status, match_note,
      review_status, import_status, raw_json, synced_at
    ) VALUES (
      ?, ?, ?, ?, ?,
      ?, ?, ?, ?, ?, ?,
      ?, ?, ?, ?, ?,
      ?, ?, ?,
      ?, ?, ?, ?,
      ?, ?, ?, ?,
      ?, ?, ?, ?,
      ?, 'pending', ?, ?
    )
  `)

  const updatePendingStmt = db.prepare(`
    UPDATE events SET
      source_url = ?, title = ?, subtitle = ?,
      description_short = ?, description_long = ?, start_at = ?, end_at = ?,
      start_time = ?, end_time = ?,
      price = ?, tags = ?, avatar_url = ?, links = ?,
      venue_name = ?, venue_address = ?, venue_city = ?,
      telefone = ?, email = ?, site = ?, cep = ?,
      acessibilidade = ?, classificacao_etaria = ?, latitude = ?, longitude = ?,
      mapas_space_id = ?, match_score = ?, match_status = ?, match_note = ?,
      review_status = ?, raw_json = ?, synced_at = ?
    WHERE platform = ? AND external_id = ? AND review_status = 'pending'
  `)

  const langGetOrCreate = db.prepare(`
    INSERT OR IGNORE INTO languages (nome) VALUES (?)
  `)
  const langLookup = db.prepare(`SELECT id FROM languages WHERE nome = ?`)
  const deleteEventLangs = db.prepare(`DELETE FROM event_languages WHERE event_id = ?`)
  const insertEventLang = db.prepare(`INSERT OR IGNORE INTO event_languages (event_id, language_id) VALUES (?, ?)`)

  const sealGetByExtId = db.prepare(`SELECT id FROM seals WHERE external_id = ?`)
  const sealGetByNome = db.prepare(`SELECT id FROM seals WHERE nome = ?`)
  const sealInsert = db.prepare(`INSERT INTO seals (external_id, nome, descricao) VALUES (?, ?, ?)`)
  const sealUpdateExtId = db.prepare(`UPDATE seals SET external_id = ? WHERE id = ?`)
  const deleteEventSeals = db.prepare(`DELETE FROM event_seals WHERE event_id = ?`)
  const insertEventSeal = db.prepare(`INSERT OR IGNORE INTO event_seals (event_id, seal_id) VALUES (?, ?)`)

  const languageCache = new Map<string, number>()
  const sealCacheById = new Map<number, number>()

  const BATCH_SIZE = 500
  let batchCount = 0

  function processBatch(batch: typeof rawEvents) {
    const tx = db.transaction(() => {
      for (const raw of batch) {
        if (isSkippableName(raw.title)) {
          eventsSkipped++
          continue
        }

        const norm = normalizeEvent(raw, 'sympla')
        norm.title = sanitizeString(norm.title, 255) ?? norm.title
        norm.subtitle = sanitizeString(norm.subtitle, 255)
        norm.descriptionShort = sanitizeString(norm.descriptionShort, 65535)
        norm.descriptionLong = sanitizeString(norm.descriptionLong, 65535)
        norm.venueName = sanitizeString(norm.venueName, 255)
        norm.venueAddress = sanitizeString(norm.venueAddress, 255)
        norm.venueCity = sanitizeString(norm.venueCity, 100)
        norm.telefone = sanitizeString(norm.telefone, 50)
        norm.email = sanitizeString(norm.email, 100)
        norm.site = sanitizeString(norm.site, 255)
        norm.cep = sanitizeString(norm.cep, 20)
        norm.classificacaoEtaria = sanitizeString(norm.classificacaoEtaria, 50)
        norm.price = sanitizeString(norm.price, 255)

        const match = matchVenue(norm.venueName, venueCache)
        const approve = applyAutoApprove({
          matchStatus: match.matchStatus,
          mapasSpaceId: match.mapasSpaceId,
          title: norm.title,
          startAt: norm.startAt,
          endAt: norm.endAt,
          descriptionShort: norm.descriptionShort,
        })

        insertStmt.run(
          norm.platform, norm.externalId, norm.sourceUrl, norm.title, norm.subtitle,
          norm.descriptionShort, norm.descriptionLong, norm.startAt, norm.endAt, norm.startTime, norm.endTime,
          norm.price, norm.language, norm.tags, norm.avatarUrl, norm.links,
          norm.venueName, norm.venueAddress, norm.venueCity,
          norm.telefone, norm.email, norm.site, norm.cep,
          norm.acessibilidade ? 1 : 0, norm.classificacaoEtaria, norm.latitude, norm.longitude,
          match.mapasSpaceId, match.matchScore, match.matchStatus, approve.matchNote,
          approve.reviewStatus, norm.rawJson, syncedAt,
        )

        updatePendingStmt.run(
          norm.sourceUrl, norm.title, norm.subtitle,
          norm.descriptionShort, norm.descriptionLong, norm.startAt, norm.endAt,
          norm.startTime, norm.endTime,
          norm.price, norm.tags, norm.avatarUrl, norm.links,
          norm.venueName, norm.venueAddress, norm.venueCity,
          norm.telefone, norm.email, norm.site, norm.cep,
          norm.acessibilidade ? 1 : 0, norm.classificacaoEtaria, norm.latitude, norm.longitude,
          match.mapasSpaceId, match.matchScore, match.matchStatus, approve.matchNote,
          approve.reviewStatus, norm.rawJson, syncedAt,
          norm.platform, norm.externalId,
        )

        const eventRow = db.prepare(
          `SELECT id FROM events WHERE platform = ? AND external_id = ?`
        ).get(norm.platform, norm.externalId) as { id: number } | undefined

        if (!eventRow) continue
        const eventId = eventRow.id

        if (raw.terms?.linguagem || raw.terms?.area) {
          const linguagens = raw.terms?.linguagem ?? raw.terms?.area ?? []
          deleteEventLangs.run(eventId)
          for (const langNome of linguagens) {
            const sanitized = sanitizeString(langNome, 100)
            if (!sanitized) continue
            let langId = languageCache.get(sanitized)
            if (!langId) {
              langGetOrCreate.run(sanitized)
              langId = (langLookup.get(sanitized) as { id: number } | undefined)?.id
              if (langId) languageCache.set(sanitized, langId)
            }
            if (langId) insertEventLang.run(eventId, langId)
          }
        }

        if (raw.seals && Array.isArray(raw.seals)) {
          deleteEventSeals.run(eventId)
          for (const seal of raw.seals) {
            if (!seal.name || typeof seal !== 'object') continue
            const sealNome = sanitizeString(seal.name, 255)
            if (!sealNome) continue
            const sealDesc = sanitizeString(seal.shortDescription, 65535)
            const sealExtId = seal.id || null

            let sealId: number | undefined
            if (sealExtId) {
              sealId = sealCacheById.get(sealExtId)
              if (!sealId) {
                const found = sealGetByExtId.get(sealExtId) as { id: number } | undefined
                if (found) {
                  sealId = found.id
                  sealCacheById.set(sealExtId, sealId)
                }
              }
            }
            if (!sealId) {
              const found = sealGetByNome.get(sealNome) as { id: number } | undefined
              if (found) {
                sealId = found.id
                if (sealExtId && !sealCacheById.has(sealExtId)) {
                  sealUpdateExtId.run(sealExtId, sealId)
                  sealCacheById.set(sealExtId, sealId)
                }
              }
            }
            if (!sealId) {
              const info = sealInsert.run(sealExtId, sealNome, sealDesc)
              sealId = Number(info.lastInsertRowid)
              if (sealExtId) sealCacheById.set(sealExtId, sealId)
            }
            insertEventSeal.run(eventId, sealId)
          }
        }
      }
    })
    tx()
  }

  let batch: typeof rawEvents = []
  for (const raw of rawEvents) {
    batch.push(raw)
    if (batch.length >= BATCH_SIZE) {
      batchCount++
      console.log(`[sync-sympla] Processing batch ${batchCount} (${batch.length} events)`)
      processBatch(batch)
      batch = []
    }
  }
  if (batch.length > 0) {
    batchCount++
    console.log(`[sync-sympla] Processing final batch ${batchCount} (${batch.length} events)`)
    processBatch(batch)
  }

  const afterCount = (db.prepare('SELECT COUNT(*) as n FROM events').get() as { n: number }).n
  eventsNew = afterCount - beforeCount

  db.prepare(`
    UPDATE crawl_runs SET status = 'done', events_fetched = ?, events_new = ?, finished_at = ? WHERE id = ?
  `).run(rawEvents.length, eventsNew, new Date().toISOString(), RUN_ID)

  console.log(`[sync-sympla] Done. fetched=${rawEvents.length} new=${eventsNew} skipped=${eventsSkipped}`)
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
