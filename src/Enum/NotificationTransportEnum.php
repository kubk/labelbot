<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

class NotificationTransportEnum extends Enum
{
    public const TELEGRAM = 'telegram';
    public const EMAIL = 'email';
}
