<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class UniqueSlug
{
    public function fromTitle(string $title, string $modelClass): string
    {
        $base = \Illuminate\Support\Str::slug($title);
        if ($base === '') {
            throw \Illuminate\Validation\ValidationException::withMessages(['slug' => 'Judul harus menghasilkan slug yang valid. Isi slug secara manual.']);
        }
        $base = mb_substr($base, 0, 240);
        $slug = $base;
        $number = 2;
        while (DB::table((new $modelClass)->getTable())->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$number++;
        }
        return $slug;
    }
}
