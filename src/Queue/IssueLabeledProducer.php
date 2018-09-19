<?php

declare(strict_types=1);

namespace App\Queue;

use App\Entity\Repository;
use App\GitHosting\GitHostingInterface;
use App\Repository\SubscriptionRepository;
use Enqueue\Client\ProducerInterface;
use phootwork\json\Json;

class IssueLabeledProducer
{
    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * @var GitHostingInterface[]|iterable
     */
    private $gitHostings;

    public function __construct(
        ProducerInterface $producer,
        SubscriptionRepository $subscriptionRepository,
        iterable $gitHostings
    ) {
        $this->producer = $producer;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->gitHostings = $gitHostings;
    }

    public function dispatchIssueLabeledEvents(\DateInterval $pollingInterval): void
    {
        $repositories = $this->subscriptionRepository->findAllRepositories();

        foreach ($repositories as $repository) {
            $gitHosting = $this->getGitHosting($repository);
            $issueLabeledEvents = $gitHosting->getIssueLabeledEvents($repository, $pollingInterval);
            foreach ($issueLabeledEvents as $event) {
                $this->producer->sendEvent(IssueLabeledEvent::TOPIC_NAME, Json::encode($event));
            }
        }
    }

    private function getGitHosting(Repository $repository): GitHostingInterface
    {
        foreach ($this->gitHostings as $gitHosting) {
            if ($gitHosting->supports($repository)) {
                return $gitHosting;
            }
        }

        throw new \InvalidArgumentException(sprintf('Repository "%s" is not supported', $repository->getUrl()));
    }
}
