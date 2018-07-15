<?php

declare(strict_types=1);

namespace App\Tests\Queue;

use App\Notificator\TelegramNotificator;
use App\Queue\IssueLabeledConsumer;
use App\Queue\IssueLabeledEvent;
use App\Repository\UserRepository;
use App\Tests\AbstractTestCase;
use App\ValueObject\{Label, Repository};
use Enqueue\Util\JSON;
use Interop\Queue\PsrContext;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Psr\Log\NullLogger;

class IssueLabeledConsumerTest extends AbstractTestCase
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->userRepository = $this->container->get(UserRepository::class);
    }

    /**
     * @dataProvider provideSubscriptionsWithExpectedSubscribers
     *
     * @param Repository $repository
     * @param Label      $label
     * @param int        $subscribersCount
     *
     * @throws \Exception
     */
    public function testAllEventsAreProcessedCorrectly(Repository $repository, Label $label, int $subscribersCount): void
    {
        $this->loadFixtures();

        /** @var TelegramNotificator|MockObject $telegramNotificatorMock */
        $telegramNotificatorMock = $this->createMock(TelegramNotificator::class);
        $telegramNotificatorMock->expects($this->exactly($subscribersCount))->method('notify');
        $telegramNotificatorMock->method('shouldNotify')->willReturn(true);

        $issueLabeledConsumer = new IssueLabeledConsumer($this->userRepository, new NullLogger(), [$telegramNotificatorMock]);

        /** @var PsrContext $context */
        $context = $this->container->get('enqueue.events.context');
        $issueLabeledEvent = new IssueLabeledEvent(
            $repository,
            $label,
            'https://github.com/user/repo/issue/1'
        );
        $message = $context->createMessage(Json::encode($issueLabeledEvent));

        $issueLabeledConsumer->process($message, $context);
    }

    public function provideSubscriptionsWithExpectedSubscribers(): array
    {
        return [
            [new Repository('https://github.com/kubk/image-pixel-manipulation'), new Label('docs'), 1],
            [new Repository('https://github.com/symfony/symfony'), new Label('docs'), 1],
            [new Repository('https://github.com/symfony/symfony'), new Label('easy pick'), 3],
            [new Repository('https://github.com/symfony/symfony'), new Label('help wanted'), 2],
            [new Repository('https://github.com/kubk/wave-algo'), new Label('easy pick'), 2],
        ];
    }
}
