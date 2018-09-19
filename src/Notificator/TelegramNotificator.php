<?php

declare(strict_types=1);

namespace App\Notificator;

use App\Entity\User;
use App\Enum\NotificationTransportEnum;
use App\Queue\IssueLabeledEvent;
use BotMan\BotMan\BotMan;
use BotMan\Drivers\Telegram\Extensions\{Keyboard, KeyboardButton};
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class TelegramNotificator implements NotificatorInterface
{
    /**
     * @var BotMan
     */
    private $bot;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(BotMan $bot, TranslatorInterface $translator, LoggerInterface $logger)
    {
        $this->bot = $bot;
        $this->translator = $translator;
        $this->logger = $logger;
    }

    public function shouldNotify(User $user): bool
    {
        return $user->getTelegramId() && $user->getNotificationTransport() === NotificationTransportEnum::TELEGRAM;
    }

    public function notify(User $user, IssueLabeledEvent $event): void
    {
        $message = $this->translator->trans('repository.new_labeled_event', [
            '%repository%' => $event->getRepository()->getUrl(),
            '%issueUrl%' => $event->getIssueUrl(),
            '%label%' => $event->getLabel()->withoutEmoji(),
        ], null, $user->getLanguage());

        $buttonText = $this->translator->trans('repository.go_to_issue', [], null, $user->getLanguage());

        $response = $this->bot->say($message, [$user->getTelegramId()], null, $this->createKeyboard($event, $buttonText));

        if ($response->isClientError()) {
            $this->logger->error((string) $response);
        }
    }

    private function createKeyboard(IssueLabeledEvent $event, string $buttonText): array
    {
        $button = KeyboardButton::create($buttonText)
            ->url($event->getIssueUrl());
        $keyboard = Keyboard::create()
            ->addRow($button);

        return $keyboard->toArray() + ['parse_mode' => 'HTML'];
    }
}
