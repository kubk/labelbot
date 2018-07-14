<?php

declare(strict_types=1);

namespace App\Bot\Controller;

/**
 * Bot command that has a suggestion. Users will see these suggestions when they type / in the chat with your bot.
 * These suggestions are used to generate messages for BotFather.
 *
 * @see AdminController
 * @see https://core.telegram.org/bots#botfather-commands
 */
interface HasSuggestionInterface
{
    public function getSuggestion(): string;
}
