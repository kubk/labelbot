<?php

declare(strict_types=1);

namespace App\GitHosting;

use App\Queue\IssueLabeledEvent;
use App\ValueObject\{Label, Repository};
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use phootwork\json\Json;

class GitLab implements GitHostingInterface
{
    private const BASE_URL_FORMAT = 'https://gitlab.com/api/v4/projects/%s%%2F%s/issues';

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
        if (stripos($repository->getUrl(), 'gitlab') === false) {
            return false;
        }

        $url = sprintf(self::BASE_URL_FORMAT, $repository->getOwner(), $repository->getName());

        try {
            $this->guzzle->get($url);
        } catch (RequestException $exception) {
            return false;
        }

        return true;
    }

    /**
     * @param Repository    $repository
     * @param \DateInterval $interval
     *
     * @return IssueLabeledEvent[]
     */
    public function getIssueLabeledEvents(Repository $repository, \DateInterval $interval): array
    {
        $url = sprintf(self::BASE_URL_FORMAT, $repository->getOwner(), $repository->getName());
        $response = $this->guzzle->get($url);
        $json = Json::decode($response->getBody()->getContents());

        $issuesWithinInterval = array_filter($json, function (array $issue) use ($interval) {
            $issueCreatedAt = new \DateTimeImmutable($issue['created_at']);

            return $issueCreatedAt->add($interval) > Carbon::now();
        });

        // Builtin FlatMap could be useful here: https://martinfowler.com/articles/collection-pipeline/flat-map.html
        $issueLabeledEvents = [];
        foreach ($issuesWithinInterval as $issue) {
            foreach ($issue['labels'] as $label) {
                $issueLabeledEvents[] = new IssueLabeledEvent($repository, new Label($label), $issue['web_url']);
            }
        }

        return $issueLabeledEvents;
    }

    /**
     * @param Repository $repository
     * @param Label      $label
     *
     * @return null|string
     */
    public function getLastOpenedIssue(Repository $repository, Label $label): ?string
    {
        $urlFormat = self::BASE_URL_FORMAT . '?' . http_build_query([
            'labels' => $label->getOriginalName(),
            'per_page' => 1,
        ]);

        try {
            $response = $this->guzzle->get(sprintf($urlFormat, $repository->getOwner(), $repository->getName()));
        } catch (RequestException $exception) {
            return null;
        }

        $json = Json::decode($response->getBody()->getContents());

        return $json[0]['web_url'] ?? null;
    }

    /**
     * @param Repository $repository
     *
     * @return Label[]
     */
    public function getAvailableLabels(Repository $repository): array
    {
        return [
            new Label('bug'),
            new Label('api'),
            new Label('docs'),
            new Label('emoji'),
            new Label('enhancement'),
            new Label('backend'),
            new Label('discussion'),
        ];
    }
}
