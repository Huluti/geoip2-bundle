rector:
	./vendor/bin/rector

test:
	./vendor/bin/phpunit --display-deprecations

analyze:
	./vendor/bin/phpstan analyze --memory-limit=1G

lint:
	./vendor/bin/php-cs-fixer fix
