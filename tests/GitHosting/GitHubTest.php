<?php

declare(strict_types=1);

namespace App\Tests\GitHosting;

use App\GitHosting\GitHub;
use App\Tests\GuzzleMockFactory;
use App\ValueObject\{Label, Repository};
use Carbon\Carbon;
use Github\Client;
use Github\HttpClient\Builder;
use GuzzleHttp\Psr7\Response;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class GitHubTest extends TestCase
{
    private const TEST_REPO_URL = 'https://github.com/symfony/symfony';

    /**
     * @var GitHub
     */
    private $github;

    public function setUp(): void
    {
        $guzzleMockFactory = new GuzzleMockFactory();
        $httpAdapter = $guzzleMockFactory->createAdapter([
            '/repos/symfony/symfony/labels' => new Response(
                200,
                ['Content-Type' => 'application/json'],
                file_get_contents(__DIR__ . '/../Stub/github_response_symfony_symfony_labels.json')
            ),
            '/repos/symfony/symfony/issues?page=1' => new Response(
                200,
                ['Content-Type' => 'application/json'],
                file_get_contents(__DIR__ . '/../Stub/github_response_symfony_issues.json')
            ),
            '/repos/symfony/symfony/issues/events?page=1' => new Response(
                200,
                ['Content-Type' => 'application/json'],
                file_get_contents(__DIR__ . '/../Stub/github_client_response_symfony_symfony.json')
            ),
            '/repos/spatie/laravel-html/issues/events?page=1' => new Response(
                200,
                ['Content-Type' => 'application/json'],
                file_get_contents(__DIR__ . '/../Stub/github_client_response_spatie_laravel_html.json')
            ),
            '/repos/spatie/laravel-html' => new Response(200, ['Content-Type' => 'application/json'], '{"exists": true}'),
            '/repos/symfony/symfony' => new Response(200, ['Content-Type' => 'application/json'], '{"exists": true}'),
        ]);

        $this->github = new GitHub(new Client(new Builder($httpAdapter)));
    }

    public function testItRecognizesGithubRepositories(): void
    {
        $this->assertTrue($this->github->supports(new Repository(self::TEST_REPO_URL)));
    }

    public function testIdDoesNotRecognizeNonGithubRepositories(): void
    {
        $this->assertFalse($this->github->supports(new Repository('https://gitlab.com/gitlab-org/gitlab-ce')));
    }

    public function testItReturnsAvailableLabelsForRepository(): void
    {
        $labels = $this->github->getAvailableLabels(new Repository(self::TEST_REPO_URL));
        $this->assertCount(30, $labels);
        $this->assertEquals('Feature Freeze', $labels[0]->withoutEmoji());
        $this->assertEquals('Asset', $labels[1]->withoutEmoji());
        $this->assertEquals('FrameworkBundle', current(array_slice($labels, -2))->withoutEmoji());
    }

    /**
     * @dataProvider provideLabelsWithLastIssueUrls
     *
     * @param string $label
     * @param string $lastIssueUrl
     */
    public function testItFindsLastOpenedIssueWithLabel(string $label, string $lastIssueUrl): void
    {
        $issue = $this->github->getLastOpenedIssue(new Repository(self::TEST_REPO_URL), new Label($label));

        $this->assertEquals($lastIssueUrl, $issue);
    }

    public function provideLabelsWithLastIssueUrls(): array
    {
        return [
            [
                'bug',
                'https://github.com/symfony/symfony/issues/27311',
            ],
            [
                'security',
                'https://github.com/symfony/symfony/issues/27311',
            ],
            [
                'feature',
                'https://github.com/symfony/symfony/issues/27307',
            ],
            [
                'httpfoundation',
                'https://github.com/symfony/symfony/issues/27307',
            ],
            [
                'rfc',
                'https://github.com/symfony/symfony/issues/27276',
            ],
        ];
    }

    /**
     * @dataProvider provideRepositoriesWithExpectedLabeledEventsCount
     *
     * @param string             $repositoryUrl
     * @param \DateTimeImmutable $now
     * @param \DateInterval      $interval
     * @param int                $expectedEventsCount
     */
    public function testItReturnsIssueLabeledEventsForRepositoryAndDateInterval(
        string $repositoryUrl,
        \DateTimeImmutable $now,
        \DateInterval $interval,
        int $expectedEventsCount
    ): void {
        Carbon::setTestNow($now);
        $events = $this->github->getIssueLabeledEvents(new Repository($repositoryUrl), $interval);

        $this->assertCount($expectedEventsCount, $events);
    }

    public function provideRepositoriesWithExpectedLabeledEventsCount(): array
    {
        return [
            // GitHub returns time in GMT, Moscow time is GMT+3
            // Issue created at 2018-02-02T11:00:51Z
            [
                'https://github.com/spatie/laravel-html',
                new \DateTimeImmutable('2018-05-05', new \DateTimeZone('Europe/Moscow')),
                \DateInterval::createFromDateString('3 years'),
                2,
            ],
            [
                'https://github.com/spatie/laravel-html',
                new \DateTimeImmutable('2018-02-02 14:05:51', new \DateTimeZone('Europe/Moscow')),
                \DateInterval::createFromDateString('181 minutes'),
                2,
            ],
            [
                'https://github.com/spatie/laravel-html',
                // GMT
                new \DateTimeImmutable('2018-02-02T11:02:51Z'),
                \DateInterval::createFromDateString('5 minutes'),
                2,
            ],
            [
                'https://github.com/spatie/laravel-html',
                new \DateTimeImmutable('2018-02-02 14:02:51', new \DateTimeZone('Europe/Moscow')),
                \DateInterval::createFromDateString('5 minutes'),
                2,
            ],
            [
                'https://github.com/spatie/laravel-html',
                new \DateTimeImmutable('2018-02-02 14:07:51', new \DateTimeZone('Europe/Moscow')),
                \DateInterval::createFromDateString('5 minutes'),
                0,
            ],
            [
                'https://github.com/symfony/symfony',
                new \DateTimeImmutable('2018-06-06 14:07:51', new \DateTimeZone('Europe/Moscow')),
                \DateInterval::createFromDateString('3 years'),
                3,
            ],
            // Issue created at 2018-05-18T18:04:37Z,
            [
                'https://github.com/symfony/symfony',
                new \DateTimeImmutable('2018-05-20 14:07:51', new \DateTimeZone('Europe/Moscow')),
                \DateInterval::createFromDateString('2 days'),
                3,
            ],
            [
                'https://github.com/symfony/symfony',
                new \DateTimeImmutable('2018-05-20 14:07:51Z'),
                \DateInterval::createFromDateString('1 day'),
                0,
            ],
        ];
    }
}
