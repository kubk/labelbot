<?php

declare(strict_types=1);

namespace App\Bot\Middleware;

use BotMan\BotMan\Interfaces\Middleware\Matching;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

class AdminMiddleware implements Matching
{
    /**
     * @var string
     */
    private $adminChatId;

    public function __construct(string $adminChatId)
    {
        $this->adminChatId = $adminChatId;
    }

    public function matching(IncomingMessage $message, $pattern, $regexMatched): bool
    {
        return $regexMatched && (string) $message->getSender() === $this->adminChatId;
    }
}
