import type { Connector, RawEvent } from './types.ts'

interface SymplaRawEvent {
  id: number
  name: string
  url: string
  start_date: string
  end_date: string
  start_time?: string
  end_time?: string
  images?: { original?: string; lg?: string }
  location?: {
    name?: string
    city?: string
    state?: string
    lat?: number
    lon?: number
    address?: string
    zip_code?: string
  }
  organizer?: { name?: string; id?: string }
  type?: string
  event_type?: string
  description?: string
  description_short?: string
  price?: string
  accessible?: boolean
  age_rating?: string
  phone?: string
  email?: string
  website?: string
}

const CITY_SLUGS: Record<string, string> = {
  'Fortaleza': 'fortaleza-ce',
  'São Paulo': 'sao-paulo-sp',
  'Rio de Janeiro': 'rio-de-janeiro-rj',
}

function parseRscEvents(html: string): SymplaRawEvent[] {
  const blockRe = /self\.__next_f\.push\(\[1,"((?:[^"\\]|\\.)*)"\]\)/g
  const events: SymplaRawEvent[] = []
  const seen = new Set<number>()

  let match: RegExpExecArray | null
  while ((match = blockRe.exec(html)) !== null) {
    let decoded: string
    try {
      decoded = JSON.parse(`"${match[1]}"`)
    } catch {
      continue
    }

    const eventRe = /\{"end_date":"[^"]+","images":\{/g
    let em: RegExpExecArray | null
    while ((em = eventRe.exec(decoded)) !== null) {
      let depth = 0
      let end = em.index
      for (let i = em.index; i < decoded.length; i++) {
        if (decoded[i] === '{') depth++
        else if (decoded[i] === '}') {
          depth--
          if (depth === 0) { end = i + 1; break }
        }
      }

      try {
        const evt: SymplaRawEvent = JSON.parse(decoded.slice(em.index, end))
        if (evt.id && evt.name && evt.start_date && !seen.has(evt.id)) {
          seen.add(evt.id)
          events.push(evt)
        }
      } catch {
        // malformed JSON fragment — skip
      }
    }
  }

  return events
}

function mapToRawEvent(e: SymplaRawEvent): RawEvent {
  return {
    externalId: String(e.id),
    sourceUrl: e.url,
    title: e.name,
    descriptionShort: e.description_short ?? e.description ?? undefined,
    startAt: e.start_date,
    endAt: e.end_date,
    startTime: e.start_time ?? undefined,
    endTime: e.end_time ?? undefined,
    price: e.price ?? undefined,
    avatarUrl: e.images?.original ?? e.images?.lg ?? undefined,
    venueName: e.location?.name ?? undefined,
    venueAddress: e.location?.address ?? undefined,
    venueCity: e.location?.city ?? undefined,
    telefone: e.phone ?? undefined,
    email: e.email ?? undefined,
    site: e.website ?? undefined,
    cep: e.location?.zip_code ?? undefined,
    acessibilidade: e.accessible ?? undefined,
    classificacaoEtaria: e.age_rating ?? undefined,
    latitude: e.location?.lat ?? undefined,
    longitude: e.location?.lon ?? undefined,
    links: e.url ? { sympla: e.url } : undefined,
  }
}

export class SymplaConnector implements Connector {
  platform = 'sympla' as const

  async fetchEvents(city: string): Promise<RawEvent[]> {
    const slug = CITY_SLUGS[city] ?? city.toLowerCase().replace(/\s+/g, '-')
    const url = `https://www.sympla.com.br/eventos/${slug}`

    console.log(`[sympla] Fetching ${url}`)

    const res = await fetch(url, {
      headers: {
        'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language': 'pt-BR,pt;q=0.9',
      },
    })

    if (!res.ok) {
      throw new Error(`Sympla fetch failed: HTTP ${res.status} for ${url}`)
    }

    const html = await res.text()
    const parsed = parseRscEvents(html)

    console.log(`[sympla] Parsed ${parsed.length} events from RSC payload`)

    const stateCode = slug.split('-').pop()?.toUpperCase()
    const filtered = parsed.filter(e => {
      const state = e.location?.state?.toUpperCase()
      const eventCity = e.location?.city?.toLowerCase()
      if (stateCode && state === stateCode) return true
      if (eventCity && city && eventCity.includes(city.toLowerCase())) return true
      return false
    })

    console.log(`[sympla] Filtered to ${filtered.length} events for city=${city}`)

    return filtered.map(mapToRawEvent)
  }
}
