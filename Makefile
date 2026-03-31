DC=docker compose -f docker-compose-dev.yml

up:
	$(DC) up -d --build

down:
	$(DC) down

install:
	$(DC) exec -T php php radaptor.php install --json

test:
	$(DC) exec -T -e XDEBUG_MODE=off php phpunit

phpstan:
	$(DC) exec -T -e XDEBUG_MODE=off php phpstan analyze

.PHONY: up down install test phpstan
