<?php

declare(strict_types=1);

namespace App\GitHosting;

use App\Queue\IssueLabeledEvent;
use App\ValueObject\{Label, Repository};
use Carbon\Carbon;
use Github\Client;
use Github\Exception\RuntimeException;
use GuzzleHttp\Exception\RequestException;

class GitHub implements GitHostingInterface
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function supports(Repository $repository): bool
    {
        if (stripos($repository->getUrl(), 'github') === false) {
            return false;
        }

        try {
            return (bool) $this->client->repo()->show($repository->getOwner(), $repository->getName());
        } catch (RuntimeException $exception) {
            return false;
        }
    }

    /**
     * @param Repository    $repository
     * @param \DateInterval $interval
     *
     * @return IssueLabeledEvent[]
     */
    public function getIssueLabeledEvents(Repository $repository, \DateInterval $interval): array
    {
        $issueEvents = $this->client->issues()->events()->all($repository->getOwner(), $repository->getName());

        $issueLabeledEvents = array_filter($issueEvents, function (array $event) use ($interval) {
            $eventCreatedAt = new \DateTimeImmutable($event['created_at']);
            $isIssueEvent = strpos($event['issue']['html_url'] ?? '', '/issues/') !== false;

            return $isIssueEvent && $event['event'] === 'labeled' && Carbon::now() < $eventCreatedAt->add($interval);
        });

        return array_map(function (array $event) use ($repository) {
            return new IssueLabeledEvent(
                $repository,
                new Label($event['label']['name']),
                $event['issue']['html_url']
            );
        }, $issueLabeledEvents);
    }

    public function getLastOpenedIssue(Repository $repository, Label $label): ?string
    {
        // TODO: https://developer.github.com/v3/issues/
        // labels - A list of comma separated label names. Example: bug, ui, @high
        try {
            $issues = $this->client->issues()->all($repository->getOwner(), $repository->getName());
        } catch (RequestException $exception) {
            return null;
        }

        foreach ($issues as $issue) {
            foreach ($issue['labels'] as $labelFromIssue) {
                if ($label->equals(new Label($labelFromIssue['name']))) {
                    return $issue['html_url'];
                }
            }
        }

        return null;
    }

    /**
     * @param Repository $repository
     *
     * @return Label[]
     */
    public function getAvailableLabels(Repository $repository): array
    {
        $labels = $this->client->repo()->labels()->all($repository->getOwner(), $repository->getName());

        return array_map(function (array $label) {
            return new Label($label['name']);
        }, $labels);
    }
}
