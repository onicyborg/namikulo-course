@extends('layouts.admin')

@section('title', 'Siapkan video · '.$lesson->title)
@push('styles')<link rel="stylesheet" href="{{ url('css/video-admin.css') }}?v=2">@endpush
@push('plugin-scripts')<script src="{{ url('vendor/ffmpeg/ffmpeg.js') }}"></script>@endpush

@section('content')
<x-page-header title="Siapkan video" parent="Kurikulum" :parentUrl="route('admin.courses.curriculum', $lesson->course)" />
<div class="section-body video-admin" id="video-preparation" data-workspace-state="browser"
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

    <div class="vp-layout"><div class="vp-primary">
        <div class="card"><div class="card-body video-admin-main">
            <div class="vp-workspace-title"><span class="vp-title-icon"><x-dashboard.icon name="file-video" /></span><div><h3>Persiapan video</h3><p>Pilih sumber, proses di perangkat, lalu unggah hasilnya.</p></div></div>
            <ol class="video-admin-steps" aria-label="Tahap persiapan">
                @foreach(['browser' => 'Periksa browser', 'processing' => 'Proses di perangkat', 'uploading' => 'Unggah hasil', 'validating' => 'Validasi server', 'ready' => 'Siap'] as $step => $label)
                    <li data-stage="{{ $step }}"><span class="vp-step-number" aria-hidden="true">{{ $loop->iteration }}</span><span>{{ $label }}</span><small data-step-state>Menunggu</small></li>
                @endforeach
            </ol>
            <div class="video-admin-message" role="status" aria-live="polite" data-video-status>Memeriksa kemampuan browser...</div>
            <div class="vp-upload" data-video-dropzone aria-disabled="true">
                <div data-video-empty>
                    <span class="vp-upload-icon"><x-dashboard.icon name="arrow-up" /></span>
                    <h3>Unggah video pembelajaran</h3>
                    <p>Tarik satu file MP4 ke sini, atau pilih dari perangkat Anda.</p>
                    <label class="vp-file-picker" for="video-file"><span>Pilih file</span><input type="file" id="video-file" name="video_source" accept="video/mp4,.mp4" aria-describedby="video-source-help" disabled></label>
                    <small>MP4 · Maksimal {{ config('video.source_max_size_mb') }} MiB · {{ config('video.source_max_duration_seconds') / 60 }} menit</small>
                </div>
                <div class="vp-selected" data-video-selected hidden>
                    <span class="vp-file-icon"><x-dashboard.icon name="file-video" /></span>
                    <div class="vp-file-copy"><h4 data-video-file-name></h4><p data-video-file-meta></p><span class="vp-file-check" data-video-file-check>Belum diperiksa</span></div>
                    <div class="vp-file-actions"><button type="button" class="btn btn-outline-primary" data-video-change>Ganti file</button><button type="button" class="vp-remove" data-video-clear aria-label="Hapus pilihan file"><x-dashboard.icon name="x" /></button></div>
                </div>
            </div>
            <p id="video-source-help" class="vp-source-help">File sumber tetap di perangkat. Pemeriksaan durasi, resolusi, dan codec dilakukan saat persiapan dimulai.</p>
            <p class="video-admin-file sr-only" data-video-file>Belum ada file dipilih.</p>
            <div class="video-admin-progress" hidden data-video-progress-wrap><div class="progress"><div class="progress-bar" role="progressbar" style="width:0%" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-video-progress></div></div><small data-video-progress-label></small></div>
            <div class="video-admin-actions"><button type="button" class="btn btn-primary" data-video-start disabled>@if($lesson->videoAsset?->status === \App\Enums\VideoStatus::Ready)Siapkan video pengganti @else Siapkan dan unggah @endif</button><button type="button" class="btn btn-outline-danger" data-video-cancel hidden>Batal</button><button type="button" class="btn btn-outline-primary" data-video-retry hidden>Coba lagi</button><div class="video-admin-success-actions" data-video-success-actions hidden><a class="btn btn-primary" href="{{ route('learn.show', $lesson) }}" target="_blank" rel="noopener noreferrer">Pratinjau video</a><button type="button" class="btn btn-outline-primary" data-video-replace>Siapkan video pengganti</button></div></div>
        </div></div>
    </div><aside class="vp-sidebar">
        <section class="card vp-live-status" aria-labelledby="vp-status-title"><div class="card-body"><span class="vp-status-icon"><x-dashboard.icon name="activity" /></span><h3 id="vp-status-title">Status persiapan</h3><strong data-video-live-status role="status">Memeriksa browser</strong><p data-video-live-hint>Pilih video setelah pemeriksaan browser selesai.</p></div></section>
        <div class="card"><div class="card-header"><h3>Video aktif</h3></div><div class="card-body"><p><strong data-video-current-status>{{ $lesson->videoAsset?->status?->label() ?? 'Belum ada video siap' }}</strong></p><p class="text-muted" data-video-current-description>@if($lesson->videoAsset?->status === \App\Enums\VideoStatus::Ready)Video aktif tetap tersedia selama pengganti diproses. Penggantian berlaku setelah hasil baru lolos validasi.@else Course membutuhkan sedikitnya satu video siap sebelum diterbitkan.@endif</p><div data-video-ready-actions @if($lesson->videoAsset?->status !== \App\Enums\VideoStatus::Ready) hidden @endif><a class="btn btn-outline-primary btn-sm mr-2" href="{{ route('learn.show', $lesson) }}" target="_blank" rel="noopener noreferrer">Pratinjau</a><button type="button" class="btn btn-outline-danger btn-sm" data-toggle="modal" data-target="#video-delete-modal" data-delete-url="{{ route('admin.lessons.video.destroy', $lesson) }}" data-name="video pada materi {{ $lesson->title }}">Hapus video</button></div></div></div><div class="card"><div class="card-body"><h3>Persyaratan video</h3><dl class="vp-requirements">
            <div><dt>Format</dt><dd>MP4 · H.264 yuv420p</dd></div>
            <div><dt>Audio</dt><dd>AAC atau tanpa audio</dd></div>
            <div><dt>Resolusi maks.</dt><dd>{{ $limits['width'] }} × {{ $limits['height'] }} landscape<br>{{ $limits['height'] }} × {{ $limits['width'] }} portrait</dd></div>
            <div><dt>Durasi maks.</dt><dd>{{ $limits['duration_seconds'] / 60 }} menit</dd></div>
            <div><dt>Sumber maks.</dt><dd>{{ $limits['source_bytes'] / 1024 / 1024 }} MiB</dd></div>
            <div><dt>Hasil HLS maks.</dt><dd>{{ config('video.upload_max_size_mb') }} MiB</dd></div>
        </dl><p class="vp-guidance-note">Keyframe disarankan tiap 6 detik. Video besar membutuhkan lebih banyak memori dan ruang disk perangkat.</p><h3>Sebelum mulai</h3><ul class="video-admin-notes"><li>Gunakan browser desktop dengan WebAssembly, Worker, dan Web Crypto.</li><li>Biarkan tab terbuka selama pemrosesan dan unggahan.</li><li>Refresh dapat mengharuskan Anda memilih file dan memproses ulang.</li></ul></div></div></aside></div>
</div>
<x-confirm-delete-modal id="video-delete-modal" title="Hapus video" />
@endsection

@push('scripts')<script src="{{ url('js/pages/video-prepare.js') }}?v=20"></script>@endpush
