<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContentOrder
{
    public function save(Builder $query, array $ids, string $table, ?\App\Models\User $user): void
    {
        DB::transaction(function () use ($query, $ids, $table, $user): void {
            $current = $query->lockForUpdate()->pluck('id')->all();
            if (count($ids) !== count($current) || count(array_unique($ids)) !== count($ids) || array_diff($ids, $current) || array_diff($current, $ids)) {
                throw ValidationException::withMessages(['order' => 'Daftar urutan tidak sesuai dengan data pada halaman. Muat ulang halaman dan coba lagi.']);
            }
            foreach ($ids as $position => $id) {
                (clone $query)->whereKey($id)->update(['sort_order' => $position + 1]);
            }
            app(ActivityLogger::class)->log('reordered', $user, $table, null, ['ids' => $current], ['ids' => $ids]);
        });
    }
}
