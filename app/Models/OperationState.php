<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OperationState extends Model
{
    use HasUuids;

    protected $fillable = ['task', 'cursor', 'last_started_at', 'last_succeeded_at', 'next_attempt_at', 'last_error'];

    protected function casts(): array
    {
        return ['cursor' => 'array', 'last_started_at' => 'datetime', 'last_succeeded_at' => 'datetime', 'next_attempt_at' => 'datetime'];
    }
}
