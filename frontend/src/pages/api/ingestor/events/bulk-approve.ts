import type { APIRoute } from 'astro'
import { getDb } from '../../../../lib/ingestor/db.ts'

export const POST: APIRoute = async ({ request }) => {
  let body: { ids?: unknown }
  try {
    body = await request.json()
  } catch {
    return new Response(JSON.stringify({ error: 'Invalid JSON' }), { status: 400 })
  }

  const ids = body.ids
  if (!Array.isArray(ids) || ids.length === 0) {
    return new Response(JSON.stringify({ error: 'ids must be a non-empty array' }), { status: 400 })
  }

  const numericIds = ids.map(Number).filter(n => !isNaN(n) && n > 0)
  if (numericIds.length === 0) {
    return new Response(JSON.stringify({ error: 'No valid IDs provided' }), { status: 400 })
  }

  const db = getDb()
  const placeholders = numericIds.map(() => '?').join(',')

  // Curator bulk-approve: always sets to 'approved' regardless of auto-approve logic
  const bulkApprove = db.transaction(() => {
    db.prepare(
      `UPDATE events SET review_status = 'approved'
       WHERE id IN (${placeholders}) AND review_status NOT IN ('rejected')`
    ).run(...numericIds)
  })

  bulkApprove()

  const approved = (db.prepare(
    `SELECT changes() as n`
  ).get() as { n: number }).n

  return new Response(
    JSON.stringify({ approved, total: numericIds.length }),
    { status: 200, headers: { 'Content-Type': 'application/json' } },
  )
}
