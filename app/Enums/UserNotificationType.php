<?php

namespace App\Enums;

enum UserNotificationType: string
{
    case PaymentPending = 'payment_pending';
    case PaymentPaid = 'payment_paid';
    case PaymentExpired = 'payment_expired';
    case AccessGranted = 'access_granted';
    case AccessRevoked = 'access_revoked';
    case AccessExpiring = 'access_expiring';
    case DiscussionQuestion = 'discussion_question';
    case DiscussionReply = 'discussion_reply';
}
