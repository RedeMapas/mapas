import { defineMiddleware } from 'astro:middleware';

const MAPAS_URL = process.env['MAPAS_API_URL'] ?? 'http://localhost:8080';

export const onRequest = defineMiddleware(async (context, next) => {
    const url = context.url.pathname;
    if (url.startsWith('/ingestor')) {
        const cookieHeader = context.request.headers.get('cookie') ?? '';
        const redirectTo = encodeURIComponent(context.url.href);
        if (!cookieHeader) {
            return context.redirect(`/auth/login?redirectTo=${redirectTo}`);
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
                return context.redirect(`/auth/login?redirectTo=${redirectTo}`);
            }
            const data = await res.json();
            if (!Array.isArray(data) || data.length === 0) {
                return context.redirect(`/auth/login?redirectTo=${redirectTo}`);
            }
        } catch {
            return context.redirect(`/auth/login?redirectTo=${redirectTo}`);
        }
    }
    return next();
});
