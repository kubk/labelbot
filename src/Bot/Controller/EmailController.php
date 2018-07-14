<?php

declare(strict_types=1);

namespace App\Bot\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use BotMan\BotMan\BotMan;
use Symfony\Component\Translation\TranslatorInterface;

class EmailController implements HasSuggestionInterface
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(UserRepository $userRepository, TranslatorInterface $translator)
    {
        $this->userRepository = $userRepository;
        $this->translator = $translator;
    }

    public function requestEmailConfirmation(BotMan $bot, string $email): void
    {
        if ($this->userRepository->findOneBy(['email' => $email])) {
            $bot->reply($this->translator->trans('email.already_used'));
            return;
        }

        /** @var User $user */
        $user = $bot->getMessage()->getExtras('user');
        $user->requestEmailConfirmation($email);

        $bot->reply($this->translator->trans('email.sent', ['%email%' => $email]));
    }

    public function confirmEmail(BotMan $bot, string $confirmationToken): void
    {
        $user = $this->userRepository->findOneBy(['confirmationToken' => $confirmationToken]);

        if ($user === null) {
            return;
        }

        $user->confirmEmail();

        $bot->reply($this->translator->trans('email.confirmed', ['%email%' => $user->getEmail()]));
    }

    public function getSuggestion(): string
    {
        return '/email';
    }
}
