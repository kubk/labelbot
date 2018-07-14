<?php

declare(strict_types=1);

namespace App\Tests\Queue;

use App\Entity\Repository;
use App\Queue\IssueLabeledProducer;
use App\Repository\SubscriptionRepository;
use App\Tests\AbstractTestCase;
use App\Tests\Integration\GitHubMock;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TraceableProducer;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class IssueLabeledProducerTest extends AbstractTestCase
{
    /**
     * @var TraceableProducer
     */
    private $traceableProducer;

    public function setUp(): void
    {
        parent::setUp();
        $this->traceableProducer = new TraceableProducer($this->container->get(ProducerInterface::class));
    }

    public function testHandlingGithubLabeledEvents(): void
    {
        /** @var SubscriptionRepository|MockObject $repositoryRepository */
        $repositoryRepository = $this->createMock(SubscriptionRepository::class);
        $repositoryRepository
            ->method('findAllRepositories')
            ->willReturn(array_map(function (string $repositoryUrl) {
                return new Repository($repositoryUrl);
            }, GitHubMock::VALID_REPOS));

        $issueLabeledProducer = new IssueLabeledProducer($this->traceableProducer, $repositoryRepository, [new GitHubMock()]);
        $issueLabeledProducer->dispatchIssueLabeledEvents(\DateInterval::createFromDateString('3 years'));
        $this->assertCount(2, $this->traceableProducer->getTopicTraces('issue_labeled'));
    }
}
