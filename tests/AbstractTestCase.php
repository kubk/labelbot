<?php

declare(strict_types=1);

namespace App\Tests;

use App\DataFixtures\AppFixture;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Container;

abstract class AbstractTestCase extends KernelTestCase
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var Container
     */
    protected $container;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->container = $kernel->getContainer();
        $this->entityManager = $this->container->get('doctrine')->getManager();
        $this->entityManager->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->entityManager->rollback();
    }

    protected function loadFixtures(): void
    {
        $appFixture = new AppFixture();
        $appFixture->load($this->entityManager);
    }
}
