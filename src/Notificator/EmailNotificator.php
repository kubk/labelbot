<?php

declare(strict_types=1);

namespace App\Notificator;

use App\Entity\User;
use App\Enum\NotificationTransportEnum;
use App\Queue\IssueLabeledEvent;
use Symfony\Component\Translation\TranslatorInterface;

class EmailNotificator implements NotificatorInterface
{
    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(\Swift_Mailer $mailer, TranslatorInterface $translator)
    {
        $this->mailer = $mailer;
        $this->translator = $translator;
    }

    public function shouldNotify(User $user): bool
    {
        return $user->isEmailConfirmed() && $user->getNotificationTransport() === NotificationTransportEnum::EMAIL;
    }

    public function notify(User $user, IssueLabeledEvent $event): void
    {
        $messageBody = $this->translator->trans('email.notification.body', [
            '%repository%' => $event->getRepository()->getUrl(),
            '%issueUrl%' => $event->getIssueUrl(),
            '%label%' => $event->getLabel()->withoutEmoji(),
        ], null, $user->getLanguage());

        $messageSubject = $this->translator->trans('email.notification.subject', [], null, $user->getLanguage());

        $message = (new \Swift_Message($messageSubject))
            ->setTo($user->getEmail())
            ->setBody($messageBody, 'text/html');

        $this->mailer->send($message);
    }
}
