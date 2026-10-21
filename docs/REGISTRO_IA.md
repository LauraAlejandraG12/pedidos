# Registro de uso de IA

El enunciado pide registrar **cada prompt** usado durante el reto. Este
documento lo lleva la persona que resolvió el ejercicio; lo que sigue es el
registro del trabajo de desarrollo con un asistente de IA (OpenCode +
Big Pickle) del 20 de octubre de 2026.

| # | Prompt (resumen o literal) | Para qué |
|---|---|---|
| 1 | "Parte 5 — Reto práctico: módulo de pedidos (25 puntos) … qué tanto crees que nos demoraremos? realmente la bd del punto 1.4 es una porquería … las solicitudes del cliente en cuanto a base de datos fueron dos tablas" | Entregar el enunciado y pedir estimación de tiempo |
| 2 | "revisa mis imagenes primero" / "las imagenes de docker loquito" | Verificar qué imágenes Docker ya existían localmente antes de decidir el stack |
| 3 | "lo de la transportadora es falso, como puedes ver, es testing, evitalo, por cierto, dockeriza todo. recuerda que es un ejercicio" | Confirmar que la API de tarifas es ficticia (dominio `.test`) y dockerizar todo |
| 4 | "disculpa interrumpirte, es que estos tontitos hablan mucho de phpmyadmin, usa MySql o Sqlite solo para darles el gusto" | Cambiar el motor de base de datos a MySQL y agregar phpMyAdmin al compose |
| 5 | "primero haz el servidor y cuando vayamos al punto extra te doy unos caveats para destacar" | Construir primero el backend (sin el frontend React/Angular opcional) |
| 6 | Continuación de la fase de construcción: "sigue" / "continúa" para escribir modelos, migraciones, seeders, servicios (`GestorPedidos`, `TransportadoraHttp`), controladores web y API, políticas, vistas Blade, CSS, rutas, infraestructura Docker (Dockerfile, compose, nginx, entrypoint, stub de tarifas), README y Makefile | Completar el servidor del módulo de pedidos |
| 7 | "los tests fallan, arréglalos" (≈16 de 23 fallando al correr `php artisan test` dentro del contenedor) | Diagnosticar por qué los tests corrían contra MySQL en vez de sqlite en memoria |
| 8 | "corrigiendo assertions de floats" (continuación del arreglo de la suite) | Ajustar aserciones numéricas del reporte (JSON serializa `300000.0` como `300000`) |
| 9 | Depuración de los 4 tests que seguían fallando | Corregir: namespace de la vista paginada (`pagination::pedidos`), trait `HasApiTokens` en `User`, fake de la transportadora y respuesta del API |
| 10 | "termina de dejar todo verde y haz smoke test" | Dejar la suite en verde (23/23) y validar los flujos en vivo con curl |
| 11 | "actualiza el registro de IA con lo que faltaba" | Registrar las incidencias de esta fase (esta sección) |

## Decisiones que tomó la IA (revisadas antes de aplicarlas)

- **MySQL 8.4 + phpMyAdmin en vez de Postgres**, por el requisito del jurado.
- **Stub local de la transportadora** en `docker/tarifas/router.php`: la
  aplicación hace un `POST` HTTP de verdad, solo que `TARIFAS_BASE_URL` apunta
  al contenedor `tarifas`. El código de la app no cambia si se conecta al
  proveedor real.
- **Sanctum** para proteger el endpoint de escritura de la API.
- **Blade con CSS a mano** (sin npm/Vite) para no depender de un build de
  Node dentro de Docker.

## Incidencias resueltas en la fase de pruebas

- **Los tests corrían contra MySQL (y borraron los datos sembrados).**
  `env_file: .env` en el servicio `app` metía `APP_ENV=local` y `DB_*` al
  entorno del SO; Laravel lee `$_SERVER` antes que las variables que PHPUnit
  inyecta en `$_ENV`, así que el `phpunit.xml` perdía. Arreglo: sin `env_file`
  en el servicio, `force="true"` en las entradas del `phpunit.xml` y el
  entrypoint leyendo `DB_*` del `.env` en vez del entorno del contenedor.
  Dato: el entrypoint está horneado en la imagen, hay que hacer
  `docker compose build app` tras editarlo (no basta con recrear).
- **JSON y floats:** `json_encode(300000.0)` sale como `300000`, las aserciones
  de totales usan cast a `float`/`assertEquals`.
- **Vista paginada:** se registra como `pagination::pedidos` (con `::`); con
  punto, el finder la busca en `resources/views/pagination/` y no en el
  override de `resources/views/vendor/pagination/`.
- **`Http::fake()` acumula stubs y gana el primero que calza:** un fake
  `'*' => 200` en `setUp` impedía que el fake de error de un test específico
  aplicara. Se movió a un helper por test más `Http::preventStrayRequests()`
  como red de seguridad (el stub real es alcanzable desde el contenedor).
- **API devolvía el pedido desactualizado:** `GestorPedidos` reconsulta el
  pedido con `lockForUpdate()` dentro de la transacción; el controlador ahora
  devuelve la instancia que retorna el gestor, no la del route binding.
- **`pedidos:token` no aparecía:** error de sintaxis en la creación
  (`\'` al final de una cadena; el backslash final es `\\'`). Laravel omitía
  el archivo roto de la descubrimiento de comandos sin avisar.

## Prompts que faltan por registrar

Cuando se avance con el punto opcional (listado en React o Angular) y con los
"caveats" del ejercicio, agregar nuevas filas acá.
