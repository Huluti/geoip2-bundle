test:
	./vendor/bin/phpunit

analyze:
	./vendor/bin/phpstan analyze --memory-limit=1G
