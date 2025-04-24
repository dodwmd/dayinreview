#!make
.PHONY: help build test lint static-analysis dusk docker-build docker-publish setup

# Include environment variables from .env file if it exists
-include .env

# Set default values for environment variables if they don't exist
GITHUB_REGISTRY ?= ghcr.io
GITHUB_USERNAME ?= dodwmd
APP_NAME ?= dayinreview
TAG ?= latest

help: ## Show this help menu
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

setup: ## Install dependencies and set up project
	./scripts/setup.sh

lint: ## Run Laravel Pint code style fixer
	./scripts/lint.sh

static-analysis: ## Run PHPStan static analysis
	./scripts/static-analysis.sh

dusk: ## Run Laravel Dusk browser tests
	./scripts/dusk.sh

test: lint static-analysis ## Run all tests (lint, static analysis)
	php artisan test
	# Dusk tests are currently disabled due to browser/database configuration issues
	# ./scripts/dusk.sh

build: test ## Build the application for production
	./scripts/build.sh

docker-build: ## Build Docker container
	docker build -t $(GITHUB_REGISTRY)/$(GITHUB_USERNAME)/$(APP_NAME):$(TAG) -f docker/Dockerfile .

docker-publish: docker-build ## Publish Docker container to GitHub Packages
	echo $(GITHUB_TOKEN) | docker login $(GITHUB_REGISTRY) -u $(GITHUB_USERNAME) --password-stdin
	docker push $(GITHUB_REGISTRY)/$(GITHUB_USERNAME)/$(APP_NAME):$(TAG)
