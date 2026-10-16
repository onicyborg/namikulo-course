@props(['title', 'description', 'icon' => 'inbox', 'actionUrl' => null, 'actionLabel' => null])

<div class="empty-state app-empty-state" role="status">
    <div class="empty-state-icon" aria-hidden="true"><i data-feather="{{ $icon }}"></i></div>
    <h2>{{ $title }}</h2>
    <p class="lead">{{ $description }}</p>
    @if ($actionUrl && $actionLabel)
        <a href="{{ $actionUrl }}" class="btn btn-primary">{{ $actionLabel }}</a>
    @endif
</div>
