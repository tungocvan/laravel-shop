# syntax=docker/dockerfile:1.7
ARG NODE_VERSION=22
FROM node:${NODE_VERSION}-bookworm-slim
WORKDIR /var/www/socket
COPY socket/package.json socket/package-lock.json ./
RUN npm ci --omit=dev
COPY socket ./
RUN chown -R node:node /var/www/socket
USER node
CMD ["node", "server.js"]
