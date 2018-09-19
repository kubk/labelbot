<?php

declare(strict_types=1);

namespace App\Tests\Notificator;

use App\Entity\User;
use App\Entity\{Label, Repository};
use App\Enum\NotificationTransportEnum;
use App\Notificator\TelegramNotificator;
use App\Queue\IssueLabeledEvent;
use App\Tests\AbstractTestCase;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\Tests\FakeDriver;
use Psr\Log\NullLogger;

class TelegramNotificatorTest extends AbstractTestCase
{
    /**
     * @var TelegramNotificator
     */
    private $telegramNotificator;

    /**
     * @var FakeDriver
     */
    private $driver;

    public function setUp(): void
    {
        parent::setUp();
        $this->driver = new FakeDriver();
        $bot = BotManFactory::create([]);
        $bot->setDriver($this->driver);

        $this->telegramNotificator = new TelegramNotificator(
            $bot,
            self::$container->get('translator'),
            new NullLogger()
        );
    }

    public function testItDoesNotSendNotificationWhenTelegramTransportIsNotEnabled(): void
    {
        $user = new User('1');
        $user->setNotificationTransport(NotificationTransportEnum::EMAIL);
        $this->assertFalse($this->telegramNotificator->shouldNotify($user));
    }

    public function testItNotifiesAboutIssueLabeledEvent(): void
    {
        $event = new IssueLabeledEvent(
            new Repository('https://github.com/symfony/symfony'),
            new Label('docs'),
            'https://github.com/symfony/symfony/issues/2'
        );

        $user = new User('1');
        $this->assertTrue($this->telegramNotificator->shouldNotify($user));
        $this->telegramNotificator->notify($user, $event);
        $this->assertCount(1, $this->driver->getBotMessages());
    }
}
