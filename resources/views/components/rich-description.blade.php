@props(['html' => null])
@php($safeHtml = app(\App\Services\DescriptionSanitizer::class)->clean($html))
@once
    @push('styles')<link rel="stylesheet" href="{{ url('css/rich-description.css') }}?v=2">@endpush
@endonce
@if($safeHtml)
<div {{ $attributes->class(['nc-rich-description', 'nc-authored-colors' => (bool) preg_match('/style="[^"]*(?:background-)?color:/i', $safeHtml)]) }}>{!! $safeHtml !!}</div>
@endif
