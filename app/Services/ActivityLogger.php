<?php

namespace App\Services;

use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActivityLogger
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'access_token',
        'refresh_token',
        'authorization',
        'server_key',
        'client_key',
        'signature_key',
        'encryption_key',
        'secret',
    ];

    public function log(
        string $action,
        ?User $user = null,
        ?string $tableName = null,
        ?string $recordId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        ?Request $request = null,
    ): SystemLog {
        $request ??= request();

        return SystemLog::query()->create([
            'user_id' => $user?->getKey(),
            'action' => Str::limit($action, 40, ''),
            'table_name' => $tableName ? Str::limit($tableName, 80, '') : null,
            'record_id' => $recordId ? Str::limit($recordId, 36, '') : null,
            'method' => $request?->method(),
            'url' => $request ? Str::limit($request->url(), 500, '') : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? Str::limit((string) $request->userAgent(), 255, '') : null,
            'old_values' => $this->sanitize($oldValues),
            'new_values' => $this->sanitize($newValues),
            'description' => $description ? Str::limit($description, 255, '') : null,
        ]);
    }

    private function sanitize(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $clean = [];

        foreach ($values as $key => $value) {
            $normalizedKey = Str::lower((string) $key);

            if ($this->isSensitive($normalizedKey)) {
                $clean[$key] = '[DIHAPUS]';
                continue;
            }

            $clean[$key] = is_array($value) ? $this->sanitize($value) : $value;
        }

        return $clean;
    }

    private function isSensitive(string $key): bool
    {
        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if ($key === $sensitiveKey || Str::endsWith($key, '_'.$sensitiveKey)) {
                return true;
            }
        }

        return false;
    }
}
