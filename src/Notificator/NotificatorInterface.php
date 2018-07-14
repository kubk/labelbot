<?php

declare(strict_types=1);

namespace App\Notificator;

use App\Entity\User;
use App\Queue\IssueLabeledEvent;

interface NotificatorInterface
{
    /**
     * @param User $user
     *
     * @return bool
     */
    public function shouldNotify(User $user): bool;

    /**
     * @param User              $user
     * @param IssueLabeledEvent $event
     */
    public function notify(User $user, IssueLabeledEvent $event): void;
}
