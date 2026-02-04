SHELL := /bin/bash

ifneq (,$(wildcard .env))
include .env
export
endif

.PHONY: install dev build lint test docs db-init

install:
	composer install --dev
	npm ci

dev:
	@trap 'kill 0' INT TERM EXIT; \
		php -S localhost:8080 -t public/ public/index.php & \
		npm run dev

build:
	npm run build

lint:
	vendor/bin/phpstan analyse src bootstrap public

test:
	vendor/bin/phpunit

docs:
	mkdocs build
	vendor/bin/phpdoc run

db-init:
	PGPASSWORD="$${DB_PASSWORD}" psql "postgresql://$${DB_USERNAME}:$${DB_PASSWORD}@$${DB_HOST:-localhost}:$${DB_PORT:-5432}/$${DB_NAME:-dodomudojade}" -f schema/schema.sql
