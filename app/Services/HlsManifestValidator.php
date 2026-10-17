<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class HlsManifestValidator
{
    public function validate(string $manifest, string $ivHex): array
    {
        if (strlen($manifest) > config('video.manifest_max_bytes') || str_contains($manifest, "\0")) {
            $this->reject();
        }

        $lines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $manifest)), fn ($line) => $line !== ''));
        if (($lines[0] ?? null) !== '#EXTM3U' || end($lines) !== '#EXT-X-ENDLIST') $this->reject();

        $allowedTags = ['#EXTM3U', '#EXT-X-ENDLIST', '#EXT-X-PLAYLIST-TYPE:VOD', '#EXT-X-MEDIA-SEQUENCE:0'];
        $hasKey = false;
        $hasVod = false;
        $hasSequence = false;
        $hasTarget = false;
        $endCount = 0;
        $durations = [];
        $names = [];
        $awaitingSegment = false;

        foreach ($lines as $line) {
            if ($awaitingSegment && str_starts_with($line, '#')) $this->reject();
            if ($line === '#EXT-X-PLAYLIST-TYPE:VOD') $hasVod = true;
            if ($line === '#EXT-X-MEDIA-SEQUENCE:0') $hasSequence = true;
            if ($line === '#EXT-X-ENDLIST') $endCount++;
            if (in_array($line, $allowedTags, true)) continue;
            if (preg_match('/^#EXT-X-VERSION:[3-9]$/', $line)) continue;
            if (preg_match('/^#EXT-X-TARGETDURATION:([1-9]\d*)$/', $line)) {
                $hasTarget = true;
                continue;
            }
            if (preg_match('/^#EXT-X-KEY:METHOD=AES-128,URI="key\.bin",IV=0x([0-9a-fA-F]{32})$/', $line, $match)) {
                if ($hasKey || ! hash_equals(strtolower($ivHex), strtolower($match[1]))) $this->reject();
                $hasKey = true;
                continue;
            }
            if (preg_match('/^#EXTINF:(\d+(?:\.\d{1,6})?),?$/', $line, $match)) {
                if ($awaitingSegment || ! $hasKey || (float) $match[1] <= 0) $this->reject();
                $durations[] = (float) $match[1];
                $awaitingSegment = true;
                continue;
            }
            if (preg_match('/^seg_(\d{5})\.ts$/', $line, $match)) {
                if (! $awaitingSegment || (int) $match[1] !== count($names)) $this->reject();
                $names[] = $line;
                $awaitingSegment = false;
                continue;
            }
            $this->reject();
        }

        if (! $hasKey || ! $hasVod || ! $hasSequence || ! $hasTarget || $endCount !== 1 || $awaitingSegment || $names === []
            || count($names) > config('video.max_segments')
            || array_sum($durations) > config('video.source_max_duration_seconds') + 10) $this->reject();

        return ['names' => $names, 'duration' => array_sum($durations)];
    }

    private function reject(): never
    {
        throw ValidationException::withMessages(['manifest' => 'Manifest HLS tidak sesuai format VOD AES-128 yang diizinkan.']);
    }
}
