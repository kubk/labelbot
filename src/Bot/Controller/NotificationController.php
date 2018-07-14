<?php

declare(strict_types=1);

namespace App\Bot\Controller;

use App\Entity\NotificationTransport;
use App\Entity\User;
use BotMan\BotMan\BotMan;
use BotMan\Drivers\Telegram\Extensions\{Keyboard, KeyboardButton};
use Spatie\Emoji\Emoji;
use Symfony\Component\Translation\TranslatorInterface;

class NotificationController implements HasSuggestionInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function showNotifications(BotMan $bot): void
    {
        $keyboard = Keyboard::create()
            ->resizeKeyboard();

        $keyboard->addRow(
            KeyboardButton::create('E-Mail ' . Emoji::eMailSymbol())
                ->callbackData('/notification email'),
            KeyboardButton::create('Telegram ' . Emoji::smallAirplane())
                ->callbackData('/notification telegram')
        );

        $bot->reply($this->translator->trans('notification.all'), $keyboard->toArray());
    }

    public function enable(BotMan $bot, string $notificationTransport): void
    {
        /** @var User $user */
        $user = $bot->getMessage()->getExtras('user');

        $user->enableTransport(new NotificationTransport($notificationTransport));

        $bot->reply($this->translator->trans('notification.enabled'));

        if (!$user->isEmailConfirmed()) {
            $bot->reply($this->translator->trans('email.request_confirmation'));
        }
    }

    public function getSuggestion(): string
    {
        return '/notifications';
    }
}
