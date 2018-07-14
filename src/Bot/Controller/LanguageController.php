<?php

declare(strict_types=1);

namespace App\Bot\Controller;

use App\Entity\User;
use BotMan\BotMan\BotMan;
use BotMan\Drivers\Telegram\Extensions\{Keyboard, KeyboardButton};
use Spatie\Emoji\Emoji;
use Symfony\Component\Translation\TranslatorInterface;

class LanguageController implements HasSuggestionInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $availableLanguages;

    public function __construct(TranslatorInterface $translator, array $availableLanguages)
    {
        $this->translator = $translator;
        $this->availableLanguages = $availableLanguages;
    }

    public function showLanguageKeyboard(BotMan $bot): void
    {
        $keyboard = Keyboard::create()->resizeKeyboard();

        $keyboard->addRow(
            KeyboardButton::create('English ' . Emoji::flagForUnitedStates())
                ->callbackData('/language en'),
            KeyboardButton::create('Русский ' . Emoji::flagForRussia())
                ->callbackData('/language ru')
        );

        $bot->reply($this->translator->trans('language.all'), $keyboard->toArray());
    }

    public function switchLanguage(BotMan $bot, string $language): void
    {
        if (!in_array($language, $this->availableLanguages, true)) {
            $bot->reply($this->translator->trans('language.is_not_supported'));
            return;
        }

        /** @var User $user */
        $user = $bot->getMessage()->getExtras('user');
        $user->switchLanguage($language);

        $bot->reply($this->translator->trans('language.changed', [], null, $user->getLanguage()));
    }

    public function getSuggestion(): string
    {
        return '/languages';
    }
}
