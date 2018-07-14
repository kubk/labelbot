<?php

declare(strict_types=1);

namespace App\GitHosting;

use App\Entity\Label;
use App\Entity\Repository;
use App\Queue\IssueLabeledEvent;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use phootwork\json\Json;

class BitBucket implements GitHostingInterface
{
    /**
     * @var Client
     */
    private $guzzle;

    public function __construct(Client $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    /**
     * @param Repository $repository
     *
     * @return bool
     */
    public function supports(Repository $repository): bool
    {
        if (stripos($repository->getUrl(), 'bitbucket') === false) {
            return false;
        }

        try {
            $this->getIssues($repository);
        } catch (RequestException $exception) {
            // The repository url is invalid or private or has no issue tracker
            return false;
        }

        return true;
    }

    /**
     * @param Repository $repository
     *
     * @return array
     */
    private function getIssues(Repository $repository): array
    {
        $url = sprintf(
            'https://api.bitbucket.org/2.0/repositories/%s/%s/issues',
            $repository->getOwner(),
            $repository->getName()
        );

        $response = $this->guzzle->get($url);

        return Json::decode($response->getBody()->getContents());
    }

    /**
     * @param Repository    $repository
     * @param \DateInterval $interval
     *
     * @return IssueLabeledEvent[]
     */
    public function getIssueLabeledEvents(Repository $repository, \DateInterval $interval): array
    {
        $issues = $this->getIssues($repository);

        $issuesCreatedWithinInterval = array_filter($issues['values'], function (array $issue) use ($interval) {
            $issueCreatedAt = new \DateTimeImmutable($issue['created_on']);

            return $issueCreatedAt->add($interval) > Carbon::now();
        });

        return array_map(function (array $issue) use ($repository) {
            return new IssueLabeledEvent(
                $repository,
                new Label($issue['kind'] ?? $issue['priority']),
                $issue['links']['html']['href']
            );
        }, $issuesCreatedWithinInterval);
    }

    /**
     * @param Repository $repository
     * @param Label      $label
     *
     * @return null|string
     */
    public function getLastOpenedIssue(Repository $repository, Label $label): ?string
    {
        try {
            $issues = $this->getIssues($repository);
        } catch (RequestException $exception) {
            return null;
        }

        foreach ($issues['values'] as $issue) {
            if ($label->equals(new Label($issue['kind'])) || $label->equals(new Label($issue['priority']))) {
                return $issue['links']['html']['href'];
            }
        }

        return null;
    }

    /**
     * AFAIK BitBucket doesn't support custom labels for issues: https://bitbucket.org/site/master/issues/5356/labels-for-issues-bb-6550.
     *
     * @param Repository $repository
     *
     * @return Label[]
     */
    public function getAvailableLabels(Repository $repository): array
    {
        return [
            // priority
            new Label('trivial'),
            new Label('minor'),
            new Label('major'),
            new Label('critical'),
            new Label('blocker'),
            // kind
            new Label('bug'),
            new Label('enhancement'),
            new Label('proposal'),
            new Label('task'),
        ];
    }
}
