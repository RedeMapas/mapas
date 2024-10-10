FROM ghcr.io/railwayapp/nixpacks:ubuntu-1727136237

ENTRYPOINT ["/bin/bash", "-l", "-c"]
WORKDIR /app/


COPY .nixpacks/nixpkgs-dbc4f15b899ac77a8d408d8e0f89fa9c0c5f2b78.nix .nixpacks/nixpkgs-dbc4f15b899ac77a8d408d8e0f89fa9c0c5f2b78.nix
RUN nix-env -if .nixpacks/nixpkgs-dbc4f15b899ac77a8d408d8e0f89fa9c0c5f2b78.nix && nix-collect-garbage -d

COPY .nixpacks/assets /assets/
ARG NIXPACKS_METADATA NIXPACKS_PHP_FALLBACK_PATH NIXPACKS_PHP_ROOT_DIR PORT
ENV NIXPACKS_METADATA=$NIXPACKS_METADATA NIXPACKS_PHP_FALLBACK_PATH=$NIXPACKS_PHP_FALLBACK_PATH NIXPACKS_PHP_ROOT_DIR=$NIXPACKS_PHP_ROOT_DIR PORT=$PORT

# setup phase
# noop

# install phase
COPY . /app/.
RUN  mkdir -p /var/log/nginx && mkdir -p /var/cache/nginx
RUN  composer install --ignore-platform-reqs
RUN  pnpm i --frozen-lockfile

# build phase
COPY . /app/.
RUN  pnpm run build





# start
COPY . /app
CMD ["node /assets/scripts/prestart.mjs /assets/nginx.template.conf /nginx.conf && (php-fpm -y /assets/php-fpm.conf & nginx -c /nginx.conf)"]

