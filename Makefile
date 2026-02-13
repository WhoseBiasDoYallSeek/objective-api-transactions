.PHONY: build up down seed test shell

# prefer legacy binary if present, otherwise use the Docker Compose v2 plugin (`docker compose`)
DC := $(shell command -v docker-compose >/dev/null 2>&1 && echo docker-compose || echo "docker compose")

build:
	$(DC) build

up:
	$(DC) up -d --remove-orphans

down:
	$(DC) down

seed:
	$(DC) run --rm slim php scripts/seed_database.php

test:
	$(DC) run --rm slim vendor/bin/phpunit --colors=always

shell:
	$(DC) run --rm --service-ports slim sh
