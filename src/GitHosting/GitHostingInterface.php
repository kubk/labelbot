<?php

declare(strict_types=1);

namespace App\GitHosting;

use App\Queue\IssueLabeledEvent;
use App\ValueObject\{Label, Repository};

/**
 * Interface for different Git hostings such as GitHub, BitBucket or GitLab.
 */
interface GitHostingInterface
{
    /**
     * @param Repository $repository
     *
     * @return bool
     */
    public function supports(Repository $repository): bool;

    /**
     * @param Repository    $repository
     * @param \DateInterval $interval
     *
     * @return IssueLabeledEvent[]
     */
    public function getIssueLabeledEvents(Repository $repository, \DateInterval $interval): array;

    /**
     * @param Repository $repository
     * @param Label      $label
     *
     * @return null|string
     */
    public function getLastOpenedIssue(Repository $repository, Label $label): ?string;

    /**
     * @param Repository $repository
     *
     * @return Label[]
     */
    public function getAvailableLabels(Repository $repository): array;
}
