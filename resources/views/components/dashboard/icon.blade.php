@props(['name'])
<svg {{ $attributes->class(['nd-icon']) }} width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    <use href="{{ url('icons/dashboard-lucide.svg') }}?v=3#{{ $name }}"></use>
</svg>
