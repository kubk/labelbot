<?php

declare(strict_types=1);

namespace App\Queue;

use App\ValueObject\{Label, Repository};
use phootwork\json\Json;

class IssueLabeledEvent implements \JsonSerializable
{
    public const TOPIC_NAME = 'issue_labeled';

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
    private $issueUrl;

    public function __construct(Repository $repository, Label $label, string $issueUrl)
    {
        $this->repository = $repository;
        $this->issueUrl = $issueUrl;
        $this->label = $label;
    }

    public function getRepository(): Repository
    {
        return $this->repository;
    }

    public function getLabel(): Label
    {
        return $this->label;
    }

    public function getIssueUrl(): string
    {
        return $this->issueUrl;
    }

    public static function createFromJson(string $json): self
    {
        [$repositoryUrl, $issueUrl, $label] = Json::decode($json);

        return new self(new Repository($repositoryUrl), new Label($label), $issueUrl);
    }

    public function jsonSerialize(): array
    {
        return [$this->repository->getUrl(), $this->issueUrl, $this->label->getOriginalName()];
    }

    public function __toString(): string
    {
        return sprintf('%s|%s|%s', $this->repository->getUrl(), $this->issueUrl, $this->label->getOriginalName());
    }
}
