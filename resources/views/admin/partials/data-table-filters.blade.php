<form method="GET" action="{{ $action }}" class="nc-data-table-filters" data-admin-table-filters>
    <div><label for="{{ $idPrefix }}-page-size">Tampilkan data</label><select id="{{ $idPrefix }}-page-size" name="per_page" class="form-control">@foreach([10, 25, 50, 100] as $size)<option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 10) === $size)>{{ $size }} data</option>@endforeach</select></div>
    <div><label for="{{ $idPrefix }}-search">{{ $searchLabel }}</label><input id="{{ $idPrefix }}-search" name="q" type="search" maxlength="120" value="{{ $filters['q'] ?? '' }}" placeholder="{{ $searchPlaceholder }}" class="form-control"></div>
    <div><label for="{{ $idPrefix }}-status">Status</label><select id="{{ $idPrefix }}-status" name="status" class="form-control"><option value="">Semua status</option>@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
    <a class="nc-data-table-reset" href="{{ $action }}" data-admin-table-reset aria-label="Reset filter" data-action-tooltip="Reset filter"><x-dashboard.icon name="x" /></a>
    <noscript><button class="btn btn-primary" type="submit">Cari data</button></noscript>
</form>
