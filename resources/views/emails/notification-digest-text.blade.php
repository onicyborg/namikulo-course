Halo, {{ $recipient->name }}.

Ada {{ $total }} notifikasi yang belum Anda baca hingga pukul 07.00 WIB, {{ $date }}.

@foreach($items as $item)
{{ $item->title }}
{{ $item->summary }}

@endforeach
@if($total > $items->count())Dan {{ $total - $items->count() }} notifikasi lainnya.

@endif
Lihat semua: {{ $recipient->isAdmin() ? route('admin.notifications.index') : route('member.notifications.index') }}
