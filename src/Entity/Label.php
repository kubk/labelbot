<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class Label
{
    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $originalName;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $normalizedName;

    public function __construct(string $name)
    {
        $this->originalName = $name;
        $this->normalizedName = $this->normalize($name);
    }

    private function normalize(string $label): string
    {
        if (!$label) {
            throw new \InvalidArgumentException('Label must not be empty');
        }

        return mb_strtolower(trim($label));
    }

    public function withoutEmoji(): string
    {
        // Not all of the GitHub emoji's are properly rendered by Telegram
        return trim(preg_replace('/:\w+:/', '', $this->originalName));
    }

    public function equals(self $label): bool
    {
        return $this->normalizedName === $label->normalizedName;
    }

    public function getNormalizedName(): string
    {
        return $this->normalizedName;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }
}
