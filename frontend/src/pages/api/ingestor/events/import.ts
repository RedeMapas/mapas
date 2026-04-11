import type { APIRoute } from 'astro'
import { getDb } from '../../../../lib/ingestor/db.ts'

export const POST: APIRoute = async ({ request }) => {
  const admCookie = request.headers.get('cookie') ?? ''
  if (!admCookie.includes('mapasculturais.adm')) {
    return new Response(JSON.stringify({ error: 'Unauthorized' }), { status: 401, headers: { 'Content-Type': 'application/json' } })
  }

  let body: { ids?: unknown }
  try {
    body = await request.json()
  } catch {
    return new Response(JSON.stringify({ error: 'Invalid JSON' }), { status: 400, headers: { 'Content-Type': 'application/json' } })
  }

  const ids = body.ids
  if (!Array.isArray(ids) || ids.length === 0) {
    return new Response(JSON.stringify({ error: 'ids must be a non-empty array' }), { status: 400, headers: { 'Content-Type': 'application/json' } })
  }

  const numericIds = ids.map(Number).filter(n => !isNaN(n) && n > 0)
  if (numericIds.length === 0) {
    return new Response(JSON.stringify({ error: 'No valid IDs provided' }), { status: 400, headers: { 'Content-Type': 'application/json' } })
  }

  const db = getDb()
  const placeholders = numericIds.map(() => '?').join(',')

  const events = db.prepare(
    `SELECT * FROM events WHERE id IN (${placeholders}) AND review_status IN ('approved', 'auto_approved') AND import_status = 'pending'`
  ).all(...numericIds) as Array<Record<string, unknown>>

  if (events.length === 0) {
    return new Response(JSON.stringify({ error: 'No importable events found' }), { status: 404, headers: { 'Content-Type': 'application/json' } })
  }

  const resultados: Array<{ id: number; status: 'imported' | 'failed'; error?: string }> = []

  for (const evt of events) {
    const eventId = evt['id'] as number
    const mapasUrl = process.env['MAPAS_API_URL'] ?? 'http://localhost:8080'

    try {
      const eventRes = await fetch(`${mapasUrl}/api/event/index`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Cookie': admCookie },
        body: JSON.stringify({
          name: evt['title'],
          shortDescription: evt['description_short'] ?? '',
          longDescription: evt['description_long'] ?? null,
          status: 1,
        }),
      })

      if (!eventRes.ok) {
        db.prepare(`UPDATE events SET import_status = 'failed' WHERE id = ?`).run(eventId)
        resultados.push({ id: eventId, status: 'failed', error: 'event creation failed' })
        continue
      }

      const eventData = await eventRes.json() as { id?: number }
      const mapasEventId = eventData.id ?? 0

      if (mapasEventId <= 0) {
        db.prepare(`UPDATE events SET import_status = 'failed' WHERE id = ?`).run(eventId)
        resultados.push({ id: eventId, status: 'failed', error: 'invalid event ID from Mapas' })
        continue
      }

      const occRes = await fetch(`${mapasUrl}/api/eventOccurrence`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Cookie': admCookie },
        body: JSON.stringify({
          eventId: mapasEventId,
          spaceId: evt['mapas_space_id'],
          description: '',
          price: evt['price'] ?? '',
          rule: { frequency: 'once', startsOn: evt['start_at'], endsOn: evt['end_at'] },
        }),
      })

      if (!occRes.ok) {
        const errText = await occRes.text()
        db.prepare(`UPDATE events SET import_status = 'failed' WHERE id = ?`).run(eventId)
        resultados.push({ id: eventId, status: 'failed', error: 'occurrence creation failed' })
        continue
      }

      db.prepare(`UPDATE events SET import_status = 'imported' WHERE id = ?`).run(eventId)
      resultados.push({ id: eventId, status: 'imported' })
    } catch {
      db.prepare(`UPDATE events SET import_status = 'failed' WHERE id = ?`).run(eventId)
      resultados.push({ id: eventId, status: 'failed', error: 'network error' })
    }
  }

  const imported = resultados.filter(r => r.status === 'imported').length
  const failed = resultados.filter(r => r.status === 'failed').length

  return new Response(JSON.stringify({ imported, failed, results: resultados }), { status: 200, headers: { 'Content-Type': 'application/json' } })
}
