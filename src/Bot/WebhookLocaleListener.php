<?php

declare(strict_types=1);

namespace App\Bot;

use App\Repository\UserRepository;
use phootwork\json\Json;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class WebhookLocaleListener implements EventSubscriberInterface
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        $webhook = Json::decode((string) $request->getContent());

        if (!isset($webhook['message']['chat']['id'])) {
            return;
        }

        $user = $this->userRepository->findByTelegramId($webhook['message']['chat']['id']);

        if ($user !== null) {
            $request->attributes->set('_locale', $user->getLanguage());
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                // Call before Symfony\Component\HttpKernel\EventListener\LocaleListener
                ['onKernelRequest', 20],
            ],
        ];
    }
}
