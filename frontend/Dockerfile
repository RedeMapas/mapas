FROM node:20-alpine
RUN corepack enable && corepack prepare pnpm@latest --activate
WORKDIR /app
COPY package.json pnpm-lock.yaml ./
RUN pnpm install
COPY . .
EXPOSE 4321
CMD ["sh", "-c", "pnpm astro dev --host 0.0.0.0 --port 4321"]
