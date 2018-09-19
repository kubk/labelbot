<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Bot\WebhookProcessor;
use App\Entity\IssueLabeledSubscription;
use App\Enum\NotificationTransportEnum;
use App\Repository\{SubscriptionRepository, UserRepository};
use App\Tests\AbstractTestCase;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Drivers\Tests\FakeDriver;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;

class WebhookProcessorTest extends AbstractTestCase
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * @var \Swift_Plugins_MessageLogger
     */
    private $messageLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = self::$container->get(UserRepository::class);
        $this->subscriptionRepository = self::$container->get(SubscriptionRepository::class);

        $this->messageLogger = new \Swift_Plugins_MessageLogger();
        self::$container->get('mailer')->registerPlugin($this->messageLogger);
    }

    public function testUserCanStartConversation(): void
    {
        $userId = '1';
        $outgoingMessage = $this->sendMessageToBot('/start', $userId);
        $this->assertNotEmpty($outgoingMessage->getText());
    }

    public function testUserCanSwitchLanguage(): void
    {
        $this->loadFixtures();
        $userId = '1';
        $user = $this->userRepository->findByTelegramId($userId);
        $this->assertNotEquals('ru', $user->getLanguage());

        $outgoingMessage = $this->sendMessageToBot('/language ru', $userId);
        $this->assertNotEmpty($outgoingMessage->getText());
        $user = $this->userRepository->findByTelegramId($userId);
        $this->assertEquals('ru', $user->getLanguage());
    }

    public function testUserCanNotSwitchToNotSupportedLanguage(): void
    {
        $this->loadFixtures();
        $userId = '1';
        $user = $this->userRepository->findByTelegramId($userId);

        $languageBeforeRequest = $user->getLanguage();
        $this->sendMessageToBot('/language invalid', $userId);
        $user = $this->userRepository->findByTelegramId($userId);
        $this->assertEquals($languageBeforeRequest, $user->getLanguage());
    }

    /**
     * @dataProvider provideValidRepositoriesWithLabels
     *
     * @param string $message
     */
    public function testUserCanSubscribeToRepositoryAndLabel(string $message): void
    {
        $this->loadFixtures();
        $userId = '3';
        $user = $this->userRepository->findByTelegramId($userId);
        $subscriptionsBeforeRequest = $user->getSubscriptions()->count();

        $outgoingMessage = $this->sendMessageToBot($message, $userId);
        $this->assertNotEmpty($outgoingMessage);
        $user = $this->userRepository->findByTelegramId($userId);
        $this->assertCount($subscriptionsBeforeRequest + 1, $user->getSubscriptions());
    }

    public function provideValidRepositoriesWithLabels(): array
    {
        return [
            [
                sprintf('/subscribe %s %s', 'https://github.com/symfony/security', 'frameworkbundle'),
            ],
            [
                sprintf('/subscribe %s %s', 'https://github.com/symfony/security', 'easy pick'),
            ],
            [
                sprintf('/subscribe %s %s', 'https://github.com/symfony/security', 'docs'),
            ],
            [
                sprintf('/subscribe %s %s', 'https://github.com/symfony/security', 'easy pick'),
            ],
        ];
    }

    public function testUserCanNotSubscribeForInvalidRepository(): void
    {
        $this->loadFixtures();
        $userId = '3';
        $user = $this->userRepository->findByTelegramId($userId);
        $subscriptionsBeforeRequest = $user->getSubscriptions()->count();

        $outgoingMessage = $this->sendMessageToBot(sprintf('/subscribe %s %s', 'http://invalid.repository/url', 'docs'), $userId);
        $this->assertNotEmpty($outgoingMessage);
        $user = $this->userRepository->findByTelegramId($userId);
        $this->assertCount($subscriptionsBeforeRequest, $user->getSubscriptions());
    }

    public function testUserCanListLabelsForRepository(): void
    {
        $this->loadFixtures();
        $userId = '1';
        $outgoingMessage = $this->sendMessageToBot('https://github.com/symfony/symfony', $userId);
        $this->assertContains('Available labels', $outgoingMessage->getText());
    }

    /**
     * @dataProvider provideInvalidRepositoryUrl
     *
     * @param string $invalidRepositoryUrl
     */
    public function testUserCanNotListLabelsWhenRepositoryUrlIsInvalidOrNotSupported(string $invalidRepositoryUrl): void
    {
        $this->loadFixtures();
        $userId = '1';
        $outgoingMessage = $this->sendMessageToBot($invalidRepositoryUrl, $userId);
        $this->assertContains('unrecognized', $outgoingMessage->getText(), '', $ignoreCase = true);
    }

    public function provideInvalidRepositoryUrl(): array
    {
        return [
            ['https://github.invalid/invalid/invalid'],
            ['https://invalid.com'],
        ];
    }

    public function testUserCanRemoveSubscription(): void
    {
        $this->loadFixtures();
        $userId = '3';
        $user = $this->userRepository->findByTelegramId($userId);
        $subscriptions = $user->getSubscriptions();
        $subscriptionsBeforeRequest = $subscriptions->count();
        /** @var IssueLabeledSubscription $subscriptionToRemove */
        $subscriptionToRemove = $subscriptions->last();

        $message = sprintf(
            '/unsubscribe %s %s',
            $subscriptionToRemove->getRepository()->getUrl(),
            $subscriptionToRemove->getLabel()->getNormalizedName()
        );
        $outgoingMessage = $this->sendMessageToBot($message, $userId);
        $this->assertNotEmpty($outgoingMessage->getText());
        $user = $this->userRepository->findByTelegramId($userId);
        $this->assertCount($subscriptionsBeforeRequest - 1, $user->getSubscriptions());
    }

    public function testUserCanNotRemoveNonexistentSubscription(): void
    {
        $this->loadFixtures();
        $userId = '3';
        $user = $this->userRepository->findByTelegramId($userId);
        $subscriptionsBeforeRequest = $user->getSubscriptions()->count();

        $message = sprintf('/unsubscribe %s %s', 'https://github.com/symfony/security', 'docs');
        $outgoingMessage = $this->sendMessageToBot($message, $userId);
        $this->assertNotEmpty($outgoingMessage);
        $user = $this->userRepository->findByTelegramId($userId);
        $this->assertCount($subscriptionsBeforeRequest, $user->getSubscriptions());
    }

    public function testBotIgnoresTheSameSubscriptions(): void
    {
        $this->loadFixtures();
        $userId = '3';
        $user = $this->userRepository->findByTelegramId($userId);
        $subscriptionsBeforeRequest = $user->getSubscriptions()->count();

        $this->sendMessageToBot(sprintf('/subscribe %s %s', 'https://github.com/symfony/security', 'docs'), $userId);
        $this->sendMessageToBot(sprintf('/subscribe %s %s', 'https://github.com/symfony/security', 'docs'), $userId);
        $this->sendMessageToBot(sprintf('/subscribe %s %s', 'https://github.com/symfony/security', 'docs'), $userId);

        $user = $this->userRepository->findByTelegramId($userId);
        $this->assertCount($subscriptionsBeforeRequest + 1, $user->getSubscriptions());
    }

    public function testUserCanRequestEmailConfirmation(): void
    {
        $this->loadFixtures();
        $userId = '3';
        $outgoingMessage = $this->sendMessageToBot('/email test@test.com', $userId);
        $this->assertContains('Please check your email', $outgoingMessage->getText());
        $this->assertCount(1, $this->messageLogger->getMessages());
    }

    public function testUserCanEnableEmailNotifications(): void
    {
        $this->loadFixtures();
        $userId = '1';
        $user = $this->userRepository->findByTelegramId($userId);
        $this->assertEquals(NotificationTransportEnum::TELEGRAM, $user->getNotificationTransport());

        $this->sendMessageToBot('/notification email', $userId);

        $user = $this->userRepository->findByTelegramId($userId);
        $this->assertEquals(NotificationTransportEnum::EMAIL, $user->getNotificationTransport());
    }

    public function adminReceivesNotificationsAboutNewSubscriptions(): void
    {
        $this->markTestSkipped();
    }

    public function testAdminCanListAvailableCommandForBotFather(): void
    {
        $this->loadFixtures();
        $adminChatId = '1';
        $response = $this->sendMessageToBot('/command-list', $adminChatId);

        $this->assertContains('help', $response->getText());
    }

    public function testCommandListOnlyAvailableForAdmins(): void
    {
        $this->loadFixtures();
        $regularUserId = '2';
        $response = $this->sendMessageToBot('/command-list', $regularUserId);

        $this->assertNotContains('help', $response->getText());
    }

    private function sendMessageToBot(string $text, $userId): ?OutgoingMessage
    {
        $driver = new FakeDriver();
        $driver->messages = [new IncomingMessage($text, $userId, $recipient = null)];
        /** @var BotMan $bot */
        $bot = self::$container->get(BotMan::class);
        $bot->setDriver($driver);

        /** @var WebhookProcessor $webhookProcessor */
        $webhookProcessor = self::$container->get(WebhookProcessor::class);
        $webhookProcessor->handleTelegramRequest($bot);

        $message = $driver->getBotMessages()[0] ?? null;

        $this->entityManager->flush();

        return $message;
    }
}
