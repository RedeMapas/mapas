import type { APIRoute } from 'astro'
import { getDb } from '../../../../../lib/ingestor/db.ts'

export const POST: APIRoute = async ({ params, request }) => {
  const id = Number(params.id)
  if (!id || isNaN(id)) {
    return new Response(JSON.stringify({ error: 'Invalid ID' }), { status: 400, headers: { 'Content-Type': 'application/json' } })
  }

  const admCookie = request.headers.get('cookie') ?? ''
  if (!admCookie.includes('mapasculturais.adm')) {
    return new Response(JSON.stringify({ error: 'Unauthorized' }), { status: 401, headers: { 'Content-Type': 'application/json' } })
  }

  const db = getDb()
  const event = db.prepare(
    `SELECT * FROM events WHERE id = ? AND review_status IN ('approved', 'auto_approved') AND import_status = 'pending'`
  ).get(id) as Record<string, unknown> | undefined

  if (!event) {
    return new Response(JSON.stringify({ error: 'Event not found or not importable' }), { status: 404, headers: { 'Content-Type': 'application/json' } })
  }

  if (!event['mapas_space_id']) {
    return new Response(JSON.stringify({ error: 'espaço não vinculado' }), { status: 400, headers: { 'Content-Type': 'application/json' } })
  }

  const mapasUrl = process.env['MAPAS_API_URL'] ?? 'http://localhost:8080'

  let mapasEventId: number
  try {
    const eventRes = await fetch(`${mapasUrl}/api/event/index`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Cookie': admCookie,
      },
      body: JSON.stringify({
        name: event['title'],
        shortDescription: event['description_short'] ?? '',
        longDescription: event['description_long'] ?? null,
        status: 1,
      }),
    })

    if (!eventRes.ok) {
      const errText = await eventRes.text()
      db.prepare(`UPDATE events SET import_status = 'failed' WHERE id = ?`).run(id)
      return new Response(JSON.stringify({ error: 'Mapas event creation failed', detail: errText }), { status: 502, headers: { 'Content-Type': 'application/json' } })
    }

    const eventData = await eventRes.json() as { id?: number }
    mapasEventId = eventData.id ?? 0

    if (mapasEventId <= 0) {
      db.prepare(`UPDATE events SET import_status = 'failed' WHERE id = ?`).run(id)
      return new Response(JSON.stringify({ error: 'Mapas event creation failed: no ID returned' }), { status: 502, headers: { 'Content-Type': 'application/json' } })
    }
  } catch (err) {
    db.prepare(`UPDATE events SET import_status = 'failed' WHERE id = ?`).run(id)
    return new Response(JSON.stringify({ error: 'Network error creating event', detail: String(err) }), { status: 502, headers: { 'Content-Type': 'application/json' } })
  }

  try {
    const occRes = await fetch(`${mapasUrl}/api/eventOccurrence`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Cookie': admCookie,
      },
      body: JSON.stringify({
        eventId: mapasEventId,
        spaceId: event['mapas_space_id'],
        description: '',
        price: event['price'] ?? '',
        rule: {
          frequency: 'once',
          startsOn: event['start_at'],
          endsOn: event['end_at'],
        },
      }),
    })

    if (!occRes.ok) {
      const errText = await occRes.text()
      db.prepare(`UPDATE events SET import_status = 'failed' WHERE id = ?`).run(id)
      fetch(`${mapasUrl}/api/event/single/${mapasEventId}`, {
        method: 'DELETE',
        headers: { 'Cookie': admCookie },
      }).catch(() => {})
      return new Response(JSON.stringify({ error: 'Mapas occurrence creation failed', detail: errText }), { status: 502, headers: { 'Content-Type': 'application/json' } })
    }
  } catch (err) {
    db.prepare(`UPDATE events SET import_status = 'failed' WHERE id = ?`).run(id)
    fetch(`${mapasUrl}/api/event/single/${mapasEventId}`, {
      method: 'DELETE',
      headers: { 'Cookie': admCookie },
    }).catch(() => {})
    return new Response(JSON.stringify({ error: 'Network error creating occurrence', detail: String(err) }), { status: 502, headers: { 'Content-Type': 'application/json' } })
  }

  db.prepare(`UPDATE events SET import_status = 'imported' WHERE id = ?`).run(id)

  return new Response(JSON.stringify({ id, mapasEventId }), { status: 200, headers: { 'Content-Type': 'application/json' } })
}
