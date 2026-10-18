@props(['title', 'parent' => null, 'parentUrl' => null])

@if(request()->routeIs('admin.*', 'member.*'))
    <header class="nd-page-header nd-page-header--legacy">
        <div>
            <nav class="nd-breadcrumb" aria-label="Breadcrumb">
                <span>Namikulo</span><x-dashboard.icon name="chevron-right" /><span>{{ request()->routeIs('admin.*') ? 'Area Admin' : 'Area Member' }}</span>
                @if($parent && $parentUrl && $parent !== 'Dashboard')<x-dashboard.icon name="chevron-right" /><a href="{{ $parentUrl }}">{{ $parent }}</a>@endif
                <x-dashboard.icon name="chevron-right" /><span aria-current="page">{{ $title }}</span>
            </nav>
            <h1>{{ $title }}</h1>
        </div>
    </header>
@else
    <div class="section-header">
        <h1>{{ $title }}</h1>
        @if ($parent && $parentUrl)
            <div class="section-header-breadcrumb" aria-label="Breadcrumb">
                <div class="breadcrumb-item"><a href="{{ $parentUrl }}">{{ $parent }}</a></div>
                <div class="breadcrumb-item active" aria-current="page">{{ $title }}</div>
            </div>
        @endif
    </div>
@endif
