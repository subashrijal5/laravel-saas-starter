SHELL := /bin/bash
DOCKER_COMPOSE := docker compose

.PHONY: help up down restart logs ps build shell-app \
	install update composer npm artisan migrate test pint \
	npm-dev npm-build keygen fresh

help:
	@echo "Available targets:"
	@echo "  make up             - Start all containers in detached mode"
	@echo "  make down           - Stop and remove containers"
	@echo "  make restart        - Restart containers"
	@echo "  make logs           - Tail container logs"
	@echo "  make ps             - Show container status"
	@echo "  make build          - Rebuild containers"
	@echo "  make install        - Install PHP and Node dependencies"
	@echo "  make update         - Update PHP and Node dependencies"
	@echo "  make composer ARGS='install'      - Run composer command"
	@echo "  make npm ARGS='run dev -- --host 0.0.0.0 --port 5173' - Run npm command on host"
	@echo "  make artisan ARGS='migrate'       - Run artisan command"
	@echo "  make migrate        - Run migrations"
	@echo "  make test           - Run test suite"
	@echo "  make pint           - Run Laravel Pint formatter"
	@echo "  make npm-dev        - Start Vite dev server on host"
	@echo "  make npm-build      - Build frontend assets"
	@echo "  make keygen         - Generate APP_KEY"
	@echo "  make fresh          - Fresh migrate with seed"
	@echo "  make shell-app      - Open shell in app container"

up:
	$(DOCKER_COMPOSE) up -d

down:
	$(DOCKER_COMPOSE) down

restart:
	$(DOCKER_COMPOSE) down
	$(DOCKER_COMPOSE) up -d

logs:
	$(DOCKER_COMPOSE) logs -f

ps:
	$(DOCKER_COMPOSE) ps

build:
	$(DOCKER_COMPOSE) up -d --build

install:
	$(DOCKER_COMPOSE) exec app composer install
	npm install

update:
	$(DOCKER_COMPOSE) exec app composer update
	npm update

composer:
	$(DOCKER_COMPOSE) exec app composer $(ARGS)

npm:
	PATH="$(PWD)/bin:$(PATH)" npm $(ARGS)

artisan:
	$(DOCKER_COMPOSE) exec app php artisan $(ARGS)

migrate:
	$(DOCKER_COMPOSE) exec app php artisan migrate

test:
	$(DOCKER_COMPOSE) exec app php artisan test --compact

pint:
	$(DOCKER_COMPOSE) exec app vendor/bin/pint --dirty --format agent

npm-dev:
	PATH="$(PWD)/bin:$(PATH)" npm run dev -- --host 0.0.0.0 --port 5173

npm-build:
	PATH="$(PWD)/bin:$(PATH)" npm run build

keygen:
	$(DOCKER_COMPOSE) exec app php artisan key:generate

fresh:
	$(DOCKER_COMPOSE) exec app php artisan migrate:fresh --seed

shell-app:
	$(DOCKER_COMPOSE) exec app sh
