.PHONY: help build up down logs exec test lint lint-fix migrate

help:
	@echo "Molunzaka Streaming Platform - Development Commands"
	@echo ""
	@echo "Usage:"
	@echo "  make build           Build Docker containers"
	@echo "  make up              Start all containers"
	@echo "  make down            Stop and remove containers"
	@echo "  make logs            View container logs"
	@echo "  make exec COMMAND    Execute command in app container"
	@echo "  make test            Run test suite"
	@echo "  make lint            Run code linter (Pint)"
	@echo "  make lint-fix        Auto-fix code style"
	@echo "  make migrate         Run database migrations"
	@echo "  make seed            Seed database"
	@echo "  make fresh           Fresh migration with seed"
	@echo "  make tinker          Start Tinker REPL"
	@echo ""

build:
	docker compose build

up:
	docker compose up -d
	@echo "Containers are now running!"
	@echo "API: http://localhost:8000"
	@echo "Admin: http://localhost:8000/admin"

down:
	docker compose down

logs:
	docker compose logs -f app

exec:
	@read -p "Enter command: " cmd; \
	docker compose exec app $$cmd

test:
	docker compose exec app php artisan test

lint:
	docker compose exec app composer lint

lint-fix:
	docker compose exec app composer lint -- --fix

migrate:
	docker compose exec app php artisan migrate

seed:
	docker compose exec app php artisan db:seed

fresh:
	docker compose exec app php artisan migrate:fresh --seed

tinker:
	docker compose exec app php artisan tinker

install:
	docker compose exec app composer install

cache-clear:
	docker compose exec app php artisan cache:clear
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan view:clear

queue-work:
	docker compose exec app php artisan queue:work redis

horizon:
	docker compose exec app php artisan horizon
