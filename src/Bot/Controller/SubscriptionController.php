<?php

declare(strict_types=1);

namespace App\Bot\Controller;

use App\Entity\{IssueLabeledSubscription, User};
use App\Entity\{Label, Repository};
use App\GitHosting\GitHostingInterface;
use App\Repository\SubscriptionRepository;
use BotMan\BotMan\BotMan;
use BotMan\Drivers\Telegram\Extensions\{Keyboard, KeyboardButton};
use Symfony\Component\Translation\TranslatorInterface;

class SubscriptionController implements HasSuggestionInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * @var GitHostingInterface[]|iterable
     */
    private $gitHostings;

    public function __construct(
        TranslatorInterface $translator,
        SubscriptionRepository $subscriptionRepository,
        iterable $gitHostings
    ) {
        $this->translator = $translator;
        $this->gitHostings = $gitHostings;
        $this->subscriptionRepository = $subscriptionRepository;
    }

    public function showAvailableLabels(BotMan $bot, string $repositoryUrl): void
    {
        if (!$this->isValidRepositoryUrl($repositoryUrl)) {
            $bot->reply($this->translator->trans('repository.invalid'));
            return;
        }

        $repository = new Repository($repositoryUrl);
        $gitHosting = $this->getGitHosting($repository);
        if (!$gitHosting) {
            $bot->reply($this->translator->trans('repository.unrecognized'));
            return;
        }

        $labels = $gitHosting->getAvailableLabels($repository);
        if (count($labels) === 0) {
            $bot->reply($this->translator->trans('repository.no_labels'));
        }

        $labelsWithoutEmoji = array_map(function (Label $label) {
            return $label->withoutEmoji();
        }, $labels);

        $bot->reply($this->translator->trans('repository.available_labels'), $this->createKeyboard($repository, $labelsWithoutEmoji));
    }

    private function getGitHosting(Repository $repository): ?GitHostingInterface
    {
        foreach ($this->gitHostings as $gitHosting) {
            if ($gitHosting->supports($repository)) {
                return $gitHosting;
            }
        }

        return null;
    }

    private function createKeyboard(Repository $repository, array $availableLabels): array
    {
        $keyboard = Keyboard::create()
            ->resizeKeyboard();

        $buttons = array_map(function (string $label) use ($repository): KeyboardButton {
            return KeyboardButton::create($label)
                ->callbackData(sprintf('/subscribe %s %s', $repository->getUrl(), $label));
        }, $availableLabels);

        foreach (array_chunk($buttons, 3) as $row) {
            $keyboard->addRow(...$row);
        }

        return $keyboard->toArray();
    }

    private function isValidRepositoryUrl(string $repositoryUrl): bool
    {
        return filter_var($repositoryUrl, FILTER_VALIDATE_URL) && strpos($repositoryUrl, '/') !== false;
    }

    public function subscribe(BotMan $bot, string $repositoryUrl, string $labelName): void
    {
        /** @var User $user */
        $user = $bot->getMessage()->getExtras('user');
        if (!$this->isValidRepositoryUrl($repositoryUrl)) {
            $bot->reply($this->translator->trans('repository.invalid'));
            return;
        }

        $repository = new Repository($repositoryUrl);
        $gitHosting = $this->getGitHosting($repository);
        if (!$gitHosting) {
            $bot->reply($this->translator->trans('repository.unrecognized'));
            return;
        }

        $label = new Label($labelName);
        if ($this->subscriptionRepository->findSubscription($user, $repository, $label)) {
            $bot->reply($this->translator->trans('subscription.already_exists'));
            return;
        }

        $user->subscribeForLabel($repository, $label);

        $message = $this->translator->trans('subscription.added', [
            '%repositoryUrl%' => $repository->getUrl(),
            '%label%' => $label->withoutEmoji(),
        ]);
        $bot->reply($message, ['disable_web_page_preview' => true, 'parse_mode' => 'HTML']);

        $issue = $gitHosting->getLastOpenedIssue($repository, $label);
        if ($issue) {
            $bot->reply($this->translator->trans('repository.last_opened_issue', [
                '%issueUrl%' => $issue,
                '%label%' => $label->withoutEmoji(),
            ]), ['parse_mode' => 'HTML']);
        }
    }

    public function unsubscribe(BotMan $bot, string $repositoryUrl, string $labelName): void
    {
        /** @var User $user */
        $user = $bot->getMessage()->getExtras('user');

        if (!$this->isValidRepositoryUrl($repositoryUrl)) {
            $bot->reply($this->translator->trans('repository.invalid'));
            return;
        }

        $repository = new Repository($repositoryUrl);
        $label = new Label($labelName);
        $subscription = $this->subscriptionRepository->findSubscription($user, $repository, $label);
        if (!$subscription) {
            $bot->reply($this->translator->trans('subscription.not_found'));
            return;
        }

        $user->removeSubscription($subscription);

        $bot->reply($this->translator->trans('subscription.removed'));
    }

    public function showSubscriptions(BotMan $bot): void
    {
        /** @var User $user */
        $user = $bot->getMessage()->getExtras('user');
        $subscriptions = $user->getSubscriptions();

        if (!$subscriptions->count()) {
            $bot->reply($this->translator->trans('subscription.list_empty'), ['disable_web_page_preview' => true]);
            return;
        }

        $subscriptionsString = array_reduce($subscriptions->toArray(), function (string $carry, IssueLabeledSubscription $current) {
            return sprintf("%s\n- %s | %s", $carry, $current->getRepository()->getUrl(), $current->getLabel()->withoutEmoji());
        }, $this->translator->trans('subscription.my'));

        $bot->reply($subscriptionsString, ['disable_web_page_preview' => true]);
    }

    public function getSuggestion(): string
    {
        return '/subscriptions';
    }
}
