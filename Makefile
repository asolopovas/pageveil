SHELL := /bin/bash
SLUG  := pageveil
MAIN  := $(SLUG).php
VERSION := $(shell grep -E '^\s*\*\s*Version:' $(MAIN) | awk '{print $$3}')
DIST  := dist
ZIP   := $(DIST)/$(SLUG)-$(VERSION).zip
SVN_URL := https://plugins.svn.wordpress.org/$(SLUG)
SVN_DIR := .svn-build

.PHONY: help install test lint build clean tag release wp-deploy wp-assets-deploy

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN{FS=":.*?## "}{printf "  %-18s %s\n",$$1,$$2}'

install: ## Install composer dev deps
	composer install

test: ## Run PHPUnit
	composer test

lint: ## PHP syntax check
	@find . -path ./vendor -prune -o -name '*.php' -print | xargs -n1 php -l >/dev/null

build: clean ## Build release zip in dist/
	@mkdir -p $(DIST)
	@rm -rf $(DIST)/$(SLUG)
	@mkdir -p $(DIST)/$(SLUG)
	@rsync -a --exclude-from=.distignore ./ $(DIST)/$(SLUG)/
	@cd $(DIST) && zip -qr $(SLUG)-$(VERSION).zip $(SLUG)
	@echo "Built $(ZIP)"

clean: ## Remove build artifacts
	@rm -rf $(DIST) $(SVN_DIR) .phpunit.cache

tag: ## Create and push git tag v$(VERSION)
	@git diff --quiet || (echo "Working tree dirty"; exit 1)
	git tag -a v$(VERSION) -m "Release v$(VERSION)"
	git push origin v$(VERSION)

release: build ## Create GitHub release with zip asset
	gh release create v$(VERSION) $(ZIP) \
		--title "v$(VERSION)" \
		--notes "Release v$(VERSION)" \
		--latest

wp-deploy: build ## Deploy current version to WordPress.org SVN trunk + tag
	@command -v svn >/dev/null || (echo "svn required"; exit 1)
	@rm -rf $(SVN_DIR)
	svn checkout --depth immediates $(SVN_URL) $(SVN_DIR)
	svn update --set-depth infinity $(SVN_DIR)/trunk
	svn update --set-depth immediates $(SVN_DIR)/tags
	rsync -a --delete --exclude-from=.distignore --exclude='.svn' ./ $(SVN_DIR)/trunk/
	cd $(SVN_DIR) && svn add --force trunk && svn status | awk '/^!/{print $$2}' | xargs -r svn delete
	cd $(SVN_DIR) && svn copy trunk tags/$(VERSION)
	cd $(SVN_DIR) && svn commit -m "Release $(VERSION)"

wp-assets-deploy: ## Push .wordpress-org/ assets to SVN /assets
	@command -v svn >/dev/null || (echo "svn required"; exit 1)
	@test -d .wordpress-org || (echo "no .wordpress-org/ directory"; exit 1)
	@rm -rf $(SVN_DIR)-assets
	svn checkout --depth immediates $(SVN_URL) $(SVN_DIR)-assets
	svn update --set-depth infinity $(SVN_DIR)-assets/assets
	rsync -a --delete .wordpress-org/ $(SVN_DIR)-assets/assets/
	cd $(SVN_DIR)-assets && svn add --force assets && svn commit -m "Update assets"
