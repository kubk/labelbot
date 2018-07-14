<?php

declare(strict_types=1);

namespace App\Bot;

use App\Bot\Controller\AdminController;
use App\Bot\Controller\EmailController;
use App\Bot\Controller\HelpController;
use App\Bot\Controller\LanguageController;
use App\Bot\Controller\NotificationController;
use App\Bot\Controller\SubscriptionController;
use App\Bot\Middleware\{AdminMiddleware, FlushMiddleware, TypingMiddleware, UserMiddleware};
use BotMan\BotMan\BotMan;
use Symfony\Component\HttpFoundation\Response;

class WebhookProcessor
{
    /**
     * @var AdminMiddleware
     */
    private $adminMiddleware;

    /**
     * @var TypingMiddleware
     */
    private $typingMiddleware;

    /**
     * @var UserMiddleware
     */
    private $userMiddleware;

    /**
     * @var FlushMiddleware
     */
    private $flushMiddleware;

    public function __construct(
        AdminMiddleware $adminMiddleware,
        TypingMiddleware $typingMiddleware,
        UserMiddleware $userMiddleware,
        FlushMiddleware $flushMiddleware
    ) {
        $this->adminMiddleware = $adminMiddleware;
        $this->typingMiddleware = $typingMiddleware;
        $this->flushMiddleware = $flushMiddleware;
        $this->userMiddleware = $userMiddleware;
    }

    public function handleTelegramRequest(BotMan $bot): Response
    {
        $bot->hears('/start', HelpController::class . '@showStartKeyboard');
        $bot->hears('(http[^\s]+)', SubscriptionController::class . '@showAvailableLabels');
        $bot->hears('/subscribe (http[^\s]+) ([\w\s]+)', SubscriptionController::class . '@subscribe');
        $bot->hears('/unsubscribe (http[^\s]+) ([\w\s]+)', SubscriptionController::class . '@unsubscribe');
        $bot->hears('/subscriptions', SubscriptionController::class . '@showSubscriptions');
        $bot->hears('/languages', LanguageController::class . '@showLanguageKeyboard');
        $bot->hears('/language {language}', LanguageController::class . '@switchLanguage');
        $bot->hears('/notifications', NotificationController::class . '@showNotifications');
        $bot->hears('/notification (telegram|email)', NotificationController::class . '@enable');
        $bot->hears('/email {email}', EmailController::class . '@requestEmailConfirmation');

        $bot->hears('/command-list', AdminController::class . '@generateCommandList')
            ->middleware($this->adminMiddleware);

        $bot->fallback(HelpController::class . '@showHelp');

        $bot->middleware->received($this->userMiddleware);
        $bot->middleware->heard($this->typingMiddleware);
        $bot->middleware->sending($this->flushMiddleware);

        $bot->listen();

        // Symfony controller must return a response
        return new Response();
    }
}
