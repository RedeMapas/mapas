import { defineConfig } from 'astro/config';
import node from '@astrojs/node';
import tailwindcss from '@tailwindcss/vite';

const devApiUrl = process.env.PHP_API_URL || 'http://localhost:8080';

export default defineConfig({
  output: 'server',
  adapter: node({ mode: 'standalone' }),
  vite: {
    plugins: [tailwindcss()],
    ssr: {
      external: ['better-sqlite3'],
    },
    server: {
      allowedHosts: true,
    },
  },
});
