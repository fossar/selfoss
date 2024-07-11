analyse-server:
	composer exec -- phpstan analyse --memory-limit 512M

bump-version version:
	utils/bump-version.js {{version}}

check: check-client check-server

check-client:
	just client/check

check-server: lint-server cs-server test-server analyse-server

cs-server:
	composer exec -- php-cs-fixer fix --verbose --dry-run --diff

dev:
	just client/dev

build:
	just client/build

dist:
	python3 utils/create-zipball.py

fix: fix-client fix-server

fix-client:
	just client/fix

fix-server:
	composer exec -- php-cs-fixer fix --verbose --diff

install-dependencies: install-dependencies-client install-dependencies-server

install-dependencies-client:
	npm install --production=false --prefix client/

install-dependencies-server:
	composer install --dev

lint-server:
	composer exec -- parallel-lint src tests

test-server:
	composer exec -- simple-phpunit --bootstrap tests/bootstrap.php tests

test-integration:
	python3 tests/integration/run.py
