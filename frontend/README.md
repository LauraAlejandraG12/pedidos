# Listado de pedidos (Angular)

Punto extra del reto: listado de pedidos en Angular 22 que consume
`GET /api/pedidos?estado=…` del backend Laravel.

## Ejecución

Forma parte del `docker-compose.yml` de la raíz:

```sh
docker compose up -d --build frontend
```

Queda en <http://localhost:4200>. El nginx del contenedor sirve el build
estático y hace proxy de `/api/` hacia el servicio `web`, así que la app
comparte origen con Laravel y no necesita CORS.

## Desarrollo local (opcional)

```sh
npm install
npm start        # usa proxy.conf.json -> http://localhost:8000
npm test         # Vitest
npm run build
```

La app es de **solo lectura**: el listado y su filtro por estado. Las
operaciones (crear, editar, cambiar estado) viven en el panel Laravel.
