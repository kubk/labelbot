<?php

declare(strict_types=1);

namespace App\Bot\Middleware;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\Interfaces\Middleware\Heard;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

/**
 * Sends "is typing" message to let the user know that the request is being worked on.
 *
 * @see https://core.telegram.org/bots/api#sendchataction
 */
class TypingMiddleware implements Heard
{
    /**
     * {@inheritdoc}
     */
    public function heard(IncomingMessage $message, $next, BotMan $bot)
    {
        $bot->types();

        return $next($message);
    }
}
