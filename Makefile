test:
	./vendor/bin/phpunit --display-deprecations

analyze:
	./vendor/bin/phpstan analyze --memory-limit=1G
