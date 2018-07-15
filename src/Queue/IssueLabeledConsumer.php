<?php

declare(strict_types=1);

namespace App\Queue;

use App\Notificator\NotificatorInterface;
use App\Repository\UserRepository;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\{PsrContext, PsrMessage, PsrProcessor};
use Psr\Log\LoggerInterface;

class IssueLabeledConsumer implements PsrProcessor, TopicSubscriberInterface
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var NotificatorInterface[]|iterable
     */
    private $notificators;

    public function __construct(UserRepository $userRepository, LoggerInterface $logger, iterable $notificators)
    {
        $this->userRepository = $userRepository;
        $this->logger = $logger;
        $this->notificators = $notificators;
    }

    public function process(PsrMessage $message, PsrContext $context): string
    {
        $event = IssueLabeledEvent::createFromJson($message->getBody());
        $subscribedUsers = $this->userRepository->getAllSubscribedTo($event->getRepository(), $event->getLabel());

        foreach($subscribedUsers as $user) {
            foreach ($this->notificators as $notificator) {
                if (!$notificator->shouldNotify($user)) {
                    continue;
                }

                $notificator->notify($user, $event);

                $this->logger->info(sprintf(
                    'Notification "%s" sent to user "%s", event "%s"',
                    gettype($notificator),
                    $user->getId(),
                    $event->__toString()
                ));
            }
        }

        return self::ACK;
    }

    public static function getSubscribedTopics(): array
    {
        return [IssueLabeledEvent::TOPIC_NAME];
    }
}
