.RECIPEPREFIX +=

cs:
  vendor/bin/php-cs-fixer fix

prepare-test-env:
  bin/console doctrine:database:create --if-not-exists --env="test" && \
  bin/console doctrine:migrations:migrate --env="test"

test:
  php vendor/bin/simple-phpunit --stop-on-failure

psalm:
  vendor/bin/psalm --show-info=false

ngrok:
  ngrok http 80

translation:
  bin/console translation:update --dump-messages --force en && \
  bin/console translation:update --dump-messages --force ru
