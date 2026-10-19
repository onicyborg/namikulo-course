<?php

namespace Database\Seeders;

use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class DemoCourseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('DemoCourseSeeder hanya boleh dijalankan pada APP_ENV=local.');
        }

        $catalog = [
            ['Basis Data', 'dasar-postgresql-skema-tabel-dan-query', 'Dasar PostgreSQL: Skema, Tabel, dan Query', 'Menyusun tabel, relasi, dan query SQL untuk kebutuhan aplikasi sehari-hari.', 149000],
            ['Basis Data', 'optimasi-query-postgresql-explain', 'Optimasi Query PostgreSQL dengan EXPLAIN', 'Membaca execution plan dan memilih indeks berdasarkan pola akses data.', 249000],
            ['Backend', 'merancang-rest-api-dengan-laravel', 'Merancang REST API dengan Laravel', 'Membangun endpoint, validasi, dan respons API yang konsisten.', 299000],
            ['Backend', 'autentikasi-dan-otorisasi-laravel', 'Autentikasi dan Otorisasi Laravel', 'Mengatur sesi, peran, policy, dan batas akses pada aplikasi Laravel.', 229000],
            ['Backend', 'pengujian-aplikasi-laravel', 'Pengujian Aplikasi Laravel', 'Menulis pengujian fitur untuk alur penting dan kegagalan yang perlu ditangani.', 219000],
            ['Frontend', 'typescript-untuk-aplikasi-web', 'TypeScript untuk Aplikasi Web', 'Memakai tipe data untuk menjaga kontrak komponen dan integrasi API.', 199000],
            ['Frontend', 'javascript-asinkron-dan-integrasi-api', 'JavaScript Asinkron dan Integrasi API', 'Mengelola permintaan jaringan, state pemuatan, dan penanganan kesalahan.', 179000],
            ['Frontend', 'html-semantik-dan-aksesibilitas', 'HTML Semantik dan Aksesibilitas', 'Menyusun halaman yang mudah dinavigasi dengan keyboard dan pembaca layar.', 149000],
            ['Frontend', 'css-responsif-untuk-dashboard', 'CSS Responsif untuk Dashboard', 'Membuat tabel, formulir, dan navigasi yang tetap nyaman di layar kecil.', 179000],
            ['Operasi', 'dasar-docker-untuk-pengembangan', 'Dasar Docker untuk Pengembangan', 'Menyiapkan lingkungan aplikasi dan database yang dapat diulang oleh tim.', 229000],
            ['Operasi', 'deployment-laravel-di-cpanel', 'Deployment Laravel di cPanel', 'Menyiapkan rilis, konfigurasi lingkungan, dan penyimpanan pada shared hosting.', 249000],
            ['Operasi', 'git-untuk-kolaborasi-tim', 'Git untuk Kolaborasi Tim', 'Mengelola branch, review perubahan, dan penyelesaian konflik secara tertib.', 129000],
            ['Backend', 'keamanan-form-dan-upload-web', 'Keamanan Form dan Upload Web', 'Menerapkan validasi, CSRF, dan pembatasan file pada alur input pengguna.', 199000],
            ['Basis Data', 'pemodelan-data-relasional', 'Pemodelan Data Relasional', 'Merancang kunci, relasi, dan batasan data sebelum menulis migration.', 189000],
            ['Operasi', 'monitoring-aplikasi-dan-analisis-log', 'Monitoring Aplikasi dan Analisis Log', 'Membaca log, mengenali kegagalan, dan menyusun tindak lanjut operasional.', 189000],
        ];

        $created = DB::transaction(function () use ($catalog): int {
            $total = Course::query()->count();
            $created = 0;

            foreach ($catalog as [$categoryName, $topicSlug, $title, $summary, $price]) {
                if ($total >= 15) {
                    break;
                }

                $slug = 'demo-'.$topicSlug;
                $existing = Course::withTrashed()->where('slug', $slug)->first();
                if ($existing && ! $existing->trashed()) {
                    continue;
                }

                if ($existing) {
                    $suffix = 2;
                    do {
                        $slug = 'demo-'.$topicSlug.'-'.$suffix++;
                    } while (Course::withTrashed()->where('slug', $slug)->exists());
                }

                $category = Category::query()->firstOrCreate(
                    ['slug' => Str::slug($categoryName)],
                    ['name' => $categoryName, 'sort_order' => 0],
                );

                Course::query()->create([
                    'category_id' => $category->id,
                    'title' => '[Demo] '.$title,
                    'slug' => $slug,
                    'summary' => $summary,
                    'description' => '<p>'.$summary.'</p>',
                    'price' => $price,
                    'access_months' => config('course.access_months'),
                    'status' => CourseStatus::Draft,
                    'is_featured' => false,
                ]);

                $created++;
                $total++;
            }

            return $created;
        });

        $this->command?->info($created.' course demo ditambahkan. Total course aktif: '.Course::query()->count().'.');
    }
}
