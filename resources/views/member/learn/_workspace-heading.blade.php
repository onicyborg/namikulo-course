<header class="nl-workspace-heading">
    <div class="nl-lesson-meta">
        <span>{{ $lesson->section->title }}</span>
        @if($lessonNumber)<span>Materi {{ $lessonNumber }} / {{ $readyCount }}</span>@endif
    </div>
    <h1 id="lesson-title">{{ $lesson->title }}</h1>
    <a class="nl-jump-curriculum" href="#learn-curriculum"><x-dashboard.icon name="layers" />Lihat isi course<x-dashboard.icon name="arrow-right" /></a>
</header>
