<?php

declare(strict_types=1);

namespace App\Bot\Controller;

use App\Entity\User;
use App\Enum\NotificationTransportEnum;
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
        if (!in_array($notificationTransport, NotificationTransportEnum::toArray(), true)) {
            $bot->reply('Invalid notification transport');
            return;
        }

        /** @var User $user */
        $user = $bot->getMessage()->getExtras('user');

        $user->setNotificationTransport($notificationTransport);

        $bot->reply($this->translator->trans('notification.enabled'));

        if ($notificationTransport === NotificationTransportEnum::EMAIL && !$user->isEmailConfirmed()) {
            $bot->reply($this->translator->trans('email.request_confirmation'));
        }
    }

    public function getSuggestion(): string
    {
        return '/notifications';
    }
}
