<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class EmailConfirmationSender
{
    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    public function __construct(
        RouterInterface $router,
        TranslatorInterface $translator,
        \Swift_Mailer $mailer
    ) {
        $this->router = $router;
        $this->translator = $translator;
        $this->mailer = $mailer;
    }

    public function onEmailConfirmationRequested(EmailConfirmationRequestedEvent $event): void
    {
        $link = $this->router->generate('email_confirm', [
            'confirmationToken' => $event->getConfirmationToken(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $messageBody = $this->translator->trans('email.confirmation_requested', [
            '%link%' => $link,
        ], null, $event->getLanguage());

        $messageSubject = $this->translator->trans('email.confirmation.subject', [], null, $event->getLanguage());

        $message = (new \Swift_Message($messageSubject))
            ->setTo($event->getEmail())
            ->setBody($messageBody, 'text/html');

        $this->mailer->send($message);
    }
}
