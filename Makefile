.RECIPEPREFIX +=

container_php = docker exec -it labelbot_php
container_db = docker exec -it labelbot_db

cs:
  $(container_php) vendor/bin/php-cs-fixer fix

prepare-test-env:
  docker-compose -f docker/docker-compose.dev.yml run --rm php bin/console doctrine:database:create --if-not-exists --env="test" && \
  docker-compose -f docker/docker-compose.dev.yml run --rm php bin/console doctrine:migrations:migrate --env="test"

test:
  $(container_php) vendor/bin/simple-phpunit --stop-on-failure

psalm:
  $(container_php) vendor/bin/psalm --show-info=false

ngrok:
  ngrok http 80

translation:
  $(container_php) \
  bin/console translation:update --dump-messages --force en && \
  bin/console translation:update --dump-messages --force ru

doctrine-diff:
  $(container_php) bin/console doctrine:migrations:diff

doctrine-migrate:
  $(container_php) bin/console doctrine:migrations:migrate

