<?php

declare(strict_types=1);

namespace App\Bot\Middleware;

use App\Entity\User;
use App\Repository\UserRepository;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Interfaces\Middleware\Received;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

class UserMiddleware implements Received
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function received(IncomingMessage $message, $next, BotMan $bot)
    {
        $user = $this->userRepository->findByTelegramId($message->getSender());

        if (!$user && $message->getText() === '/start') {
            $user = new User($message->getSender());
            $this->userRepository->add($user);
        }

        $message->addExtras('user', $user);

        return $next($message);
    }
}
