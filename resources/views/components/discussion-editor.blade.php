@props(['id', 'value' => '', 'invalid' => false, 'help' => null])
@once
    @push('player-scripts')<script src="{{ url('vendor/lexical/namikulo-editor.js') }}?v=1" defer></script>@endpush
@endonce
@php($format = old('body_format', 'plain') === 'html' ? 'html' : 'plain')
<div class="nc-discussion-editor {{ $invalid ? 'has-error' : '' }}">
    <input type="hidden" name="body_format" value="{{ $format }}">
    <textarea id="{{ $id }}" name="body" rows="4" required maxlength="20000" data-discussion-editor class="form-control {{ $invalid ? 'is-invalid' : '' }}" @if($help) aria-describedby="{{ $help }}" @endif @if($invalid) aria-invalid="true" @endif>{{ $format === 'html' ? app(\App\Services\DescriptionSanitizer::class)->clean($value) : $value }}</textarea>
    <small class="nc-editor-count" data-editor-count>10–2.000 karakter teks</small>
    <p class="nc-editor-error" data-editor-error role="alert" hidden></p>
</div>
