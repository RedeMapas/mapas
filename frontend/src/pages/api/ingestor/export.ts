import type { APIRoute } from 'astro'
import { getDb } from '../../../lib/ingestor/db.ts'
import { generateCSV, csvFilename } from '../../../lib/ingestor/csv.ts'

export const GET: APIRoute = () => {
  const db = getDb()

  const events = db.prepare(`
    SELECT
      title, start_at AS startAt, end_at AS endAt,
      description_short AS descriptionShort, description_long AS descriptionLong,
      mapas_space_id AS mapasSpaceId, proprietario, avatar_url AS avatarUrl,
      price, tags, source_url AS sourceUrl, id
    FROM events
    WHERE review_status IN ('approved', 'auto_approved') AND import_status = 'pending'
    ORDER BY start_at ASC
  `).all() as Array<{
    id: number
    title: string
    startAt: string
    endAt: string
    descriptionShort: string | null
    descriptionLong: string | null
    mapasSpaceId: number | null
    proprietario: string | null
    avatarUrl: string | null
    price: string | null
    tags: string | null
    sourceUrl: string
  }>

  if (events.length === 0) {
    return new Response(
      JSON.stringify({ error: 'Nenhum evento aprovado pendente de exportação.' }),
      { status: 404, headers: { 'Content-Type': 'application/json' } },
    )
  }

  const csv = generateCSV(events)
  const filename = csvFilename()

  const markExported = db.transaction(() => {
    for (const evt of events) {
      db.prepare(`UPDATE events SET import_status = 'exported' WHERE id = ?`).run(evt.id)
    }
    db.prepare(`INSERT INTO export_runs (exported_at, event_count) VALUES (?, ?)`).run(
      new Date().toISOString(),
      events.length,
    )
  })
  markExported()

  return new Response(csv, {
    status: 200,
    headers: {
      'Content-Type': 'text/csv; charset=utf-8',
      'Content-Disposition': `attachment; filename="${filename}"`,
    },
  })
}
