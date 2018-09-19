<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\{Uuid, UuidInterface};

/**
 * @ORM\Entity(repositoryClass="App\Repository\SubscriptionRepository")
 */
class IssueLabeledSubscription
{
    /**
     * @var UuidInterface
     *
     * @ORM\Id
     * @ORM\Column(type="uuid_binary")
     */
    private $id;

    /**
     * @var Repository
     *
     * @ORM\Embedded(class="Repository")
     */
    private $repository;

    /**
     * @var Label
     *
     * @ORM\Embedded(class="Label")
     */
    private $label;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="subscriptions", cascade="persist")
     */
    private $user;

    public function __construct(User $user, Repository $repository, Label $label)
    {
        $this->id = Uuid::uuid4();
        $this->user = $user;
        $this->label = $label;
        $this->repository = $repository;
    }

    public function getLabel(): Label
    {
        return $this->label;
    }

    public function getRepository(): Repository
    {
        return $this->repository;
    }

    public function removeUser(): void
    {
        $this->user = null;
    }
}
