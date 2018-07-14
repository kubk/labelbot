<?php

declare(strict_types=1);

namespace App\Event;

use Knp\Rad\DomainEvent\Event;

class EmailConfirmationRequestedEvent extends Event
{
    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $confirmationToken;

    /**
     * @var string
     */
    private $language;

    public function __construct(string $email, string $confirmationToken, string $language)
    {
        parent::__construct(self::class);
        $this->email = $email;
        $this->confirmationToken = $confirmationToken;
        $this->language = $language;
    }

    public function getConfirmationToken(): string
    {
        return $this->confirmationToken;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }
}
