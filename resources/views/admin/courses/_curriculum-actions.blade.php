<details class="nc-menu">
    <summary aria-label="Aksi {{ $kind }} {{ $record->title }}"><x-dashboard.icon name="ellipsis-vertical" /></summary>
    <div class="nc-menu-panel">
        <button type="button" data-toggle="modal" data-target="#{{ $editTarget }}"><x-dashboard.icon name="pencil" />Ubah {{ $kind }}</button>
        <button type="button" data-order-move="up"><x-dashboard.icon name="arrow-up" />Pindahkan ke atas</button>
        <button type="button" data-order-move="down"><x-dashboard.icon name="arrow-down" />Pindahkan ke bawah</button>
        <button type="button" class="nc-danger" data-toggle="modal" data-target="#confirm-delete-modal" data-delete-url="{{ $deleteUrl }}" data-name="{{ $deleteName }}"><x-dashboard.icon name="trash-2" />Hapus {{ $kind }}</button>
    </div>
</details>
