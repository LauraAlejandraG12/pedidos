# Módulo de pedidos — Reto práctico (25 puntos)

Módulo pequeño de pedidos para una empresa de logística: consultar pedidos,
cambiar su estado y ver cuánto se le vende a cada cliente.

Construido con **Laravel 13 + MySQL 8.4**, todo corriendo en **Docker**.

---

## Levantarlo en un comando

```bash
docker compose up -d --build
```

La primera vez tarda unos minutos (compila las extensiones de PHP e instala
Composer). Al terminar ya quedaron corriendo:

| Servicio | URL / puerto | Qué es |
|---|---|---|
| **Aplicación** | http://localhost:8000 | Listado, formularios y reporte |
| **API JSON** | http://localhost:8000/api/pedidos | Endpoint del punto 3 |
| **phpMyAdmin** | http://localhost:8081 | Base de datos (usuario `pedidos` / contraseña `pedidos`) |
| **MySQL** | localhost:3306 | Base `pedidos` |
| **Tarifas (stub)** | http://localhost:8090 | API simulada de la transportadora |

Al arrancar el contenedor `app` espera a que MySQL esté sano, corre las
migraciones y siembra los datos de prueba (`MIGRATE_ON_START`/`SEED_ON_START`).

### Usuarios de prueba

| Correo | Contraseña | Rol |
|---|---|---|
| `admin@uva3.test` | `password` | Administrador (puede corregir cualquier pedido) |
| `operador@uva3.test` | `password` | Operador (no puede tocar pedidos entregados) |

---

## Endpoints

### Web (sesión)

| Método | Ruta | Qué hace |
|---|---|---|
| `GET` | `/pedidos` | Listado con filtro por estado (`?estado=pendiente`) |
| `GET` | `/pedidos/crear` · `POST /pedidos` | Formulario y creación de pedidos |
| `GET` | `/pedidos/{id}/editar` · `PUT /pedidos/{id}` | Edición |
| `POST` | `/pedidos/{id}/estado` | Cambio de estado |
| `GET` | `/reportes/ventas` | Total vendido por cliente del mes actual |

### API JSON

```bash
# Listado público, opcionalmente filtrado por estado
curl http://localhost:8000/api/pedidos?estado=pendiente
curl http://localhost:8000/api/pedidos/1

# Reporte del mes actual
curl http://localhost:8000/api/reporte/ventas
```

Ejemplo de respuesta de `GET /api/pedidos?estado=pendiente`:

```json
{
  "data": [
    {
      "id": 3,
      "cliente": { "id": 1, "nombre": "Distribuciones El Dorado S.A.S.", "ciudad": "Bogotá" },
      "total": 1250000.0,
      "costo_envio": null,
      "costo_total": 1250000.0,
      "fecha": "2026-10-18",
      "estado": "pendiente",
      "despachado_at": null,
      "entregado_at": null
    }
  ],
  "links": { "first": "...", "last": "..." },
  "meta": { "current_page": 1, "per_page": 15, "total": 7 }
}
```

Un estado inválido responde `422`:

```json
{
  "message": "El estado debe ser pendiente, despachado o entregado.",
  "errors": { "estado": ["El estado \"cancelado\" no es válido."] }
}
```

### Escritura por API (requiere token)

```bash
docker compose exec app php artisan pedidos:token admin@uva3.test

curl -X PATCH http://localhost:8000/api/pedidos/1/estado \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"estado": "despachado"}'
```

---

## Reglas implementadas

1. **Los pedidos entregados no se modifican.** Quien no sea administrador
   recibe `422` en la API y un mensaje de error en el formulario.
2. **El administrador corrige cualquier pedido.** Puede editar datos de un
   pedido entregado y regresarlo a otro estado (al volver a `pendiente` se
   limpian `costo_envio`, `despachado_at` y `entregado_at`).
3. **Al despachar se cotiza el envío.** El sistema llama a
   `POST {TARIFAS_BASE_URL}/v1/cotizar` con `Authorization: Bearer
   {TARIFAS_API_KEY}` y guarda el resultado en `pedidos.costo_envio`.
4. **Si la transportadora falla, no se despacha.** Se responde `502` y el
   pedido sigue `pendiente`, sin costo de envío inventado.
5. **No se despacha dos veces.** El cambio de estado corre dentro de una
   transacción con `lockForUpdate` sobre la fila del pedido.
6. **El reporte usa el mes actual** y ordena de mayor a menor, con los
   clientes sin ventas del mes excluidos.

---

## La transportadora (importante)

El enunciado apunta a `POST https://tarifas.transportadora.test/v1/cotizar`.
Ese dominio **no existe**: `.test` es un TLD reservado para pruebas locales y
la credencial "se entrega por solicitud", es decir, no está en ninguna parte.

En su lugar, la aplicación habla por HTTP real contra una URL configurable:

```
TARIFAS_BASE_URL=http://tarifas:8090   # el stub del docker-compose
TARIFAS_API_KEY=dev-key-transportadora
```

El servicio `tarifas` del compose es un stub PHP (ver `docker/tarifas/router.php`)
que reproduce el mismo contrato: la misma ruta, la misma autenticación por
`Bearer` y una respuesta con `costo_envio`, `moneda` y `eta_dias`.

**Para conectarlo al proveedor real no hay que tocar código:** solo cambia
`TARIFAS_BASE_URL` y `TARIFAS_API_KEY` en el `.env`.

---

## Base de datos

Esquema (siguiendo el modelo de la pregunta 1.4 más los campos que pide este
módulo):

```
clientes            pedidos
----                ----
id                  id
nombre              cliente_id  → clientes.id
ciudad              total
                    fecha
                    estado           (pendiente | despachado | entregado)
                    costo_envio      (lo devuelve la transportadora)
                    despachado_at
                    entregado_at
                    observaciones    (solo el admin la ve)
```

Migraciones en `database/migrations/`, datos de prueba en
`database/seeders/` (12 clientes, ~30 pedidos repartidos entre los estados y
algunos de meses anteriores para demostrar que el reporte filtra por mes).

---

## Tests

```bash
docker compose exec app php artisan test
```

Cubren: filtro del endpoint, estado inválido (`422`), creación de pedidos,
cotización al despachar, fallo de la transportadora (no se despacha),
inmutabilidad de los entregados, corrección del administrador, el reporte del
mes y el orden de mayor a menor.

---

## Comandos útiles

```bash
make up        # levantar todo
make fresh     # migrate:fresh --seed
make test      # correr la suite
make logs      # logs del contenedor de la app
make token     # generar token de API
make down      # parar todo
```
