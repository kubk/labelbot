<?php

declare(strict_types=1);

namespace App\Event;

use BotMan\BotMan\BotMan;

/**
 * Notifies admin about new subscriptions.
 */
class NewSubscriptionNotificator
{
    /**
     * @var BotMan
     */
    private $bot;

    /**
     * @var string
     */
    private $adminChatId;

    public function __construct(BotMan $bot, string $adminChatId)
    {
        $this->bot = $bot;
        $this->adminChatId = $adminChatId;
    }

    public function onNewSubscription(NewSubscriptionEvent $event): void
    {
        $message = sprintf(
            'User "%s" subscribed for "%s" - [%s]',
            $event->getTelegramId(),
            $event->getRepository()->getUrl(),
            $event->getLabel()->getNormalizedName()
        );

        $this->bot->say($message, [$this->adminChatId]);
    }
}
