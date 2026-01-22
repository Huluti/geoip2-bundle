test:
	./vendor/bin/phpunit

analyze:
	php ./vendor/bin/phpstan analyze --memory-limit=2G
