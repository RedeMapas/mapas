import { defineMiddleware } from 'astro:middleware';

/**
 * Proxy temporariamente desabilitado.
 * Todas as rotas são renderizadas pelo Astro.
 */
export const onRequest = defineMiddleware(async (context, next) => {
    return next();
});
