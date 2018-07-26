### Labelbot [![Build Status](https://travis-ci.org/kubk/labelbot.svg?branch=master)](https://travis-ci.org/kubk/labelbot)

Labelbot is a Telegram bot that allows you to subscribe to a label (for example **"good first issue"** or **"easy pick"**). Whenever an issue is tagged with the label you subscribed to, you will get a notification.
The main purpose of this project is to make a contribution to Open Source for newcomers a little bit easier.

#### Features
- GitHub / BitBucket / GitLab support
- Email / Telegram notifications
- Labelbot can communicate with a user in multiple languages

#### Technology stack
- [Symfony 4](http://symfony.com/)
- [BotMan](https://botman.io)
- [Enqueue](https://enqueue.forma-pro.com/) with RabbitMQ transport
- [Redis](https://redis.io/) is used for persisting ETag headers in order to prevent reaching the X-Rate-Limit imposed by GitHub

### Requirements
- docker
- docker-compose

### Installation
1) `git clone`
2) `docker-compose -f docker/docker-compose.dev.yml up`
3) `docker exec -it labelbot_php composer install`
4) `docker exec -it labelbot_php bin/console doctrine:database:create`
5) `docker exec -it labelbot_php bin/console doctrine:migrations:migrate`

#### Configure Telegram webhook
The easiest way is to use Ngrok:

1) `ngrok http 80`
2) `bin/webhook.sh <ngrok_url_with_https>`

To delete webhook use `bin/webhook.sh`

#### Testing
1) `make prepare-test-env`
2) `make test`
