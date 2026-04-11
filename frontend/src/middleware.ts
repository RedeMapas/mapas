import { defineMiddleware } from 'astro:middleware';

export const onRequest = defineMiddleware(async (context, next) => {
    const url = context.url.pathname;
    if (url.startsWith('/ingestor')) {
        const admCookie = context.cookies.get('mapasculturais.adm');
        if (!admCookie?.value) {
            return context.redirect('/auth/login');
        }
    }
    return next();
});
