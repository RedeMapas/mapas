import { defineConfig } from 'astro/config';
import node from '@astrojs/node';

// PHP_API_URL is read from process.env at runtime (SSR). In dev, set via docker-compose or .env.
const devApiUrl = process.env.PHP_API_URL || 'http://localhost:8080';

export default defineConfig({
  output: 'server',
  adapter: node({ mode: 'standalone' }),
  base: '/spa',
  vite: {
    server: {
      allowedHosts: true,
      proxy: {
        '/api': devApiUrl,
        '/assets': devApiUrl,
        '/files': devApiUrl,
      },
    },
  },
});
