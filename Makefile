SHELL := /usr/bin/env bash

ifneq (,$(wildcard .env))
include .env
export
endif

.PHONY: install dev build lint test docs db-init

install:
	@export COMPOSER_NO_DEV=1
	composer install
	npm ci

dev:
	export COMPOSER_NO_DEV=0
	@trap 'kill 0' INT TERM EXIT; \
		php -S localhost:9090 -t public/ public/index.php & \
		npm run dev

build:
	@echo "Building frontend"
	@npm run build

lint:
	./vendor/bin/phpstan analyse --memory-limit=2G

test:
	@./vendor/bin/phpunit

docs:
	@mkdocs build
	@./vendor/bin/phpdoc run --no-progress --no-interaction


db-init:
	PGPASSWORD="$${DB_PASSWORD}" psql "postgresql://$${DB_USERNAME}:$${DB_PASSWORD}@$${DB_HOST:-localhost}:$${DB_PORT:-5432}/$${DB_NAME:-dodomudojade}" -f schema/schema.sql
