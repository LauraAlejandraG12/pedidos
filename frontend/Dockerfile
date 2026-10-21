# syntax=docker/dockerfile:1

# Build estático del listado en Angular.
FROM node:24-alpine AS build

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

# Nginx sirve el build y hace de proxy hacia Laravel para /api.
FROM nginx:1.27-alpine

COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist/pedidos-ui/browser /usr/share/nginx/html

EXPOSE 80
