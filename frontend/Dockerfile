# ─────────────────────────────────────────────
# Stage 1: Base (shared deps + native modules)
# ─────────────────────────────────────────────
FROM node:22-slim AS base
RUN apt-get update && apt-get install -y --no-install-recommends python3 make g++ && rm -rf /var/lib/apt/lists/*
RUN corepack enable && corepack prepare pnpm@latest --activate
WORKDIR /app
COPY package.json pnpm-lock.yaml ./
# Build native modules from source (avoids prebuilt binary mismatch)
ENV npm_config_build_from_source=true
RUN pnpm install

# ─────────────────────────────────────────────
# Stage 2: Production build
# ─────────────────────────────────────────────
FROM base AS builder
COPY . .
RUN pnpm run build

# ─────────────────────────────────────────────
# Stage 3: Production runtime (for K8s)
# ─────────────────────────────────────────────
FROM node:22-slim AS production
WORKDIR /app
COPY --from=builder /app/package.json ./
COPY --from=builder /app/pnpm-lock.yaml ./
COPY --from=builder /app/node_modules ./node_modules
COPY --from=builder /app/dist ./dist
COPY --from=builder /app/public ./public
COPY --from=builder /app/astro.config.mjs ./
ENV NODE_ENV=production
EXPOSE 4321
CMD ["node", "dist/server/entry.mjs"]

# ─────────────────────────────────────────────
# Stage 4: Development (for Docker Compose)
# ─────────────────────────────────────────────
FROM base AS development
COPY . .
ENV NODE_ENV=development
EXPOSE 4321
CMD ["sh", "-c", "pnpm astro dev --host 0.0.0.0 --port 4321"]
