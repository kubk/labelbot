<?php

declare(strict_types=1);

namespace App\Console;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TelegramWebhookCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'app:webhook';

    /**
     * @var Client
     */
    private $guzzle;

    /**
     * @var string
     */
    private $telegramBotApiToken;

    public function __construct(Client $client, string $telegramBotApiToken)
    {
        $this->guzzle = $client;
        $this->telegramBotApiToken = $telegramBotApiToken;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Sets and removes Telegram Webhook');

        $this->addOption(
            'url',
            'u',
            InputOption::VALUE_REQUIRED,
            'The URL for webhook'
        );

        $this->addOption(
            'delete',
            'd',
            InputOption::VALUE_NONE,
            'Delete webhook'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        if ($input->getOption('delete')) {
            $url = "https://api.telegram.org/bot{$this->telegramBotApiToken}/setWebhook";
        } elseif ($input->getOption('url')) {
            $url = "https://api.telegram.org/bot{$this->telegramBotApiToken}/setWebhook?url={$input->getOption('url')}";
        } else {
            $help = new HelpCommand();
            $help->setCommand($this);
            $help->run($input, $output);
            return;
        }

        $io = new SymfonyStyle($input, $output);

        try {
            $response = $this->guzzle->get($url);
            $io->success((string) $response->getBody()->getContents());
        } catch (ServerException $e) {
            $io->error((string) $e->getResponse()->getBody());
        }
    }
}
