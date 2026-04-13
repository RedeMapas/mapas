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
      const eventBody: Record<string, unknown> = {
        name: evt['title'],
        shortDescription: evt['description_short'] ?? '',
        longDescription: evt['description_long'] ?? null,
        status: 1,
        classificacaoEtaria: evt['classificacao_etaria'] ?? 'Livre',
        acessibilidade: evt['acessibilidade'] ? 1 : 0,
      }

      if (evt['telefone']) eventBody['telefonePublico'] = evt['telefone']
      if (evt['email']) eventBody['emailPublico'] = evt['email']
      if (evt['site']) eventBody['site'] = evt['site']

      const eventRes = await fetch(`${mapasUrl}/api/event/index`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Cookie': admCookie },
        body: JSON.stringify(eventBody),
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

      const langRows = db.prepare(
        `SELECT l.nome FROM event_languages el JOIN languages l ON el.language_id = l.id WHERE el.event_id = ?`
      ).all(eventId) as Array<{ nome: string }>

      if (langRows.length > 0) {
        const termData = { area: langRows.map(r => r.nome) }
        await fetch(`${mapasUrl}/api/event/${mapasEventId}/terms/area`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Cookie': admCookie },
          body: JSON.stringify(termData),
        }).catch(() => {})
      }

      const tagStr = evt['tags'] as string | null
      if (tagStr) {
        try {
          const tags = JSON.parse(tagStr) as string[]
          if (tags.length > 0) {
            await fetch(`${mapasUrl}/api/event/${mapasEventId}/terms/tag`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'Cookie': admCookie },
              body: JSON.stringify({ tag: tags }),
            }).catch(() => {})
          }
        } catch {}
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
