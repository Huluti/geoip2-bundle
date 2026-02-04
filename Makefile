rector:
	./vendor/bin/rector

test:
	./vendor/bin/phpunit

analyze:
	./vendor/bin/phpstan analyze --memory-limit=1G

lint:
	./vendor/bin/php-cs-fixer fix
