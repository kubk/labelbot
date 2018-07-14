<?php

declare(strict_types=1);

namespace App\Console;

use App\Queue\IssueLabeledProducer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DispatchIssueEventsCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'app:dispatch-issue-events';

    /**
     * @var IssueLabeledProducer
     */
    private $issueLabeledProducer;

    /**
     * @var \DateInterval
     */
    private $pollingInterval;

    public function __construct(IssueLabeledProducer $issueLabeledProducer, string $pollingInterval)
    {
        $this->issueLabeledProducer = $issueLabeledProducer;
        $this->pollingInterval = new \DateInterval($pollingInterval);
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Sends last GitHub / BitBucket / GitLab issue events to queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->issueLabeledProducer->dispatchIssueLabeledEvents($this->pollingInterval);
    }
}
