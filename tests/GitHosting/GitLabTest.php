<?php

declare(strict_types=1);

namespace App\Tests\GitHosting;

use App\Entity\Label;
use App\Entity\Repository;
use App\GitHosting\GitLab;
use App\Tests\GuzzleMockFactory;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class GitLabTest extends TestCase
{
    private const TEST_REPO_URL = 'https://gitlab.com/gitlab-org/gitlab-ce';

    /**
     * @var GitLab
     */
    private $gitlab;

    public function setUp(): void
    {
        $guzzleMockFactory = new GuzzleMockFactory();
        $client = $guzzleMockFactory->createClient([
            '/api/v4/projects/gitlab-org%2Fgitlab-ce/issues' => new Response(
                200,
                ['Content-Type' => 'application/json'],
                file_get_contents(__DIR__ . '/../Stub/gitlab_response_gitlab-ce_issues.json')
            ),
            '/api/v4/projects/gitlab-org%2Fgitlab-ce/issues?labels=bug&per_page=1' => new Response(
                200,
                ['Content-Type' => 'application/json'],
                file_get_contents(__DIR__ . '/../Stub/gitlab_response_gitlab-ce_issues_with_label_bug.json')
            ),
        ]);

        $this->gitlab = new GitLab($client);
    }

    public function testItRecognizesGitlabRepositories(): void
    {
        $this->assertTrue($this->gitlab->supports(new Repository(self::TEST_REPO_URL)));
    }

    public function testIdDoesNotRecognizeNonGitlabRepositories(): void
    {
        $this->assertFalse($this->gitlab->supports(new Repository('https://github.com/symfony/symfony')));
    }

    public function testItReturnsAvailableLabelsForRepository(): void
    {
        $this->assertNotEmpty($this->gitlab->getAvailableLabels(new Repository(self::TEST_REPO_URL)));
    }

    public function testItFindsLastOpenedIssueWithLabel(): void
    {
        $issueUrl = $this->gitlab->getLastOpenedIssue(new Repository(self::TEST_REPO_URL), new Label('bug'));

        $this->assertEquals('https://gitlab.com/gitlab-org/gitlab-ce/issues/48003', $issueUrl);
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
        $events = $this->gitlab->getIssueLabeledEvents(new Repository($repositoryUrl), $interval);

        $this->assertCount($expectedEventsCount, $events);
    }

    public function provideRepositoriesWithExpectedLabeledEventsCount(): array
    {
        return [
            // GitLab returns time in GMT, Moscow time is GMT+3
            [
                self::TEST_REPO_URL,
                new \DateTimeImmutable('2018-06-16T07:27:08', new \DateTimeZone('Europe/Moscow')),
                \DateInterval::createFromDateString('2 hours'),
                2,
            ],
            [
                self::TEST_REPO_URL,
                new \DateTimeImmutable('2018-06-16T07:27:15', new \DateTimeZone('Europe/Moscow')),
                \DateInterval::createFromDateString('147 minutes'),
                7,
            ],
        ];
    }
}
