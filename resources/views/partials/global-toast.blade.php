<div class="nc-toast-host" data-toast-host></div>
<div class="nc-toast-live" data-toast-polite role="status" aria-live="polite" aria-atomic="true"></div>
<div class="nc-toast-live" data-toast-assertive role="alert" aria-live="assertive" aria-atomic="true"></div>
@foreach(['success', 'error', 'warning', 'info', 'status'] as $toastType)
    @if(is_string(session($toastType)) && session($toastType) !== '')
        <span hidden data-toast-flash="{{ $toastType === 'status' ? 'info' : $toastType }}">{{ session($toastType) }}</span>
        <noscript><p role="status">{{ session($toastType) }}</p></noscript>
    @endif
@endforeach
