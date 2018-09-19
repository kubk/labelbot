<?php

declare(strict_types=1);

namespace App\Tests\Event;

use App\Event\EmailConfirmationRequestedEvent;
use App\Event\EmailConfirmationSender;
use App\Tests\AbstractTestCase;

class EmailConfirmationSenderTest extends AbstractTestCase
{
    /**
     * @var EmailConfirmationSender
     */
    private $emailConfirmationSender;

    /**
     * @var \Swift_Plugins_MessageLogger
     */
    private $messageLogger;

    public function setUp(): void
    {
        parent::setUp();

        $mailer = self::$container->get('mailer');
        $this->messageLogger = new \Swift_Plugins_MessageLogger();
        $mailer->registerPlugin($this->messageLogger);

        $this->emailConfirmationSender = new EmailConfirmationSender(
            self::$container->get('router'),
            self::$container->get('translator'),
            $mailer
        );
    }

    public function testItGeneratesConfirmationLinkForUser(): void
    {
        $confirmationCode = 'secret';
        $event = new EmailConfirmationRequestedEvent('test@email.com', $confirmationCode, 'en');
        $this->emailConfirmationSender->onEmailConfirmationRequested($event);

        /** @var \Swift_Message[] $messages */
        $messages = $this->messageLogger->getMessages();

        $this->assertCount(1, $messages);
        $this->assertContains($confirmationCode, $messages[0]->getBody());
    }
}
