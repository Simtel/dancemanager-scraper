test: ##Run PHPUnit tests
	./vendor/bin/phpunit --display-errors --no-coverage

phpstan: ##Run phpstan analyse
	./vendor/bin/phpstan analyse --memory-limit=2G

pint: ##Run pint analyse
	./vendor/bin/pint --parallel

install:
	composer install

test-coverage: ## Run PHPUnit tests with coverage
	XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-html build/coverage/html --coverage-text