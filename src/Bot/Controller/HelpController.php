<?php

declare(strict_types=1);

namespace App\Bot\Controller;

use BotMan\BotMan\BotMan;
use BotMan\Drivers\Telegram\Extensions\{Keyboard, KeyboardButton};
use Spatie\Emoji\Emoji;
use Symfony\Component\Translation\TranslatorInterface;

class HelpController implements HasSuggestionInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $botName;

    /**
     * @var HasSuggestionInterface[]|iterable
     */
    private $commandsWithSuggestion;

    public function __construct(TranslatorInterface $translator, string $botName, iterable $commandsWithSuggestion)
    {
        $this->translator = $translator;
        $this->botName = $botName;
        $this->commandsWithSuggestion = $commandsWithSuggestion;
    }

    public function showStartKeyboard(BotMan $bot): void
    {
        $message = $this->translator->trans('hello', ['%emoji%' => Emoji::wavingHandSign()]);
        $bot->reply($message, $this->createKeyboard());
    }

    private function createKeyboard(): array
    {
        $keyboard = Keyboard::create()
            ->resizeKeyboard()
            ->addRow(
                KeyboardButton::create('Rate ' . Emoji::whiteMediumStar())
                    ->url('https://telegram.me/storebot?start=' . $this->botName),
                KeyboardButton::create('GitHub ' . Emoji::bookmark())
                    ->url('https://github.com/kubk/labelbot')
            );

        return $keyboard->toArray() + ['parse_mode' => 'HTML'];
    }

    public function showHelp(BotMan $bot): void
    {
        $bot->reply($this->translator->trans('command.unrecognized'));

        $commandsText = '';
        foreach ($this->commandsWithSuggestion as $command) {
            $suggestion = $command->getSuggestion();
            $commandsText .= "\n" . $this->translator->trans('suggestion.' . ltrim($suggestion, '/'));
        }

        $bot->reply($this->translator->trans('command.all', ['%commands%' => $commandsText]));
    }

    public function getSuggestion(): string
    {
        return '/help';
    }
}
