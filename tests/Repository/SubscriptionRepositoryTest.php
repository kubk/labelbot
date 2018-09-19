<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\IssueLabeledSubscription;
use App\Entity\{Label, Repository};
use App\Repository\SubscriptionRepository;
use App\Repository\UserRepository;
use App\Tests\AbstractTestCase;

class SubscriptionRepositoryTest extends AbstractTestCase
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subscriptionRepository = self::$container->get(SubscriptionRepository::class);
        $this->userRepository = self::$container->get(UserRepository::class);
    }

    public function testGetSubscription(): void
    {
        $this->loadFixtures();

        $user = $this->userRepository->findByTelegramId('1');

        $this->assertInstanceOf(IssueLabeledSubscription::class, $this->subscriptionRepository->findSubscription(
            $user,
            new Repository('https://github.com/symfony/symfony'),
            new Label('docs')
        ));

        $this->assertNull($this->subscriptionRepository->findSubscription(
            $user,
            new Repository('https://github.com/symfony/symfony'),
            new Label('nonexistent label')
        ));
    }
}
