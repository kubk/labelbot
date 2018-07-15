<?php

declare(strict_types=1);

namespace App\Event;

use App\ValueObject\{Label, Repository};
use Knp\Rad\DomainEvent\Event;

class NewSubscriptionEvent extends Event
{
    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var Label
     */
    private $label;

    /**
     * @var string
     */
    private $telegramId;

    public function __construct(string $telegramId, Repository $repository, Label $label)
    {
        parent::__construct(self::class);
        $this->repository = $repository;
        $this->label = $label;
        $this->telegramId = $telegramId;
    }

    public function getRepository(): Repository
    {
        return $this->repository;
    }

    public function getLabel(): Label
    {
        return $this->label;
    }

    public function getTelegramId(): string
    {
        return $this->telegramId;
    }
}
