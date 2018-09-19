<?php

declare(strict_types=1);

namespace App\Tests\GitHosting;

use App\Entity\{Label, Repository};
use App\GitHosting\BitBucket;
use App\Tests\GuzzleMockFactory;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class BitBucketTest extends TestCase
{
    private const TEST_REPO_URL = 'https://bitbucket.org/tutorials/tutorials.bitbucket.org';

    /**
     * @var BitBucket
     */
    private $bitbucket;

    public function setUp(): void
    {
        $guzzleMockFactory = new GuzzleMockFactory();
        $guzzle = $guzzleMockFactory->createClient([
            '/2.0/repositories/tutorials/tutorials.bitbucket.org/issues' => new Response(
                200,
                ['Content-Type' => 'application/json'],
                file_get_contents(__DIR__ . '/../Stub/bitbucket_response_tutorials_issues.json')
            ),
        ]);

        $this->bitbucket = new BitBucket($guzzle);
    }

    public function testItRecognizesBitBucketRepositories(): void
    {
        $this->assertTrue($this->bitbucket->supports(new Repository(self::TEST_REPO_URL)));
    }

    public function testItDoesNotRecognizeNonBitBucketRepositories(): void
    {
        $this->assertFalse($this->bitbucket->supports(new Repository('https://gitlab.com/gitlab-org/gitlab-ce')));
    }

    public function testItReturnsAvailableLabelsForRepository(): void
    {
        $labels = $this->bitbucket->getAvailableLabels(new Repository(self::TEST_REPO_URL));
        $this->assertNotEmpty($labels);
        $labelsNormalized = array_map(function (Label $label) {
            return $label->getNormalizedName();
        }, $labels);

        $bitbucketLabels = ['bug', 'minor', 'major'];

        // TODO: use assertArraySubset after upgrade to PHPUnit 7.3: https://github.com/sebastianbergmann/phpunit/pull/3161
        $this->assertEmpty(array_diff($bitbucketLabels, $labelsNormalized));
    }

    /**
     * @dataProvider provideLabelsWithLastIssueUrls
     *
     * @param string $label
     * @param string $lastIssueUrl
     */
    public function testItReturnsLastOpenedIssueWithLabel(string $label, string $lastIssueUrl): void
    {
        $issue = $this->bitbucket->getLastOpenedIssue(new Repository(self::TEST_REPO_URL), new Label($label));

        $this->assertNotNull($issue);
        $this->assertEquals($lastIssueUrl, $issue);
    }

    public function provideLabelsWithLastIssueUrls(): array
    {
        return [
            [
                'bug',
                'https://bitbucket.org/tutorials/tutorials.bitbucket.org/issues/28/fix-error',
            ],
            [
                'enhancement',
                'https://bitbucket.org/tutorials/tutorials.bitbucket.org/issues/29/test-milestone',
            ],
            [
                'trivial',
                'https://bitbucket.org/tutorials/tutorials.bitbucket.org/issues/22/testing-issue-api',
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
        $events = $this->bitbucket->getIssueLabeledEvents(new Repository($repositoryUrl), $interval);

        $this->assertCount($expectedEventsCount, $events);
    }

    public function provideRepositoriesWithExpectedLabeledEventsCount(): array
    {
        return [
            // BitBucket returns time in GMT, Moscow time is GMT+3
            [
                self::TEST_REPO_URL,
                new \DateTimeImmutable('2017-11-20T14:16:36', new \DateTimeZone('Europe/Moscow')),
                \DateInterval::createFromDateString('2 hours'),
                1,
            ],
            [
                self::TEST_REPO_URL,
                new \DateTimeImmutable('2017-11-20T14:16:36', new \DateTimeZone('Europe/Moscow')),
                \DateInterval::createFromDateString('8 months'),
                3,
            ],
        ];
    }
}
