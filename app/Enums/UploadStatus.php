<?php

namespace App\Enums;

enum UploadStatus: string
{
    case Preparing = 'preparing';
    case Uploading = 'uploading';
    case Validating = 'validating';
    case Ready = 'ready';
    case Replaced = 'replaced';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
}
