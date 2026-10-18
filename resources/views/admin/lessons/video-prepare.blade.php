@extends('layouts.admin')

@section('title', 'Siapkan video · '.$lesson->title)
@push('styles')<link rel="stylesheet" href="{{ url('css/video-admin.css') }}?v=2">@endpush
@push('plugin-scripts')<script src="{{ url('vendor/ffmpeg/ffmpeg.js') }}"></script>@endpush

@section('content')
<x-page-header title="Siapkan video" parent="Kurikulum" :parentUrl="route('admin.courses.curriculum', $lesson->course)" />
<div class="section-body video-admin" id="video-preparation"
     data-create-url="{{ route('admin.lessons.video.uploads.store', $lesson) }}"
     data-upload-base="{{ url('admin/video-uploads') }}"
     data-ffmpeg-base="{{ url('vendor/ffmpeg') }}"
     data-source-bytes="{{ $limits['source_bytes'] }}"
     data-duration-seconds="{{ $limits['duration_seconds'] }}"
     data-width="{{ $limits['width'] }}" data-height="{{ $limits['height'] }}"
     data-chunk-bytes="{{ $limits['chunk_bytes'] }}"
     data-upload-bytes="{{ config('video.upload_max_size_mb') * 1024 * 1024 }}"
     data-segment-bytes="{{ config('video.segment_max_size_mb') * 1024 * 1024 }}"
     data-max-segments="{{ config('video.max_segments') }}"
     data-process-timeout-ms="{{ config('video.process_timeout_minutes') * 60 * 1000 }}">
    <div class="video-admin-heading"><div><p class="video-admin-kicker">{{ $lesson->course->title }} / {{ $lesson->section->title }}</p><h2>{{ $lesson->title }}</h2><p>Sumber MP4 tetap di perangkat Anda. Browser menyiapkan HLS terenkripsi, lalu hanya hasilnya yang diunggah.</p></div><a class="btn btn-outline-primary" href="{{ route('admin.courses.curriculum', $lesson->course) }}">Kembali ke kurikulum</a></div>

    <div class="row"><div class="col-12 col-lg-8">
        <div class="card"><div class="card-body video-admin-main">
            <ol class="video-admin-steps" aria-label="Tahap persiapan"><li data-stage="browser">1. Periksa browser</li><li data-stage="processing">2. Proses di perangkat</li><li data-stage="uploading">3. Unggah hasil</li><li data-stage="validating">4. Validasi server</li><li data-stage="ready">5. Siap</li></ol>
            <div class="video-admin-message" role="status" aria-live="polite" data-video-status>Memeriksa kemampuan browser...</div>
            <div class="form-group"><label for="video-file">File video MP4</label><input type="file" id="video-file" class="form-control-file" accept="video/mp4,.mp4" disabled><small class="form-text text-muted">H.264 yuv420p, audio AAC bila ada, maksimal {{ config('video.max_width') }}×{{ config('video.max_height') }} landscape atau {{ config('video.max_height') }}×{{ config('video.max_width') }} portrait, {{ config('video.source_max_duration_seconds') / 60 }} menit, {{ config('video.source_max_size_mb') }} MiB. Hasil HLS maksimal {{ config('video.upload_max_size_mb') }} MiB; keyframe disarankan tiap 6 detik. Video besar memerlukan memori dan ruang disk lebih banyak.</small></div>
            <p class="video-admin-file" data-video-file>Belum ada file dipilih.</p>
            <div class="video-admin-progress" hidden data-video-progress-wrap><div class="progress"><div class="progress-bar" role="progressbar" style="width:0%" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-video-progress></div></div><small data-video-progress-label></small></div>
            <div class="video-admin-actions"><button type="button" class="btn btn-primary" data-video-start disabled>@if($lesson->videoAsset?->status === \App\Enums\VideoStatus::Ready)Siapkan video pengganti @else Siapkan dan unggah @endif</button><button type="button" class="btn btn-outline-danger" data-video-cancel hidden>Batal</button><button type="button" class="btn btn-outline-primary" data-video-retry hidden>Coba lagi</button><div class="video-admin-success-actions" data-video-success-actions hidden><a class="btn btn-primary" href="{{ route('learn.show', $lesson) }}">Pratinjau video</a><button type="button" class="btn btn-outline-primary" data-video-replace>Siapkan video pengganti</button></div></div>
        </div></div>
    </div><div class="col-12 col-lg-4"><div class="card"><div class="card-header"><h3>Status materi</h3></div><div class="card-body"><p><strong data-video-current-status>{{ $lesson->videoAsset?->status?->label() ?? 'Belum ada video siap' }}</strong></p><p class="text-muted" data-video-current-description>@if($lesson->videoAsset?->status === \App\Enums\VideoStatus::Ready)Video aktif tetap tersedia selama pengganti diproses. Penggantian berlaku setelah hasil baru lolos validasi.@else Course membutuhkan sedikitnya satu video siap sebelum diterbitkan.@endif</p><div data-video-ready-actions @if($lesson->videoAsset?->status !== \App\Enums\VideoStatus::Ready) hidden @endif><a class="btn btn-outline-primary btn-sm mr-2" href="{{ route('learn.show', $lesson) }}">Pratinjau</a><button type="button" class="btn btn-outline-danger btn-sm" data-toggle="modal" data-target="#video-delete-modal" data-delete-url="{{ route('admin.lessons.video.destroy', $lesson) }}" data-name="video pada materi {{ $lesson->title }}">Hapus video</button></div></div></div><div class="card"><div class="card-body"><h3>Sebelum mulai</h3><ul class="video-admin-notes"><li>Gunakan browser desktop dengan WebAssembly, Worker, dan Web Crypto.</li><li>Biarkan tab terbuka selama pemrosesan dan unggahan.</li><li>Refresh dapat mengharuskan Anda memilih file dan memproses ulang.</li><li>Batas perangkat dan keberhasilan AES-128 perlu dibuktikan lewat UAT.</li></ul></div></div></div></div>
</div>
<x-confirm-delete-modal id="video-delete-modal" title="Hapus video" />
@endsection

@push('scripts')<script src="{{ url('js/pages/video-prepare.js') }}?v=4"></script>@endpush
