<?php

namespace App\Enums;

enum NotificationDigestStatus: string
{
    case Attempted = 'attempted';
    case Sent = 'sent';
    case Failed = 'failed';
    case Uncertain = 'uncertain';
    case Skipped = 'skipped';
}
