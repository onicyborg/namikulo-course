@php
    $currentPage = $paginator->currentPage();
    $lastPage = max(1, $paginator->lastPage());
    $firstVisible = max(1, min($currentPage - 2, $lastPage - 4));
    $lastVisible = min($lastPage, $firstVisible + 4);
@endphp
<nav class="nc-data-table-pagination" data-admin-table-pagination aria-label="Halaman tabel">
    <span class="nc-data-table-page-info" data-admin-table-page-info role="status">
        @if($paginator->total())Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} data @else Tidak ada data @endif
    </span>
    <div class="nc-data-table-page-buttons">
        <a href="{{ $paginator->previousPageUrl() ?? '#' }}" data-admin-table-previous aria-label="Halaman sebelumnya" @if($paginator->onFirstPage()) aria-disabled="true" tabindex="-1" @endif>Sebelumnya</a>
        <span class="nc-data-table-page-numbers" data-admin-table-page-numbers>
            @if($firstVisible > 1)
                <a href="{{ $paginator->url(1) }}" data-admin-table-page="0" aria-label="Halaman 1">1</a>
                @if($firstVisible > 2)<span aria-hidden="true">…</span>@endif
            @endif
            @for($page = $firstVisible; $page <= $lastVisible; $page++)
                <a href="{{ $paginator->url($page) }}" data-admin-table-page="{{ $page - 1 }}" aria-label="Halaman {{ $page }}" @if($page === $currentPage) aria-current="page" @endif>{{ $page }}</a>
            @endfor
            @if($lastVisible < $lastPage)
                @if($lastVisible < $lastPage - 1)<span aria-hidden="true">…</span>@endif
                <a href="{{ $paginator->url($lastPage) }}" data-admin-table-page="{{ $lastPage - 1 }}" aria-label="Halaman {{ $lastPage }}">{{ $lastPage }}</a>
            @endif
        </span>
        <a href="{{ $paginator->nextPageUrl() ?? '#' }}" data-admin-table-next aria-label="Halaman berikutnya" @unless($paginator->hasMorePages()) aria-disabled="true" tabindex="-1" @endunless>Berikutnya</a>
    </div>
</nav>
