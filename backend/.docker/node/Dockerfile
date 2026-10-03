FROM node:22-alpine

ARG PNPM_VERSION=11.19.0

RUN npm install --global "pnpm@${PNPM_VERSION}"

WORKDIR /var/www/html

ENTRYPOINT ["pnpm"]
