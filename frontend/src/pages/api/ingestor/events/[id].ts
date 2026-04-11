import type { APIRoute } from 'astro'
import { getDb } from '../../../../lib/ingestor/db.ts'
import { applyAutoApprove } from '../../../../lib/ingestor/match.ts'
import type { ApprovableEvent, MatchStatus } from '../../../../lib/ingestor/types.ts'

export const PATCH: APIRoute = async ({ params, request }) => {
  const id = Number(params.id)
  if (!id || isNaN(id)) {
    return new Response(JSON.stringify({ error: 'Invalid ID' }), { status: 400 })
  }

  const db = getDb()
  const event = db.prepare('SELECT * FROM events WHERE id = ?').get(id) as Record<string, unknown> | undefined
  if (!event) {
    return new Response(JSON.stringify({ error: 'Not found' }), { status: 404 })
  }

  let body: Record<string, unknown>
  try {
    body = await request.json()
  } catch {
    return new Response(JSON.stringify({ error: 'Invalid JSON' }), { status: 400 })
  }

  // Build update map
  const updates: Record<string, unknown> = {}

  if (body['action'] === 'approve') {
    updates['review_status'] = 'approved'
  } else if (body['action'] === 'reject') {
    updates['review_status'] = 'rejected'
  }

  if (body['mapas_space_id'] != null) {
    const spaceId = Number(body['mapas_space_id'])
    updates['mapas_space_id'] = spaceId
    updates['match_status'] = 'matched'
    updates['match_score'] = 100

    const approvable: ApprovableEvent = {
      matchStatus: 'matched',
      mapasSpaceId: spaceId,
      title: event['title'] as string | null,
      startAt: event['start_at'] as string | null,
      endAt: event['end_at'] as string | null,
      descriptionShort: event['description_short'] as string | null,
    }
    const result = applyAutoApprove(approvable)
    updates['review_status'] = result.reviewStatus
    updates['match_note'] = result.matchNote
  }

  const editableFields = ['description_short', 'description_long', 'subtitle', 'price', 'language', 'tags', 'proprietario']
  for (const field of editableFields) {
    if (field in body) updates[field] = body[field] ?? null
  }

  // Re-check auto-approve when description_short changes
  if ('description_short' in updates && !updates['review_status'] && event['review_status'] !== 'rejected') {
    const approvable: ApprovableEvent = {
      matchStatus: (event['match_status'] as MatchStatus) ?? 'pending',
      mapasSpaceId: (updates['mapas_space_id'] ?? event['mapas_space_id']) as number | null,
      title: event['title'] as string | null,
      startAt: event['start_at'] as string | null,
      endAt: event['end_at'] as string | null,
      descriptionShort: updates['description_short'] as string | null,
    }
    const result = applyAutoApprove(approvable)
    updates['review_status'] = result.reviewStatus
    updates['match_note'] = result.matchNote
  }

  if (Object.keys(updates).length === 0) {
    return new Response(JSON.stringify({ error: 'No valid fields to update' }), { status: 400 })
  }

  // Build SET clause with positional params
  const keys = Object.keys(updates)
  const setClauses = keys.map(k => `${k} = ?`).join(', ')
  const values = keys.map(k => updates[k])

  db.prepare(`UPDATE events SET ${setClauses} WHERE id = ?`).run(...values, id)

  const updated = db.prepare('SELECT * FROM events WHERE id = ?').get(id)
  return new Response(JSON.stringify(updated), {
    status: 200,
    headers: { 'Content-Type': 'application/json' },
  })
}
