DC := docker compose
PNPM_VERSION ?= 11.19.0
ADMIN_URL ?= http://localhost:8080/admin

.DEFAULT_GOAL := help

.PHONY: help up down restart build rebuild ps logs shell root-shell artisan composer-install test pint migrate fresh queue-logs admin-image admin-install admin-dev admin-build admin-typecheck admin-check admin-test admin-migrate admin-create admin-setup admin-url

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*## ' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'

up: ## Start all containers
	$(DC) up -d

down: ## Stop and remove all containers
	$(DC) down

restart: down up ## Restart all containers

build: ## Build images without starting containers
	$(DC) build

rebuild: ## Rebuild images from scratch (no cache) and restart containers
	$(DC) build --no-cache
	$(DC) up -d --force-recreate

ps: ## Show container status
	$(DC) ps

logs: ## Tail logs for all containers (use s=<service> for one)
	$(DC) logs -f $(s)

queue-logs: ## Tail queue worker logs
	$(DC) logs -f queue

admin-image: ## Build the isolated Node/pnpm image used by the admin frontend
	$(DC) build --build-arg PNPM_VERSION=$(PNPM_VERSION) frontend

admin-install: admin-image ## Install Vue/Inertia dependencies inside the frontend container
	$(DC) run --rm --no-deps frontend install --frozen-lockfile

admin-dev: up admin-install ## Start Docker services and the Vite dev server for the admin panel
	$(DC) run --rm --no-deps --service-ports frontend run dev -- --host 0.0.0.0

admin-build: admin-install ## Build production assets for the admin panel and Laravel frontend
	$(DC) run --rm --no-deps frontend run build

admin-typecheck: admin-install ## Type-check the Vue/TypeScript frontend
	$(DC) run --rm --no-deps frontend run typecheck

admin-check: admin-typecheck admin-build admin-test ## Run frontend typecheck/build and the Laravel test suite

admin-test: up ## Run the Laravel test suite
	$(DC) exec app php artisan test --compact

admin-migrate: up ## Apply Laravel migrations, including admin-panel tables
	$(DC) exec app php artisan migrate

admin-create: up ## Interactively create an administrator with php artisan admin:create
	$(DC) exec app php artisan admin:create

admin-setup: admin-install admin-build admin-migrate admin-create ## Install deps, build assets, migrate the database, and create the first admin

admin-url: ## Print the local admin panel URL
	@echo "$(ADMIN_URL)"

shell: ## Open a shell in the app container
	$(DC) exec app sh

root-shell: ## Open a root shell in the app container
	$(DC) exec -u root app sh

artisan: ## Run an artisan command, e.g. make artisan cmd="migrate"
	$(DC) exec app php artisan $(cmd)

composer-install: ## Run a composer command, e.g. make composer install
	$(DC) exec app composer install

test: ## Run the test suite
	$(DC) exec app php artisan test --compact

pint: ## Fix code style with Pint
	$(DC) exec app vendor/bin/pint

migrate: ## Run database migrations
	$(DC) exec app php artisan migrate

fresh: ## Drop all tables and re-run migrations
	$(DC) exec app php artisan migrate:fresh
