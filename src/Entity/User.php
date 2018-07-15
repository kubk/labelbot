<?php

declare(strict_types=1);

namespace App\Entity;

use App\Event\{EmailConfirmationRequestedEvent, NewSubscriptionEvent};
use App\ValueObject\{Label, NotificationTransport, Repository};
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\ORM\Mapping as ORM;
use Knp\Rad\DomainEvent\{Provider, ProviderTrait};
use Ramsey\Uuid\{Uuid, UuidInterface};

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User implements Provider
{
    use ProviderTrait;

    /**
     * @var UuidInterface
     *
     * @ORM\Id
     * @ORM\Column(type="uuid_binary")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $telegramId;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $language;

    /**
     * @var null|string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $email;

    /**
     * @var null|string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $unconfirmedEmail;

    /**
     * @var null|string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $confirmationToken;

    /**
     * @var NotificationTransport
     *
     * @ORM\Embedded(class="App\ValueObject\NotificationTransport")
     */
    private $notificationTransport;

    /**
     * @var IssueLabeledSubscription[]|Collection
     *
     * @ORM\OneToMany(targetEntity="IssueLabeledSubscription", mappedBy="user", cascade={"all"}, orphanRemoval=true)
     */
    private $subscriptions;

    public function __construct(string $telegramId)
    {
        $this->id = Uuid::uuid4();
        $this->telegramId = $telegramId;
        $this->subscriptions = new ArrayCollection();
        $this->notificationTransport = NotificationTransport::telegram();
        $this->language = 'en';
    }

    public function requestEmailConfirmation(string $email): void
    {
        $confirmationToken = bin2hex(random_bytes(16));
        $this->confirmationToken = $confirmationToken;
        $this->unconfirmedEmail = $email;

        $this->events[] = new EmailConfirmationRequestedEvent($this->unconfirmedEmail, $confirmationToken, $this->language);
    }

    public function confirmEmail(): void
    {
        if (!$this->unconfirmedEmail) {
            throw new \LogicException('User must request email before confirmation');
        }

        $this->email = $this->unconfirmedEmail;
        $this->confirmationToken = null;
        $this->unconfirmedEmail = null;
    }

    public function subscribeForLabel(Repository $repository, Label $label): void
    {
        $this->subscriptions[] = new IssueLabeledSubscription($this, $repository, $label);

        $this->events[] = new NewSubscriptionEvent($this->telegramId, $repository, $label);
    }

    public function removeSubscription(IssueLabeledSubscription $subscription): void
    {
        $this->subscriptions->removeElement($subscription);
        $subscription->removeUser();
    }

    public function switchLanguage(string $language): void
    {
        $this->language = $language;
    }

    public function enableTransport(NotificationTransport $notificationTransport): void
    {
        $this->notificationTransport = $notificationTransport;
    }

    public function isTransportEnabled(NotificationTransport $notificationTransport): bool
    {
        return $this->notificationTransport->equals($notificationTransport);
    }

    public function isEmailConfirmed(): bool
    {
        return $this->email !== null;
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getTelegramId(): string
    {
        return $this->telegramId;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }
}
