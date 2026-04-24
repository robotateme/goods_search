SHELL := /bin/bash

DEFAULT_GOAL := help

HELP_TITLE := Goods Search
HELP_COLOR_RESET := \033[0m
HELP_COLOR_TITLE := \033[1;36m
HELP_COLOR_SECTION := \033[1;33m
HELP_COLOR_TARGET := \033[1;32m
HELP_COLOR_TEXT := \033[0;37m

.PHONY: help setup up down test lint psalm docs sync import

help: ## Show available commands
	@printf "\n$(HELP_COLOR_TITLE)%s$(HELP_COLOR_RESET)\n" "$(HELP_TITLE)"
	@printf "$(HELP_COLOR_TEXT)%s$(HELP_COLOR_RESET)\n\n" "Available make targets:"
	@awk 'BEGIN {FS = ":.*## "; current = ""} \
		/^[a-zA-Z0-9_.-]+:.*## / { \
			target = $$1; \
			description = $$2; \
			if (match(description, /^\[[^]]+\]/)) { \
				section = substr(description, 2, RLENGTH - 2); \
				gsub(/^\[[^]]+\][[:space:]]*/, "", description); \
			} else { \
				section = "General"; \
			} \
			if (section != current) { \
				if (current != "") printf "\n"; \
				printf "$(HELP_COLOR_SECTION)%s$(HELP_COLOR_RESET)\n", section; \
				current = section; \
			} \
			printf "  $(HELP_COLOR_TARGET)%-14s$(HELP_COLOR_RESET) %s\n", target, description; \
		}' $(MAKEFILE_LIST)
	@printf "\n"

setup: ## [Environment] Install dependencies and prepare local app
	composer install
	npm install
	./vendor/bin/sail artisan key:generate
	./vendor/bin/sail artisan migrate
	./vendor/bin/sail artisan db:seed

up: ## [Environment] Start local containers
	./vendor/bin/sail up -d

down: ## [Environment] Stop local containers
	./vendor/bin/sail down

test: ## [Quality] Run application tests
	./vendor/bin/sail artisan test

lint: ## [Quality] Run static analysis
	vendor/bin/phpstan analyse app src tests routes database bootstrap --no-progress --memory-limit=512M --level=8

psalm: ## [Quality] Run Psalm
	vendor/bin/psalm --no-progress

docs: ## [Tooling] Build OpenAPI docs
	composer docs:openapi

sync: ## [Search] Sync products into search storage
	./vendor/bin/sail artisan search:products:sync

import: ## [Search] Import products into search index
	./vendor/bin/sail artisan search:products:import
