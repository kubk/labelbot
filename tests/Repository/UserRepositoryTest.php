<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\{Label, Repository};
use App\Repository\SubscriptionRepository;
use App\Repository\UserRepository;
use App\Tests\AbstractTestCase;

class UserRepositoryTest extends AbstractTestCase
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

        $this->userRepository = self::$container->get(UserRepository::class);
        $this->subscriptionRepository = self::$container->get(SubscriptionRepository::class);
    }

    /**
     * @dataProvider provideRepositoriesAndLabelsWithExpectedSubscribersCount
     *
     * @param Repository $repository
     * @param Label      $label
     * @param int        $expectedSubscribersCount
     */
    public function testGetAllSubscribedTo(Repository $repository, Label $label, int $expectedSubscribersCount): void
    {
        $this->loadFixtures();

        $subscribers = $this->userRepository->getAllSubscribedTo($repository, $label);

        $this->assertCount($expectedSubscribersCount, $subscribers);
    }

    public function provideRepositoriesAndLabelsWithExpectedSubscribersCount(): array
    {
        return [
            [
                new Repository('https://github.com/symfony/symfony'),
                new Label('easy pick'),
                3,
            ],
            [
                new Repository('https://github.com/kubk/wave-algo'),
                new Label('easy pick'),
                2,
            ],
            [
                new Repository('https://github.com/kubk/image-pixel-manipulation'),
                new Label('docs'),
                1,
            ],
        ];
    }

    public function testGetAllRepositories(): void
    {
        $this->loadFixtures();

        $this->assertCount(3, $this->subscriptionRepository->findAllRepositories());
    }
}
