<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\{Label, Repository};
use App\GitHosting\GitHostingInterface;
use App\Queue\IssueLabeledEvent;

class GitHubMock implements GitHostingInterface
{
    public const VALID_REPOS = [
        'https://github.com/symfony/symfony',
        'https://github.com/kubk/wave-algo',
        'https://github.com/kubk/image-pixel-manipulation',
        'https://github.com/symfony/security',
    ];

    public function supports(Repository $repository): bool
    {
        return in_array($repository->getUrl(), self::VALID_REPOS, true);
    }

    public function getIssueLabeledEvents(Repository $repository, \DateInterval $interval): array
    {
        $issueLabeledEvents = [
            'https://github.com/symfony/symfony' => [
                new IssueLabeledEvent(
                    new Repository('https://github.com/symfony/symfony'),
                    new Label('docs'),
                    'https://github.com/symfony/symfony/issues/1'
                ),
                new IssueLabeledEvent(
                    new Repository('https://github.com/symfony/symfony'),
                    new Label('easy pick'),
                    'https://github.com/symfony/symfony/issues/2'
                ),
            ],
        ];

        return $issueLabeledEvents[$repository->getUrl()] ?? [];
    }

    public function getLastOpenedIssue(Repository $repository, Label $label): ?string
    {
        return null;
    }

    public function getAvailableLabels(Repository $repository): array
    {
        if (!in_array($repository->getUrl(), self::VALID_REPOS, true)) {
            return [];
        }

        return [
            new Label('docs'),
            new Label('bug'),
            new Label('good first issue'),
        ];
    }
}
