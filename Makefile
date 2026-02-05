.PHONY: help install dev up down restart build logs shell test migrate fresh seed

# Default target
help:
	@echo "StoryVerse Development Commands"
	@echo ""
	@echo "  make install     - First time setup (build, install deps, migrate)"
	@echo "  make dev         - Start development environment"
	@echo "  make up          - Start all containers"
	@echo "  make down        - Stop all containers"
	@echo "  make restart     - Restart all containers"
	@echo "  make build       - Rebuild Docker images"
	@echo "  make logs        - View container logs"
	@echo ""
	@echo "  make shell       - Open shell in app container"
	@echo "  make shell-fe    - Open shell in frontend container"
	@echo ""
	@echo "  make test        - Run backend tests"
	@echo "  make test-fe     - Run frontend tests"
	@echo ""
	@echo "  make migrate     - Run database migrations"
	@echo "  make fresh       - Fresh migration with seeds"
	@echo "  make seed        - Run database seeders"
	@echo ""
	@echo "  make composer    - Run composer command (use: make composer cmd='install')"
	@echo "  make artisan     - Run artisan command (use: make artisan cmd='cache:clear')"
	@echo "  make npm         - Run npm command (use: make npm cmd='install')"

# First time setup
install:
	@echo "🚀 Setting up StoryVerse..."
	@cp -n .env.example .env 2>/dev/null || true
	@cp -n backend/.env.example backend/.env 2>/dev/null || true
	docker compose build
	docker compose up -d postgres redis
	@echo "⏳ Waiting for services..."
	@sleep 10
	docker compose run --rm app composer install
	docker compose run --rm app php artisan key:generate
	docker compose run --rm app php artisan migrate
	docker compose up -d
	@echo "✅ StoryVerse is ready!"
	@echo "   Backend:  http://localhost:8888"
	@echo "   Frontend: http://localhost:5173"
	@echo "   RabbitMQ: http://localhost:25673"

# Development
dev: up
	@echo "🚀 StoryVerse is running!"
	@echo "   Backend:  http://localhost:8888"
	@echo "   Frontend: http://localhost:5173"

up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose restart

build:
	docker compose build --no-cache

logs:
	docker compose logs -f

logs-app:
	docker compose logs -f app

logs-worker:
	docker compose logs -f worker

# Shell access
shell:
	docker compose exec app sh

shell-fe:
	docker compose exec frontend sh

shell-db:
	docker compose exec postgres psql -U storyverse -d storyverse

# Testing
test:
	docker compose exec app php artisan test

test-fe:
	docker compose exec frontend npm run test:unit

test-e2e:
	docker compose exec frontend npm run test:e2e

# Database
migrate:
	docker compose exec app php artisan migrate

fresh:
	docker compose exec app php artisan migrate:fresh --seed

seed:
	docker compose exec app php artisan db:seed

# Composer & Artisan
composer:
	docker compose exec app composer $(cmd)

artisan:
	docker compose exec app php artisan $(cmd)

npm:
	docker compose exec frontend npm $(cmd)

# Code quality
lint:
	docker compose exec app ./vendor/bin/pint

lint-fe:
	docker compose exec frontend npm run lint

format:
	docker compose exec frontend npm run format

# Cache
cache:
	docker compose exec app php artisan config:cache
	docker compose exec app php artisan route:cache
	docker compose exec app php artisan view:cache

cache-clear:
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan route:clear
	docker compose exec app php artisan view:clear
	docker compose exec app php artisan cache:clear

# Production build
prod-build:
	docker compose exec frontend npm run build

# IDE helpers (PHPStorm/VSCode)
ide:
	docker compose exec app php artisan ide-helper:generate
	docker compose exec app php artisan ide-helper:models -N
	docker compose exec app php artisan ide-helper:meta
