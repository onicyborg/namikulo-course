@php
    $currentPage = $paginator->currentPage();
    $lastPage = max(1, $paginator->lastPage());
    $firstVisible = max(1, min($currentPage - 1, $lastPage - 2));
    $lastVisible = min($lastPage, $firstVisible + 2);
@endphp
<nav class="nc-data-table-pagination" data-admin-table-pagination aria-label="Halaman tabel">
    <span class="nc-data-table-page-info" data-admin-table-page-info role="status">
        @if($paginator->total())Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} data @else Tidak ada data @endif
    </span>
    <div class="nc-data-table-page-buttons">
        <button type="button" data-admin-table-previous aria-label="Halaman sebelumnya" @disabled($paginator->onFirstPage())>Sebelumnya</button>
        <span class="nc-data-table-page-numbers" data-admin-table-page-numbers>
            @if($firstVisible > 1)
                <button type="button" data-admin-table-page="0" aria-label="Halaman 1">1</button>
                @if($firstVisible > 2)<span aria-hidden="true">…</span>@endif
            @endif
            @for($page = $firstVisible; $page <= $lastVisible; $page++)
                <button type="button" data-admin-table-page="{{ $page - 1 }}" aria-label="Halaman {{ $page }}" @if($page === $currentPage) aria-current="page" @endif>{{ $page }}</button>
            @endfor
            @if($lastVisible < $lastPage)
                @if($lastVisible < $lastPage - 1)<span aria-hidden="true">…</span>@endif
                <button type="button" data-admin-table-page="{{ $lastPage - 1 }}" aria-label="Halaman {{ $lastPage }}">{{ $lastPage }}</button>
            @endif
        </span>
        <button type="button" data-admin-table-next aria-label="Halaman berikutnya" @disabled(! $paginator->hasMorePages())>Berikutnya</button>
    </div>
</nav>
