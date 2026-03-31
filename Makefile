DC=docker compose -f docker-compose-dev.yml

init:
	./bin/init.sh $(INIT_ARGS)

up:
	$(DC) up -d --build

down:
	$(DC) down

composer-install:
	$(DC) exec -T php composer install

install:
	$(DC) exec -T php php radaptor.php install --json

update:
	$(DC) exec -T php php radaptor.php update --json

test:
	$(DC) exec -T -e XDEBUG_MODE=off php phpunit

phpstan:
	$(DC) exec -T -e XDEBUG_MODE=off php phpstan analyze

.PHONY: init up down composer-install install update test phpstan
