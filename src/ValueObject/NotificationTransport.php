<?php

declare(strict_types=1);

namespace App\ValueObject;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class NotificationTransport
{
    private const TYPE_TELEGRAM = 'telegram';
    private const TYPE_EMAIL = 'email';

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $type;

    public function __construct(string $type)
    {
        if (!in_array($type, self::getTransports(), true)) {
            throw new \InvalidArgumentException('Invalid notification type');
        }

        $this->type = $type;
    }

    public static function telegram(): self
    {
        return new self(self::TYPE_TELEGRAM);
    }

    public static function email(): self
    {
        return new self(self::TYPE_EMAIL);
    }

    public static function getTransports(): array
    {
        return [self::TYPE_TELEGRAM, self::TYPE_EMAIL];
    }

    public function equals(self $transport): bool
    {
        return $this->type === $transport->type;
    }
}
