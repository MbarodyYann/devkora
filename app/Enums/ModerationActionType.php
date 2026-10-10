<?php

namespace App\Enums;

/**
 * Actions de modération. La phrase du journal est construite par App\Support\Moderation::sentence().
 */
enum ModerationActionType: string
{
    case Hide = 'hide';
    case Restore = 'restore';
    case Dismiss = 'dismiss';
    case Retag = 'retag';

}
