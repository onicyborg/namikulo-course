<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\PlaybackSession;
use App\Models\User;

interface VideoDelivery
{
    public function startSession(User $user, Lesson $lesson): array;
    public function assertSession(User $user, string $token): PlaybackSession;
    public function heartbeat(User $user, string $token): array;
    public function master(string $token): string;
    public function playlist(PlaybackSession $session, string $token): string;
    public function key(PlaybackSession $session): string;
    public function segmentPath(PlaybackSession $session, string $name): string;
}
