// ── Entity types ────────────────────────────────────────────────────────────

export interface EntityType {
    id: number;
    name: string;
}

export interface Agent {
    id: number;
    name: string;
    shortDescription: string;
    longDescription: string;
    type: EntityType | null;
    status: number;
    terms?: { area?: string[] };
}

export interface Space {
    id: number;
    name: string;
    shortDescription: string;
    type: EntityType | null;
    status: number;
}

export interface Event {
    id: number;
    name: string;
    shortDescription: string;
    startsOn: string | null;
    status: number;
}

export interface Opportunity {
    id: number;
    name: string;
    shortDescription: string;
    longDescription: string;
    registrationFrom: string | { date: string } | null;
    registrationTo: string | { date: string } | null;
    status: number;
    type: EntityType | null;
    ownerEntity: { id: number; name: string } | null;
}

// ── Fetch helpers ────────────────────────────────────────────────────────────

const phpUrl = (): string => import.meta.env.PHP_API_URL ?? '';

/** Maps API type name → PHP URL slug (e.g. 'agent' → 'agente') */
const TYPE_SLUGS: Record<string, string> = {
    agent: 'agente',
    space: 'espaco',
    event: 'evento',
    opportunity: 'oportunidade',
    project: 'projeto',
};

interface FindOptions {
    select?: string;
    limit?: number;
    order?: string;
    extra?: Record<string, string>;
    cookie?: string;
}

/**
 * Fetches a list of entities from the PHP API (SSR only — runs on server).
 * Returns an empty array on error.
 */
export async function findEntities<T>(
    type: string,
    options: FindOptions = {},
): Promise<T[]> {
    const { select, limit = 6, order, extra = {}, cookie } = options;
    const params = new URLSearchParams({
        'status': 'EQ(1)',
        '@limit': String(limit),
        ...(select ? { '@select': select } : {}),
        ...(order ? { '@order': order } : {}),
        ...extra,
    });

    const headers: Record<string, string> = cookie ? { cookie } : {};

    try {
        const res = await fetch(`${phpUrl()}/api/${type}/find?${params}`, { headers });
        if (!res.ok) return [];
        const data = await res.json();
        return Array.isArray(data) ? data : [];
    } catch (err) {
        console.error(`[api] findEntities ${type}:`, err);
        return [];
    }
}

/**
 * Fetches a single entity by ID from the PHP API (SSR only).
 * Returns null if not found or on error, and sets the status code.
 */
export interface FetchOneResult<T> {
    data: T | null;
    status: number | null;
}

export async function fetchOne<T>(
    type: string,
    id: string | number,
    select: string,
    cookie = '',
): Promise<FetchOneResult<T>> {
    const base = phpUrl();
    if (!base) {
        console.error('[api] PHP_API_URL não definido');
        return { data: null, status: null };
    }
    const slug = TYPE_SLUGS[type] ?? type;
    const params = new URLSearchParams({ '@select': select });
    const headers: Record<string, string> = {
        'X-Requested-With': 'XMLHttpRequest',
        ...(cookie ? { cookie } : {}),
    };
    try {
        const res = await fetch(
            `${base}/${slug}/${id}/single?${params}`,
            { headers },
        );
        const status = res.status;
        if (!res.ok) return { data: null, status };
        const data = await res.json();
        // PHP returns { error: true } for not-found entities
        if (data && typeof data === 'object' && data.error === true) {
            return { data: null, status: 404 };
        }
        return { data, status };
    } catch (err) {
        console.error(`[api] fetchOne ${type}/${id}:`, err);
        return { data: null, status: null };
    }
}

/**
 * Counts entities of a given type (SSR only).
 */
export async function countEntities(type: string): Promise<number> {
    try {
        const res = await fetch(
            `${phpUrl()}/api/${type}/find?status=EQ(1)&@count=1`,
        );
        if (!res.ok) return 0;
        return Number(await res.json()) || 0;
    } catch (err) {
        console.error(`[api] countEntities ${type}:`, err);
        return 0;
    }
}
