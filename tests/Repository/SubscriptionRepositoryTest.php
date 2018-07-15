<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\IssueLabeledSubscription;
use App\Repository\SubscriptionRepository;
use App\Repository\UserRepository;
use App\Tests\AbstractTestCase;
use App\ValueObject\{Label, Repository};

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
        $this->subscriptionRepository = $this->container->get(SubscriptionRepository::class);
        $this->userRepository = $this->container->get(UserRepository::class);
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
