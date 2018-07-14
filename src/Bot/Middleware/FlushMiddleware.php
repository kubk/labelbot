<?php

declare(strict_types=1);

namespace App\Bot\Middleware;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\Interfaces\Middleware\Sending;
use Doctrine\ORM\EntityManagerInterface;

class FlushMiddleware implements Sending
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function sending($payload, $next, BotMan $bot)
    {
        $this->entityManager->flush();

        return $next($payload);
    }
}
