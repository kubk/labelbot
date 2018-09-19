<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class Repository
{
    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $url;

    public function __construct(string $repositoryUrl)
    {
        $this->url = $repositoryUrl;
    }

    public function getOwner(): string
    {
        $path = parse_url($this->url)['path'];

        return explode('/', $path)[1];
    }

    public function getName(): string
    {
        $path = parse_url($this->url)['path'];

        return explode('/', $path)[2];
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
