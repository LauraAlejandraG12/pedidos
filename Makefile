# Atajos para el módulo de pedidos.
# Todo corre dentro de Docker: no necesitas PHP ni Composer en la máquina.

COMPOSE ?= docker compose
APP      ?= $(COMPOSE) run --rm app

.PHONY: help up down rebuild fresh migrate seed test logs shell token tarifas

help: ## Muestra esta ayuda
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

up: ## Levanta el stack completo (app, MySQL, phpMyAdmin, stub de tarifas)
	$(COMPOSE) up -d --build
	@echo ""
	@echo "App:        http://localhost:8000"
	@echo "phpMyAdmin: http://localhost:8081"
	@echo "Tarifas:    http://localhost:8090/healthz"

down: ## Detiene el stack (sin borrar datos)
	$(COMPOSE) down

rebuild: ## Reconstruye las imágenes desde cero
	$(COMPOSE) down --remove-orphans
	$(COMPOSE) up -d --build --force-recreate

fresh: ## Resetea la base de datos y la siembra de nuevo
	$(APP) php artisan migrate:fresh --seed --force

migrate: ## Corre las migraciones pendientes
	$(APP) php artisan migrate --force

seed: ## Siembra los datos de prueba
	$(APP) php artisan db:seed --force

test: ## Corre la suite de pruebas
	$(APP) php artisan test

logs: ## Sigue los logs de la app
	$(COMPOSE) logs -f app

shell: ## Shell dentro del contenedor de la app
	$(COMPOSE) exec app sh

token: ## Genera un token de API para probar los endpoints protegidos
	$(APP) php artisan pedidos:token admin@uva3.test

tarifas: ## Prueba el stub de la transportadora con curl
	curl -s -X POST http://localhost:8090/v1/cotizar \
	  -H "Authorization: Bearer $${TARIFAS_API_KEY:-dev-key-transportadora}" \
	  -H "Content-Type: application/json" \
	  -d '{"destino": "Cali", "peso_kg": 2.5, "subtotal": 850000}'
