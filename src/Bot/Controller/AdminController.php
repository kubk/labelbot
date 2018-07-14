<?php

declare(strict_types=1);

namespace App\Bot\Controller;

use BotMan\BotMan\BotMan;
use BotMan\Drivers\Telegram\Extensions\{Keyboard, KeyboardButton};
use Symfony\Component\Translation\TranslatorInterface;

class AdminController
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var HasSuggestionInterface[]|iterable
     */
    private $commandsWithSuggestion;

    public function __construct(TranslatorInterface $translator, iterable $commandsWithSuggestion)
    {
        $this->translator = $translator;
        $this->commandsWithSuggestion = $commandsWithSuggestion;
    }

    /**
     * Generates command suggestions for your bot.
     * Users will see these suggestions when they type / in the chat with your bot.
     *
     * @param BotMan $bot
     */
    public function generateCommandList(BotMan $bot): void
    {
        $commandList = '';
        foreach ($this->commandsWithSuggestion as $command) {
            if ($this->isSuggestionValid($command->getSuggestion())) {
                $bot->reply($this->translator->trans('command.invalid_suggestion', ['%suggestion%' => $command->getSuggestion()]));
                return;
            }

            $commandList .= "\n" . $command->getSuggestion();
        }

        $bot->reply($commandList);
    }

    private function isSuggestionValid(string $suggestion): bool
    {
        // https://core.telegram.org/bots#botfather-commands
        return (bool) preg_match('/\/[\w_]{,32}\s\-\s\w+/', $suggestion);
    }
}
