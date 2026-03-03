test: ##Run PHPUnit tests
	./vendor/bin/phpunit

phpstan: ##Run phpstan analyse
	./vendor/bin/phpstan analyse --memory-limit=2G

pint: ##Run pint analyse
	./vendor/bin/pint --parallel

