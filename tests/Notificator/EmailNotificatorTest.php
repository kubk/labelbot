<?php

declare(strict_types=1);

namespace App\Tests\Notificator;

use App\Entity\User;
use App\Entity\{Label, Repository};
use App\Enum\NotificationTransportEnum;
use App\Notificator\EmailNotificator;
use App\Queue\IssueLabeledEvent;
use App\Tests\AbstractTestCase;

class EmailNotificatorTest extends AbstractTestCase
{
    /**
     * @var \Swift_Plugins_MessageLogger
     */
    private $messageLogger;

    /**
     * @var EmailNotificator
     */
    private $emailNotificator;

    public function setUp(): void
    {
        parent::setUp();
        $this->messageLogger = new \Swift_Plugins_MessageLogger();
        $mailer = self::$container->get('mailer');
        $mailer->registerPlugin($this->messageLogger);

        $translator = self::$container->get('translator');
        $this->emailNotificator = new EmailNotificator($mailer, $translator);
    }

    public function testItDoesNotSendEmailWhenUserIsNotConfirmed(): void
    {
        $unconfirmedUser = new User('2');
        $unconfirmedUser->setNotificationTransport(NotificationTransportEnum::EMAIL);
        $unconfirmedUser->requestEmailConfirmation('test2@email.com');
        $this->assertFalse($this->emailNotificator->shouldNotify($unconfirmedUser));
    }

    public function testItNotifiesAboutIssueLabeledEvent(): void
    {
        $event = new IssueLabeledEvent(
            new Repository('https://github.com/symfony/symfony'),
            new Label('docs'),
            'https://github.com/symfony/symfony/issues/2'
        );
        $user = new User('2');
        $user->setNotificationTransport(NotificationTransportEnum::EMAIL);
        $user->requestEmailConfirmation('test@email.com');
        $user->confirmEmail();

        $this->assertTrue($this->emailNotificator->shouldNotify($user));
        $this->emailNotificator->notify($user, $event);
        $this->assertCount(1, $this->messageLogger->getMessages());
    }
}
