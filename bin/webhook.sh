#!/bin/bash

set -e

cd `dirname "$0"`
source ../.env

if [[ $# -eq 0 ]]; then
    curl https://api.telegram.org/bot${TELEGRAM_BOT_API_TOKEN}/setWebhook
else
    curl https://api.telegram.org/bot${TELEGRAM_BOT_API_TOKEN}/setWebhook?url=$1
fi