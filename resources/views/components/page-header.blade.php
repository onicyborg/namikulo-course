@props(['title', 'parent' => null, 'parentUrl' => null])

<div class="section-header">
    <h1>{{ $title }}</h1>
    @if ($parent && $parentUrl)
        <div class="section-header-breadcrumb" aria-label="Breadcrumb">
            <div class="breadcrumb-item"><a href="{{ $parentUrl }}">{{ $parent }}</a></div>
            <div class="breadcrumb-item active" aria-current="page">{{ $title }}</div>
        </div>
    @endif
</div>
