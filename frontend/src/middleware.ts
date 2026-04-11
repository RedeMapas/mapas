import { defineMiddleware } from 'astro:middleware';

const MAPAS_URL = process.env['MAPAS_API_URL'] ?? 'http://localhost:8080';

export const onRequest = defineMiddleware(async (context, next) => {
    const url = context.url.pathname;
    if (url.startsWith('/ingestor')) {
        const cookieHeader = context.request.headers.get('cookie') ?? '';
        if (!cookieHeader) {
            return context.redirect('/auth/login');
        }
        try {
            const res = await fetch(
                `${MAPAS_URL}/api/agent/find?@select=id&user=EQ(@me)&@limit=1&status=GTE(-10)`,
                {
                    headers: {
                        Cookie: cookieHeader,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
            );
            if (!res.ok) {
                return context.redirect('/auth/login');
            }
            const data = await res.json();
            if (!Array.isArray(data) || data.length === 0) {
                return context.redirect('/auth/login');
            }
        } catch {
            return context.redirect('/auth/login');
        }
    }
    return next();
});
